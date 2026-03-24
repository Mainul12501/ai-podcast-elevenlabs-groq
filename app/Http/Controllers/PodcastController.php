<?php

namespace App\Http\Controllers;

use App\Agents\PodcastScriptAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;

class PodcastController extends Controller
{
    // Fallback voice IDs if user doesn't pick
    private const DEFAULT_VOICES = [
        'Alex'  => 'JBFqnCBsd6RMkjVDRZzb', // George
        'Sarah' => 'EXAVITQu4vr4xnSDxMaL', // Sarah
    ];

    public function index()
    {
        return view('podcast');
    }

    public function voices()
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => env('ELEVENLABS_API_KEY'),
            ])->timeout(15)->get('https://api.elevenlabs.io/v1/voices');

            if (! $response->successful()) {
                return response()->json(['error' => 'Could not fetch voices.'], 500);
            }

            $voices = collect($response->json('voices', []))
                ->map(fn($v) => [
                    'voice_id'    => $v['voice_id'],
                    'name'        => $v['name'],
                    'description' => $v['description'] ?? null,
                    'preview_url' => $v['preview_url'] ?? null,
                    'labels'      => $v['labels'] ?? [],
                ])
                ->sortBy('name')
                ->values();

            return response()->json($voices);
        } catch (\Exception $e) {
            Log::error('PodcastController: voices error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load voices.'], 500);
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'url'                 => 'required|url|max:2048',
            'voice_alex'          => 'nullable|string|max:100',
            'voice_sarah'         => 'nullable|string|max:100',
            'conversation_length' => 'nullable|integer|min:6|max:22',
        ]);

        $voices = [
            'Alex'  => $request->input('voice_alex',  self::DEFAULT_VOICES['Alex']),
            'Sarah' => $request->input('voice_sarah', self::DEFAULT_VOICES['Sarah']),
        ];

        // 1. Fetch and extract product page text
        $html = $this->fetchPage($request->url);
        $text = $this->extractText($html);

        if (strlen($text) < 80) {
            return response()->json([
                'error' => 'Could not extract enough content from that page. Try a different product URL.',
            ], 422);
        }

        // 2. Generate podcast script via Laravel AI SDK
        $length     = $request->integer('conversation_length', 12);
        $scriptData = $this->generateScript($text, $request->url, $length);

        if ($scriptData === 'rate_limited') {
            $provider = ucfirst(env('PODCAST_AI_PROVIDER', 'groq'));
            return response()->json([
                'error' => "{$provider} rate limit reached. Wait a minute and try again, or check your API quota.",
            ], 429);
        }

        if (! $scriptData || empty($scriptData['dialogue'])) {
            return response()->json([
                'error' => 'Failed to generate podcast script. Please try again.',
            ], 500);
        }

        // 3. Generate audio via ElevenLabs Text-to-Dialogue
        $audioUrl = $this->generateAudio($scriptData['dialogue'], $voices);

        return response()->json([
            'title'     => $scriptData['title'] ?? 'Product Spotlight',
            'dialogue'  => $scriptData['dialogue'],
            'audio_url' => $audioUrl,
        ]);
    }

    private function fetchPage(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(20)->get($url);

            return $response->successful() ? $response->body() : '';
        } catch (\Exception $e) {
            Log::error('PodcastController: fetchPage error', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function extractText(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $html = preg_replace('/<(script|style|nav|footer|header|aside|iframe)[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<\/(p|div|li|h[1-6]|br|tr)>/i', ' ', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        return mb_substr($text, 0, 3500);
    }

    private function generateScript(string $pageContent, string $url, int $length = 18): array|string|null
    {
        $agent = new PodcastScriptAgent();

        $prompt = <<<PROMPT
        Generate an engaging podcast script about the product from this page.
        The dialogue array must have EXACTLY {$length} exchanges — no more, no less.

        Product URL: {$url}

        Page content:
        {$pageContent}
        PROMPT;

        [$provider, $model] = $this->resolveProvider();
        $attempts = 3;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                if ($i > 0) {
                    sleep(5 * $i);
                }

                $response = $agent->prompt($prompt, provider: $provider, model: $model);
                $raw      = trim((string) $response);

                $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
                $raw = preg_replace('/\s*```$/', '', $raw);

                $data = json_decode($raw, true);

                if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['dialogue'])) {
                    Log::error('PodcastController: JSON parse failed', ['raw' => substr($raw, 0, 500)]);
                    continue;
                }

                $data['dialogue'] = array_values(array_filter($data['dialogue'], function ($turn) {
                    return isset($turn['speaker'], $turn['text'])
                        && in_array($turn['speaker'], ['Alex', 'Sarah'])
                        && strlen(trim($turn['text'])) > 0;
                }));

                return $data;
            } catch (\Exception $e) {
                Log::error('PodcastController: generateScript error', [
                    'attempt' => $i + 1,
                    'error'   => $e->getMessage(),
                ]);

                if (str_contains($e->getMessage(), 'rate limit') || str_contains($e->getMessage(), 'rate limited')) {
                    return 'rate_limited';
                }
            }
        }

        return null;
    }

    private function resolveProvider(): array
    {
        return match (env('PODCAST_AI_PROVIDER', 'groq')) {
            'gemini' => [Lab::Gemini, 'gemini-2.0-flash'],
            'openai' => [Lab::OpenAI, 'gpt-4o-mini'],
            default  => [Lab::Groq, 'llama-3.3-70b-versatile'],
        };
    }

    private function generateAudio(array $dialogue, array $voices): ?string
    {
        try {
            $inputs = array_map(fn($turn) => [
                'text'     => $turn['text'],
                'voice_id' => $voices[$turn['speaker']] ?? self::DEFAULT_VOICES['Alex'],
            ], $dialogue);

            $response = Http::withHeaders([
                'xi-api-key'   => env('ELEVENLABS_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(180)->post('https://api.elevenlabs.io/v1/text-to-dialogue', [
                'inputs'        => $inputs,
                'model_id'      => 'eleven_multilingual_v2',
                'language_code' => 'en',
            ]);

            if (! $response->successful()) {
                Log::error('PodcastController: ElevenLabs error', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 500),
                ]);
                return null;
            }

            $filename = 'podcasts/' . Str::uuid() . '.mp3';
            Storage::disk('public')->put($filename, $response->body());

            return Storage::disk('public')->url($filename);
        } catch (\Exception $e) {
            Log::error('PodcastController: generateAudio error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
