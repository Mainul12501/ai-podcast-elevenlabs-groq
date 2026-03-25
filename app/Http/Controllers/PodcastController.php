<?php

namespace App\Http\Controllers;

use App\Agents\ImageAnalysisAgent;
use App\Agents\PodcastScriptAgent;
use App\Models\Podcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Image;

class PodcastController extends Controller
{
    private const DEFAULT_VOICES = [
        'Alex'  => 'JBFqnCBsd6RMkjVDRZzb', // George
        'Sarah' => 'EXAVITQu4vr4xnSDxMaL', // Sarah
    ];

    public function index()
    {
        return view('podcast');
    }

    public function history()
    {
        $podcasts = Podcast::latest()->get()->map(fn($p) => [
            'id'                  => $p->id,
            'title'               => $p->title,
            'product_url'         => $p->product_url,
            'conversation_length' => $p->conversation_length,
            'voice_alex_name'     => $p->voice_alex_name,
            'voice_sarah_name'    => $p->voice_sarah_name,
            'dialogue_count'      => count($p->dialogue),
            'audio_url'           => $p->audio_url,
            'created_at'          => $p->created_at->format('M j, Y · g:i A'),
        ]);

        return response()->json($podcasts);
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

    // Step 1 — generate transcript only
    public function generateScript(Request $request)
    {
        $request->validate([
            'url'                 => 'required|url|max:2048',
            'conversation_length' => 'nullable|integer|min:4|max:22',
            'extra_instructions'  => 'nullable|string|max:1000',
            'images.*'            => 'nullable|image|max:8192',
            'image_urls'          => 'nullable|string|max:5000',
        ]);

        $html = $this->fetchPage($request->url);
        $text = $this->extractText($html);

        if (strlen($text) < 80) {
            return response()->json([
                'error' => 'Could not extract enough content from that page. Try a different product URL.',
            ], 422);
        }

        $length             = $request->integer('conversation_length', 12);
        $extra              = trim($request->input('extra_instructions', ''));
        [$imageDescriptions, $storedImageUrls] = $this->analyzeImages($request);
        $scriptData         = $this->buildScript($text, $request->url, $length, $extra, $imageDescriptions);

        if ($scriptData === 'rate_limited') {
            $provider = ucfirst(env('PODCAST_AI_PROVIDER', 'groq'));
            return response()->json([
                'error' => "{$provider} rate limit reached. Wait a minute and try again.",
            ], 429);
        }

        if (! $scriptData || empty($scriptData['dialogue'])) {
            return response()->json([
                'error' => 'Failed to generate podcast script. Please try again.',
            ], 500);
        }

        return response()->json([
            'title'              => $scriptData['title'] ?? 'Product Spotlight',
            'dialogue'           => $scriptData['dialogue'],
            'stored_image_urls'  => $storedImageUrls,
        ]);
    }

    // Step 2 — generate audio from (possibly edited) dialogue
    public function generateAudio(Request $request)
    {
        $request->validate([
            'title'               => 'nullable|string|max:255',
            'product_url'         => 'nullable|url|max:2048',
            'extra_instructions'  => 'nullable|string|max:1000',
            'image_urls'          => 'nullable|array',
            'image_urls.*'        => 'nullable|url|max:2048',
            'conversation_length' => 'nullable|integer|min:4|max:22',
            'dialogue'            => 'required|array|min:1',
            'dialogue.*.speaker'  => 'required|in:Alex,Sarah',
            'dialogue.*.text'     => 'required|string|max:1000',
            'voice_alex'          => 'nullable|string|max:100',
            'voice_sarah'         => 'nullable|string|max:100',
        ]);

        $voiceAlexId  = $request->input('voice_alex',  self::DEFAULT_VOICES['Alex']);
        $voiceSarahId = $request->input('voice_sarah', self::DEFAULT_VOICES['Sarah']);

        $voices = ['Alex' => $voiceAlexId, 'Sarah' => $voiceSarahId];

        [$audioPath, $audioUrl] = $this->buildAudio($request->input('dialogue'), $voices);

        if (! $audioUrl) {
            return response()->json([
                'error' => 'Audio generation failed. Check your ElevenLabs account.',
            ], 500);
        }

        // Resolve voice names from ElevenLabs voices list (best-effort)
        $voiceNames   = $this->fetchVoiceNames([$voiceAlexId, $voiceSarahId]);

        $podcast = Podcast::create([
            'title'               => $request->input('title', 'Product Spotlight'),
            'product_url'         => $request->input('product_url', ''),
            'extra_instructions'  => $request->input('extra_instructions') ?: null,
            'image_urls'          => $request->input('image_urls') ?: null,
            'conversation_length' => $request->integer('conversation_length', 12),
            'voice_alex_id'       => $voiceAlexId,
            'voice_alex_name'     => $voiceNames[$voiceAlexId] ?? $voiceAlexId,
            'voice_sarah_id'      => $voiceSarahId,
            'voice_sarah_name'    => $voiceNames[$voiceSarahId] ?? $voiceSarahId,
            'dialogue'            => $request->input('dialogue'),
            'audio_path'          => $audioPath,
            'audio_url'           => $audioUrl,
        ]);

        return response()->json([
            'audio_url'  => $audioUrl,
            'podcast_id' => $podcast->id,
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
        if (empty($html)) return '';

        $html = preg_replace('/<(script|style|nav|footer|header|aside|iframe)[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<\/(p|div|li|h[1-6]|br|tr)>/i', ' ', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return mb_substr(trim($text), 0, 3500);
    }

    private function buildScript(string $pageContent, string $url, int $length = 12, string $extra = '', string $imageDescriptions = ''): array|string|null
    {
        $agent = new PodcastScriptAgent();

        $extraBlock = $extra
            ? "\n        ADDITIONAL INSTRUCTIONS (follow these carefully):\n        {$extra}\n"
            : '';

        $imageBlock = $imageDescriptions
            ? "\n        PRODUCT IMAGE ANALYSIS (use these visual details to enrich the script):\n        {$imageDescriptions}\n"
            : '';

        $prompt = <<<PROMPT
        Generate an engaging podcast script about the product from this page.
        The dialogue array must have EXACTLY {$length} exchanges — no more, no less.
        {$extraBlock}{$imageBlock}
        Product URL: {$url}

        Page content:
        {$pageContent}
        PROMPT;

        [$provider, $model] = $this->resolveProvider();

        for ($i = 0; $i < 3; $i++) {
            try {
                if ($i > 0) sleep(5 * $i);

                $response = $agent->prompt($prompt, provider: $provider, model: $model);
                $raw      = trim((string) $response);
                $raw      = preg_replace('/^```(?:json)?\s*/i', '', $raw);
                $raw      = preg_replace('/\s*```$/', '', $raw);
                $data     = json_decode($raw, true);

                if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['dialogue'])) {
                    Log::error('PodcastController: JSON parse failed', ['raw' => substr($raw, 0, 500)]);
                    continue;
                }

                $data['dialogue'] = array_values(array_filter($data['dialogue'], fn($t) =>
                    isset($t['speaker'], $t['text'])
                    && in_array($t['speaker'], ['Alex', 'Sarah'])
                    && strlen(trim($t['text'])) > 0
                ));

                return $data;
            } catch (\Exception $e) {
                Log::error('PodcastController: buildScript error', ['attempt' => $i + 1, 'error' => $e->getMessage()]);
                if (str_contains($e->getMessage(), 'rate limit') || str_contains($e->getMessage(), 'rate limited')) {
                    return 'rate_limited';
                }
            }
        }

        return null;
    }

    private function analyzeImages(Request $request): array
    {
        $attachments      = [];
        $storedImageUrls  = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $attachments[] = Image::fromUpload($file);
                $path = $file->store('podcast-images', 'public');
                $storedImageUrls[] = Storage::disk('public')->url($path);
            }
        }

        $rawUrls = trim($request->input('image_urls', ''));
        if ($rawUrls) {
            foreach (explode("\n", $rawUrls) as $line) {
                $url = trim($line);
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    $attachments[] = Image::fromUrl($url);
                }
            }
        }

