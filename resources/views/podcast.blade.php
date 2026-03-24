<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Podcast Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gray-950 text-gray-100 font-sans antialiased"
      x-data="podcastApp()"
      x-init="loadVoices()"
      x-cloak>

    {{-- Background blobs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-violet-700/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -right-32 w-80 h-80 bg-purple-600/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 left-1/3 w-72 h-72 bg-indigo-700/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative min-h-screen flex flex-col">

        {{-- Header --}}
        <header class="border-b border-white/5 backdrop-blur-sm bg-black/20 sticky top-0 z-20">
            <div class="max-w-5xl mx-auto px-6 py-4 flex items-center gap-3">
                <div class="w-8 h-8 bg-violet-600 rounded-lg flex items-center justify-center shadow-lg shadow-violet-600/30">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2H3v2a9 9 0 0 0 8 8.94V23h2v-2.06A9 9 0 0 0 21 12v-2h-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-white tracking-wide">AI Podcast Generator</h1>
                    <p class="text-xs text-gray-400">Powered by Groq + ElevenLabs</p>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-5xl mx-auto w-full px-6 py-10 space-y-6">

            {{-- Hero --}}
            <div class="text-center" x-show="!result">
                <div class="inline-flex items-center gap-2 bg-violet-600/10 border border-violet-500/20 text-violet-300 text-xs font-medium px-3 py-1.5 rounded-full mb-5">
                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-pulse"></span>
                    AI-Powered · Two Hosts · ElevenLabs Audio
                </div>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-white mb-4 leading-tight tracking-tight">
                    Turn any product page<br>into a <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-purple-300">podcast episode</span>
                </h2>
                <p class="text-gray-400 text-lg max-w-xl mx-auto">
                    Paste a product URL, pick your host voices, and get a full AI-generated podcast with studio-quality audio.
                </p>
            </div>

            {{-- URL Input Card --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm shadow-2xl">
                <form @submit.prevent="generate">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Product Page URL</label>
                    <div class="flex gap-3">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <input type="url" x-model="url"
                                placeholder="https://example.com/product/amazing-gadget"
                                required :disabled="loading"
                                class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition disabled:opacity-50">
                        </div>
                        <button type="submit" :disabled="loading || !url"
                            class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 disabled:bg-violet-800 disabled:cursor-not-allowed text-white font-semibold text-sm px-5 py-3 rounded-xl transition-all shadow-lg shadow-violet-600/25 whitespace-nowrap">
                            <template x-if="!loading">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2H3v2a9 9 0 0 0 8 8.94V23h2v-2.06A9 9 0 0 0 21 12v-2h-2z"/>
                                </svg>
                            </template>
                            <template x-if="loading">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </template>
                            <span x-text="loading ? loadingStep : 'Generate Podcast'"></span>
                        </button>
                    </div>

                    {{-- Conversation length slider --}}
                    <div class="mt-5 pt-5 border-t border-white/5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                Conversation Length
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-violet-300 bg-violet-600/20 border border-violet-500/30 px-2.5 py-1 rounded-lg" x-text="conversationLength + ' exchanges'"></span>
                                <span class="text-xs text-gray-500" x-text="lengthLabel"></span>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="range"
                                min="6" max="22" step="2"
                                x-model.number="conversationLength"
                                :disabled="loading"
                                class="w-full h-2 rounded-full appearance-none cursor-pointer accent-violet-500 bg-white/10 disabled:opacity-50"
                                :style="`background: linear-gradient(to right, #7c3aed 0%, #7c3aed ${((conversationLength - 6) / 16) * 100}%, rgba(255,255,255,0.1) ${((conversationLength - 6) / 16) * 100}%, rgba(255,255,255,0.1) 100%)`"
                            >
                            <div class="flex justify-between text-xs text-gray-600 mt-1.5 px-0.5">
                                <span>Short</span>
                                <span>Medium</span>
                                <span>Long</span>
                            </div>
                        </div>
                    </div>

                    {{-- Error --}}
                    <div x-show="error" x-transition class="mt-3 flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-2.5 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span x-text="error"></span>
                    </div>

                    {{-- Progress Steps --}}
                    <div x-show="loading" x-transition class="mt-5 pt-5 border-t border-white/5">
                        <div class="flex items-center justify-between gap-2">
                            <template x-for="(step, i) in steps" :key="i">
                                <div class="flex-1 flex flex-col items-center gap-1.5">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-all duration-500"
                                         :class="currentStep > i ? 'bg-violet-600 text-white' : currentStep === i ? 'bg-violet-600/30 border-2 border-violet-500 text-violet-300 animate-pulse' : 'bg-white/5 border border-white/10 text-gray-600'">
                                        <template x-if="currentStep > i">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </template>
                                        <template x-if="currentStep <= i">
                                            <span x-text="i + 1"></span>
                                        </template>
                                    </div>
                                    <span class="text-xs text-center leading-tight" :class="currentStep >= i ? 'text-gray-300' : 'text-gray-600'" x-text="step"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- VOICE PICKER                           --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-violet-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2H3v2a9 9 0 0 0 8 8.94V23h2v-2.06A9 9 0 0 0 21 12v-2h-2z"/>
                        </svg>
                        Host Voices
                    </h3>
                    <span class="text-xs text-gray-500">Pick one voice per host</span>
                </div>

                {{-- Loading voices --}}
                <div x-show="voicesLoading" class="p-8 flex flex-col items-center gap-3 text-gray-500">
                    <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span class="text-sm">Loading available voices…</span>
                </div>

                {{-- Voice panels --}}
                <div x-show="!voicesLoading" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/5">

                    {{-- Alex panel --}}
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold shadow-lg shadow-blue-500/20">A</div>
                            <div>
                                <div class="text-sm font-semibold text-white">Alex's Voice</div>
                                <div class="text-xs text-gray-500" x-text="voiceName(selectedVoiceAlex)"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-2 max-h-72 overflow-y-auto pr-1">
                            <template x-for="voice in voices" :key="'alex-' + voice.voice_id">
                                <div @click="selectedVoiceAlex = voice.voice_id"
                                     class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                     :class="selectedVoiceAlex === voice.voice_id
                                        ? 'bg-blue-600/15 border-blue-500/50 ring-1 ring-blue-500/30'
                                        : 'bg-white/3 border-white/5 hover:bg-white/8 hover:border-white/15'">

                                    {{-- Selected indicator --}}
                                    <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                         :class="selectedVoiceAlex === voice.voice_id ? 'border-blue-500 bg-blue-500' : 'border-gray-600'">
                                        <div x-show="selectedVoiceAlex === voice.voice_id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    </div>

                                    {{-- Name + description --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-white" x-text="voice.name"></div>
                                        <div class="text-xs text-gray-500 truncate" x-text="voice.description || labelsText(voice.labels)"></div>
                                    </div>

                                    {{-- Preview button --}}
                                    <button type="button"
                                        x-show="voice.preview_url"
                                        @click.stop="previewVoice(voice)"
                                        class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 transition-all"
                                        :class="previewingId === voice.voice_id
                                            ? 'bg-violet-600 text-white'
                                            : 'bg-white/10 hover:bg-white/20 text-gray-400 hover:text-white'"
                                        :title="previewingId === voice.voice_id ? 'Stop preview' : 'Preview voice'">
                                        <template x-if="previewingId !== voice.voice_id">
                                            <svg class="w-3 h-3 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </template>
                                        <template x-if="previewingId === voice.voice_id">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                        </template>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Sarah panel --}}
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-xs font-bold shadow-lg shadow-violet-500/20">S</div>
                            <div>
                                <div class="text-sm font-semibold text-white">Sarah's Voice</div>
                                <div class="text-xs text-gray-500" x-text="voiceName(selectedVoiceSarah)"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-2 max-h-72 overflow-y-auto pr-1">
                            <template x-for="voice in voices" :key="'sarah-' + voice.voice_id">
                                <div @click="selectedVoiceSarah = voice.voice_id"
                                     class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                     :class="selectedVoiceSarah === voice.voice_id
                                        ? 'bg-violet-600/15 border-violet-500/50 ring-1 ring-violet-500/30'
                                        : 'bg-white/3 border-white/5 hover:bg-white/8 hover:border-white/15'">

                                    <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                         :class="selectedVoiceSarah === voice.voice_id ? 'border-violet-500 bg-violet-500' : 'border-gray-600'">
                                        <div x-show="selectedVoiceSarah === voice.voice_id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-white" x-text="voice.name"></div>
                                        <div class="text-xs text-gray-500 truncate" x-text="voice.description || labelsText(voice.labels)"></div>
                                    </div>

                                    <button type="button"
                                        x-show="voice.preview_url"
                                        @click.stop="previewVoice(voice)"
                                        class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 transition-all"
                                        :class="previewingId === voice.voice_id
                                            ? 'bg-violet-600 text-white'
                                            : 'bg-white/10 hover:bg-white/20 text-gray-400 hover:text-white'"
                                        :title="previewingId === voice.voice_id ? 'Stop preview' : 'Preview voice'">
                                        <template x-if="previewingId !== voice.voice_id">
                                            <svg class="w-3 h-3 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </template>
                                        <template x-if="previewingId === voice.voice_id">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                        </template>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                {{-- Same-voice warning --}}
                <div x-show="selectedVoiceAlex && selectedVoiceSarah && selectedVoiceAlex === selectedVoiceSarah"
                     class="mx-5 mb-5 flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs px-4 py-2.5 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Both hosts have the same voice — the podcast will sound like one person talking to themselves. Pick different voices for the best experience.
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- RESULTS                                --}}
            {{-- ═══════════════════════════════════════ --}}
            <div x-show="result" x-transition class="space-y-6">

                {{-- Player card --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">
                    <div class="p-6 border-b border-white/5">
                        <div class="text-xs font-medium text-violet-400 uppercase tracking-widest mb-1">New Episode</div>
                        <h3 class="text-xl font-bold text-white" x-text="result?.title"></h3>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600"></div>
                                Alex — <span x-text="voiceName(selectedVoiceAlex)"></span>
                            </span>
                            <span class="flex items-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-gradient-to-br from-violet-500 to-purple-600"></div>
                                Sarah — <span x-text="voiceName(selectedVoiceSarah)"></span>
                            </span>
                        </div>
                    </div>

                    {{-- WaveSurfer player --}}
                    <div x-show="result?.audio_url" class="p-6 bg-black/20">
                        <div id="waveform" class="w-full mb-5 rounded-lg overflow-hidden min-h-16"></div>

                        <div class="flex items-center gap-4">
                            {{-- Play/Pause --}}
                            <button @click="togglePlay" :disabled="!wsReady"
                                class="w-12 h-12 bg-violet-600 hover:bg-violet-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-full flex items-center justify-center shadow-lg shadow-violet-600/30 transition-all flex-shrink-0">
                                <template x-if="wsLoading">
                                    <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </template>
                                <template x-if="!wsLoading && !playing">
                                    <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </template>
                                <template x-if="!wsLoading && playing">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                </template>
                            </button>

                            {{-- Time --}}
                            <div class="flex items-center gap-1.5 text-sm font-mono text-gray-300 flex-shrink-0">
                                <span x-text="formatTime(currentTime)">0:00</span>
                                <span class="text-gray-600">/</span>
                                <span x-text="formatTime(duration)" class="text-gray-500">0:00</span>
                            </div>

                            {{-- Volume --}}
                            <div class="flex items-center gap-2 ml-auto">
                                <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
                                </svg>
                                <input type="range" min="0" max="1" step="0.05" value="1"
                                    @input="setVolume($event.target.value)"
                                    class="w-20 accent-violet-500 cursor-pointer">
                            </div>

                            {{-- Download --}}
                            <a :href="result?.audio_url"
                               :download="(result?.title ?? 'podcast') + '.mp3'"
                               class="flex items-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 hover:text-white text-xs font-medium px-4 py-2.5 rounded-xl transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download MP3
                            </a>
                        </div>
                    </div>

                    {{-- No audio fallback --}}
                    <div x-show="result && !result.audio_url" class="p-4 bg-amber-500/10 border-t border-amber-500/20">
                        <p class="text-amber-400 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Script generated but audio encoding failed. Check your ElevenLabs account.
                        </p>
                    </div>
                </div>

                {{-- Transcript --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl backdrop-blur-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                        <h4 class="font-semibold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Podcast Script
                        </h4>
                        <span class="text-xs text-gray-500" x-text="(result?.dialogue?.length ?? 0) + ' exchanges'"></span>
                    </div>
                    <div class="divide-y divide-white/5 max-h-[600px] overflow-y-auto">
                        <template x-for="(turn, index) in (result?.dialogue ?? [])" :key="index">
                            <div class="px-6 py-4 flex gap-4 hover:bg-white/2 transition"
                                 :class="turn.speaker === 'Alex' ? 'flex-row' : 'flex-row-reverse'">
                                <div class="flex-shrink-0">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shadow-lg"
                                         :class="turn.speaker === 'Alex' ? 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/20' : 'bg-gradient-to-br from-violet-500 to-purple-600 shadow-violet-500/20'">
                                        <span x-text="turn.speaker === 'Alex' ? 'A' : 'S'"></span>
                                    </div>
                                </div>
                                <div class="max-w-[80%]" :class="turn.speaker !== 'Alex' ? 'text-right' : ''">
                                    <div class="text-xs font-semibold mb-1.5"
                                         :class="turn.speaker === 'Alex' ? 'text-blue-400' : 'text-violet-400'"
                                         x-text="turn.speaker + ' · ' + voiceName(turn.speaker === 'Alex' ? selectedVoiceAlex : selectedVoiceSarah)">
                                    </div>
                                    <div class="inline-block text-sm leading-relaxed text-gray-200 rounded-2xl px-4 py-2.5"
                                         :class="turn.speaker === 'Alex' ? 'bg-blue-600/10 border border-blue-600/20 rounded-tl-sm' : 'bg-violet-600/10 border border-violet-600/20 rounded-tr-sm'"
                                         x-html="formatLine(turn.text)">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Generate again --}}
                <div class="flex justify-center">
                    <button @click="resetResult"
                        class="flex items-center gap-2 text-sm text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-5 py-2.5 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Generate Another
                    </button>
                </div>
            </div>

            {{-- How it works --}}
            <div x-show="!result && !loading" x-transition class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <template x-for="(item, i) in howItWorks" :key="i">
                    <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                        <div class="w-10 h-10 rounded-xl bg-violet-600/10 border border-violet-500/20 flex items-center justify-center mx-auto mb-3" x-html="item.icon"></div>
                        <h4 class="font-semibold text-white text-sm mb-1" x-text="item.title"></h4>
                        <p class="text-gray-500 text-xs leading-relaxed" x-text="item.desc"></p>
                    </div>
                </template>
            </div>

        </main>

        <footer class="text-center py-6 text-xs text-gray-600 border-t border-white/5">
            AI Podcast Generator &middot; Groq Llama 3.3 &middot; ElevenLabs Multilingual v2 (English)
        </footer>

    </div>

    <script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>

    <script>
    function podcastApp() {
        return {
            // Form
            url: '',
            conversationLength: 12,
            loading: false,
            error: null,
            currentStep: 0,
            loadingStep: 'Fetching page...',
            steps: ['Fetching page', 'Writing script', 'Generating audio'],

            get lengthLabel() {
                if (this.conversationLength <= 8)  return '~2 min';
                if (this.conversationLength <= 12) return '~3 min';
                if (this.conversationLength <= 16) return '~5 min';
                if (this.conversationLength <= 20) return '~6 min';
                return '~7 min';
            },

            // Voices
            voices: [],
            voicesLoading: true,
            selectedVoiceAlex:  'JBFqnCBsd6RMkjVDRZzb', // George (default)
            selectedVoiceSarah: 'EXAVITQu4vr4xnSDxMaL', // Sarah (default)
            previewingId: null,
            previewAudio: null,

            // Result
            result: null,

            // WaveSurfer
            ws: null,
            wsReady: false,
            wsLoading: false,
            playing: false,
            currentTime: 0,
            duration: 0,

            howItWorks: [
                {
                    icon: '<svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>',
                    title: '1. Paste a product URL',
                    desc: 'Any product page — e-commerce, SaaS, landing page. We scrape the content automatically.'
                },
                {
                    icon: '<svg class="w-5 h-5 text-violet-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2H3v2a9 9 0 0 0 8 8.94V23h2v-2.06A9 9 0 0 0 21 12v-2h-2z"/></svg>',
                    title: '2. Pick your host voices',
                    desc: 'Preview and choose from all available ElevenLabs voices for each host.'
                },
                {
                    icon: '<svg class="w-5 h-5 text-violet-400" fill="currentColor" viewBox="0 0 24 24"><path d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>',
                    title: '3. Get your podcast',
                    desc: 'Groq writes the script, ElevenLabs voices it in English with natural banter and chuckles.'
                },
            ],

            async loadVoices() {
                try {
                    const res = await fetch('/voices');
                    const data = await res.json();
                    this.voices = Array.isArray(data) ? data : [];
                } catch (e) {
                    console.error('Failed to load voices', e);
                } finally {
                    this.voicesLoading = false;
                }
            },

            voiceName(voiceId) {
                const v = this.voices.find(v => v.voice_id === voiceId);
                return v ? v.name : '';
            },

            labelsText(labels) {
                if (!labels || typeof labels !== 'object') return '';
                return Object.values(labels).filter(Boolean).join(' · ');
            },

            previewVoice(voice) {
                // Stop current preview
                if (this.previewAudio) {
                    this.previewAudio.pause();
                    this.previewAudio = null;
                }
                if (this.previewingId === voice.voice_id) {
                    this.previewingId = null;
                    return;
                }
                if (!voice.preview_url) return;

                this.previewingId = voice.voice_id;
                this.previewAudio = new Audio(voice.preview_url);
                this.previewAudio.play();
                this.previewAudio.onended = () => { this.previewingId = null; };
                this.previewAudio.onerror = () => { this.previewingId = null; };
            },

            async generate() {
                // Stop any voice preview
                if (this.previewAudio) {
                    this.previewAudio.pause();
                    this.previewAudio = null;
                    this.previewingId = null;
                }

                this.error = null;
                this.result = null;
                this.playing = false;
                this.wsReady = false;
                this.loading = true;
                this.currentStep = 0;
                this.loadingStep = 'Fetching page...';

                if (this.ws) { this.ws.destroy(); this.ws = null; }

                const t1 = setTimeout(() => { this.currentStep = 1; this.loadingStep = 'Writing script...'; }, 3000);
                const t2 = setTimeout(() => { this.currentStep = 2; this.loadingStep = 'Generating audio...'; }, 12000);

                try {
                    const res = await fetch('/generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            url:                 this.url,
                            voice_alex:          this.selectedVoiceAlex,
                            voice_sarah:         this.selectedVoiceSarah,
                            conversation_length: this.conversationLength,
                        }),
                    });

                    clearTimeout(t1); clearTimeout(t2);
                    const data = await res.json();

                    if (!res.ok) {
                        this.error = data.error || 'Something went wrong. Please try again.';
                    } else {
                        this.currentStep = 3;
                        await new Promise(r => setTimeout(r, 400));
                        this.result = data;
                        if (data.audio_url) {
                            await this.$nextTick();
                            this.initWaveSurfer(data.audio_url);
                        }
                    }
                } catch (e) {
                    clearTimeout(t1); clearTimeout(t2);
                    this.error = 'Network error. Please check your connection and try again.';
                } finally {
                    this.loading = false;
                }
            },

            initWaveSurfer(audioUrl) {
                this.wsLoading = true;
                this.wsReady = false;

                this.ws = WaveSurfer.create({
                    container: '#waveform',
                    waveColor: 'rgba(124, 58, 237, 0.45)',
                    progressColor: '#7c3aed',
                    cursorColor: '#a78bfa',
                    cursorWidth: 2,
                    barWidth: 3,
                    barGap: 2,
                    barRadius: 10,
                    height: 72,
                    normalize: true,
                    url: audioUrl,
                });

                this.ws.on('ready', () => {
                    this.wsLoading = false;
                    this.wsReady = true;
                    this.duration = this.ws.getDuration();
                });
                this.ws.on('timeupdate', t => { this.currentTime = t; });
                this.ws.on('finish', () => { this.playing = false; });
                this.ws.on('error', err => { console.error('WaveSurfer:', err); this.wsLoading = false; });
            },

            togglePlay() {
                if (!this.ws || !this.wsReady) return;
                this.ws.playPause();
                this.playing = this.ws.isPlaying();
            },

            setVolume(val) {
                if (this.ws) this.ws.setVolume(parseFloat(val));
            },

            formatTime(s) {
                if (!s || isNaN(s)) return '0:00';
                return `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
            },

            formatLine(text) {
                return text.replace(/\[([^\]]+)\]/g, '<span class="text-violet-400/70 italic text-xs">[$1]</span>');
            },

            resetResult() {
                if (this.ws) { this.ws.destroy(); this.ws = null; }
                this.result = null;
                this.error = null;
                this.playing = false;
                this.wsReady = false;
                this.wsLoading = false;
                this.currentTime = 0;
                this.duration = 0;
                this.url = '';
            },
        };
    }
    </script>

</body>
</html>