        if (empty($attachments)) return ['', $storedImageUrls];

        try {
            $agent = new ImageAnalysisAgent();
            $count = count($attachments);
            $response = $agent->prompt(
                "Analyze these {$count} product image(s) and describe each one in detail.",
                $attachments,
                provider: Lab::Gemini,
                model: 'gemini-2.0-flash'
            );
            return [trim((string) $response), $storedImageUrls];
        } catch (\Exception $e) {
            Log::error('PodcastController: image analysis error', ['error' => $e->getMessage()]);
            return ['', $storedImageUrls];
        }
    }

    private function resolveProvider(): array
    {
        return match (env('PODCAST_AI_PROVIDER', 'groq')) {
            'gemini' => [Lab::Gemini, 'gemini-2.0-flash'],
            'openai' => [Lab::OpenAI, 'gpt-4o-mini'],
            default  => [Lab::Groq, 'llama-3.3-70b-versatile'],
        };
    }

    private function buildAudio(array $dialogue, array $voices): array
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
                return [null, null];
            }

            $filename = 'podcasts/' . Str::uuid() . '.mp3';
            Storage::disk('public')->put($filename, $response->body());

            return [$filename, Storage::disk('public')->url($filename)];
        } catch (\Exception $e) {
            Log::error('PodcastController: buildAudio error', ['error' => $e->getMessage()]);
            return [null, null];
        }
    }

    private function fetchVoiceNames(array $voiceIds): array
    {
        try {
            $response = Http::withHeaders(['xi-api-key' => env('ELEVENLABS_API_KEY')])
                ->timeout(10)
                ->get('https://api.elevenlabs.io/v1/voices');

            if (! $response->successful()) return [];

            return collect($response->json('voices', []))
                ->whereIn('voice_id', $voiceIds)
                ->pluck('name', 'voice_id')
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }
}
