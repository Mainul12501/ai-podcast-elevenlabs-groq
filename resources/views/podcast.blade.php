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

                {{-- History button --}}
                <button @click="historyOpen = !historyOpen; if(historyOpen) loadHistory()"
                    class="ml-auto flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border transition"
                    :class="historyOpen ? 'bg-violet-600/20 border-violet-500/40 text-violet-300' : 'bg-white/5 border-white/10 text-gray-400 hover:text-white hover:bg-white/10'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    History
                </button>

                {{-- Step indicator --}}
                <div class="flex items-center gap-2" x-show="transcript">
                    <template x-for="(s, i) in ['Transcript', 'Audio']" :key="i">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center"
                                 :class="(i === 0 && transcript) || (i === 1 && audioUrl)
                                    ? 'bg-violet-600 text-white'
                                    : 'bg-white/10 text-gray-500'">
                                <template x-if="(i === 0 && transcript) || (i === 1 && audioUrl)">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="!((i === 0 && transcript) || (i === 1 && audioUrl))">
                                    <span x-text="i + 1"></span>
                                </template>
                            </div>
                            <span class="text-xs hidden sm:inline" :class="(i === 0 && transcript) || (i === 1 && audioUrl) ? 'text-gray-300' : 'text-gray-600'" x-text="s"></span>
                            <svg x-show="i < 1" class="w-3 h-3 text-gray-700 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </template>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-5xl mx-auto w-full px-6 py-10 space-y-6">

            {{-- History panel --}}
            <div x-show="historyOpen" x-transition class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Generated Podcasts
                    </h3>
                    <span class="text-xs text-gray-500" x-text="history.length + ' episodes'"></span>
                </div>

                <div x-show="historyLoading" class="p-8 flex justify-center">
                    <svg class="w-5 h-5 text-gray-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>

                <div x-show="!historyLoading && history.length === 0" class="p-8 text-center text-gray-500 text-sm">
                    No podcasts generated yet.
                </div>

                <div x-show="!historyLoading && history.length > 0" class="divide-y divide-white/5 max-h-80 overflow-y-auto">
                    <template x-for="item in history" :key="item.id">
                        <div class="px-6 py-4 flex items-start gap-4 hover:bg-white/2 transition">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-white text-sm truncate" x-text="item.title"></span>
                                    <span class="text-xs text-gray-600 flex-shrink-0" x-text="item.created_at"></span>
                                </div>
                                <a :href="item.product_url" target="_blank"
                                   class="text-xs text-violet-400 hover:text-violet-300 truncate block mb-2 max-w-xs"
                                   x-text="item.product_url"></a>
                                <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600"></div>
                                        <span x-text="item.voice_alex_name"></span>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-gradient-to-br from-violet-500 to-purple-600"></div>
                                        <span x-text="item.voice_sarah_name"></span>
                                    </span>
                                    <span x-text="item.conversation_length + ' exchanges'"></span>
                                    <span x-text="item.dialogue_count + ' lines'"></span>
                                </div>
                            </div>
                            <a :href="item.audio_url" :download="item.title + '.mp3'"
                               class="flex-shrink-0 flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-violet-600/10 border border-violet-500/20 text-violet-300 hover:bg-violet-600/20 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                MP3
                            </a>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Hero --}}
            <div class="text-center" x-show="!transcript">
                <div class="inline-flex items-center gap-2 bg-violet-600/10 border border-violet-500/20 text-violet-300 text-xs font-medium px-3 py-1.5 rounded-full mb-5">
                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-pulse"></span>
                    AI-Powered · Two Hosts · ElevenLabs Audio
                </div>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-white mb-4 leading-tight tracking-tight">
                    Turn any product page<br>into a <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-purple-300">podcast episode</span>
                </h2>
                <p class="text-gray-400 text-lg max-w-xl mx-auto">
                    Generate a transcript, edit it to your liking, then produce studio-quality audio.
                </p>
            </div>

            {{-- ── STEP 1: URL + Settings ── --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm shadow-2xl" x-show="!transcript">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-6 h-6 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">1</div>
                    <h3 class="font-semibold text-white text-sm">Product URL & Settings</h3>
                </div>

                <form @submit.prevent="runGenerateScript">
                    {{-- URL --}}
                    <div class="flex gap-3 mb-5">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <input type="url" x-model="url" placeholder="https://example.com/product/..." required
                                :disabled="scriptLoading"
                                class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition disabled:opacity-50">
                        </div>
                        <button type="submit" :disabled="scriptLoading || !url"
                            class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 disabled:bg-violet-800 disabled:cursor-not-allowed text-white font-semibold text-sm px-5 py-3 rounded-xl transition-all shadow-lg shadow-violet-600/25 whitespace-nowrap">
                            <template x-if="!scriptLoading">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </template>
                            <template x-if="scriptLoading">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </template>
                            <span x-text="scriptLoading
                                ? (images.length || imageUrls.length ? 'Analyzing images…' : 'Generating…')
                                : 'Generate Transcript'"></span>
                        </button>
                    </div>

                    {{-- Additional instructions --}}
                    <div class="mb-5">
                        <label class="text-sm font-medium text-gray-300 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Additional Instructions
                            <span class="text-gray-600 font-normal text-xs">(optional)</span>
                        </label>
                        <textarea
                            x-model="extraInstructions"
                            :disabled="scriptLoading"
                            rows="3"
                            placeholder="e.g. Focus on the battery life and camera quality. Make Alex more skeptical. End with a comparison to competitors. Keep it fun and casual…"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition resize-none disabled:opacity-50 leading-relaxed"
                        ></textarea>
                        <p class="text-xs text-gray-600 mt-1.5">Tell the AI what to emphasise, how to frame the conversation, tone adjustments, or anything else.</p>
                    </div>

                    {{-- Image attachments --}}
                    <div class="mb-5">
                        <label class="text-sm font-medium text-gray-300 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Product Images
                            <span class="text-gray-600 font-normal text-xs">(optional — AI will analyze for richer scripts)</span>
                        </label>

                        {{-- Drop zone --}}
                        <div
                            class="relative border-2 border-dashed rounded-xl transition-all"
                            :class="isDragging
                                ? 'border-violet-500/70 bg-violet-600/10'
                                : 'border-white/10 bg-white/3 hover:border-white/20 hover:bg-white/5'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="isDragging = false; addDroppedItems($event)"
                            @click="$refs.imageFileInput.click()">
                            <input type="file" x-ref="imageFileInput" multiple accept="image/*" class="hidden"
                                @change="addFileInputImages($event)">
                            <div class="flex flex-col items-center justify-center py-6 px-4 pointer-events-none"
                                 x-show="images.length === 0 && imageUrls.length === 0">
                                <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm text-gray-500">Drop images here or <span class="text-violet-400 underline cursor-pointer">browse</span></p>
                                <p class="text-xs text-gray-600 mt-1">JPG, PNG, WebP · max 8 MB each</p>
                            </div>

                            {{-- Thumbnails --}}
                            <div x-show="images.length > 0 || imageUrls.length > 0"
                                 class="p-3 flex flex-wrap gap-2 pointer-events-none">
                                <template x-for="(img, i) in images" :key="'f-' + i">
                                    <div class="relative w-16 h-16 rounded-lg overflow-hidden border border-white/10 bg-black/20 group pointer-events-auto flex-shrink-0">
                                        <img :src="img.preview" class="w-full h-full object-cover">
                                        <button @click.stop="removeImage(i)"
                                            class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <div class="absolute bottom-0 inset-x-0 bg-black/60 text-xs text-center text-gray-300 truncate px-1 py-0.5" x-text="img.name"></div>
                                    </div>
                                </template>
                                <template x-for="(u, i) in imageUrls" :key="'u-' + i">
                                    <div class="relative w-16 h-16 rounded-lg overflow-hidden border border-white/10 bg-black/20 group pointer-events-auto flex-shrink-0">
                                        <img :src="u" class="w-full h-full object-cover" @@error="$el.style.display='none'">
                                        <button @click.stop="removeImageUrl(i)"
                                            class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <div class="absolute bottom-0 inset-x-0 bg-black/60 text-xs text-center text-gray-300 truncate px-1 py-0.5">URL</div>
                                    </div>
                                </template>
                                {{-- Add more --}}
                                <div class="w-16 h-16 rounded-lg border-2 border-dashed border-white/10 hover:border-white/20 flex items-center justify-center pointer-events-auto cursor-pointer transition"
                                     @click.stop="$refs.imageFileInput.click()">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- URL input --}}
                        <div class="flex gap-2 mt-2" @click.stop>
                            <input type="url" x-model="imageUrlInput" placeholder="Or paste an image URL…"
                                class="flex-1 bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition"
                                @keydown.enter.prevent="addImageUrl()">
                            <button type="button" @click="addImageUrl()"
                                :disabled="!imageUrlInput.trim()"
                                class="flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-lg bg-violet-600/15 border border-violet-500/30 text-violet-300 hover:bg-violet-600/25 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                        <p class="text-xs text-gray-600 mt-1.5">Images are analyzed by Gemini Vision and used to add visual context to the script.</p>
                    </div>

                    {{-- Length slider --}}
                    <div class="mb-2">
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
                        <input type="range" min="4" max="22" step="2"
                            x-model.number="conversationLength"
                            :disabled="scriptLoading"
                            class="w-full h-2 rounded-full appearance-none cursor-pointer accent-violet-500 disabled:opacity-50"
                            :style="`background: linear-gradient(to right, #7c3aed 0%, #7c3aed ${((conversationLength - 4) / 18) * 100}%, rgba(255,255,255,0.1) ${((conversationLength - 4) / 18) * 100}%, rgba(255,255,255,0.1) 100%)`">
                        <div class="flex justify-between text-xs text-gray-600 mt-1.5 px-0.5">
                            <span>Short</span><span>Medium</span><span>Long</span>
                        </div>
                    </div>

                    {{-- Error --}}
                    <div x-show="scriptError" x-transition class="mt-3 flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-2.5 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span x-text="scriptError"></span>
                    </div>
                </form>
            </div>

            {{-- ── STEP 2: Transcript (editable) ── --}}
            <div x-show="transcript" x-transition class="bg-white/5 border border-white/10 rounded-2xl backdrop-blur-sm overflow-hidden">

                {{-- Transcript header --}}
                <div class="px-6 py-4 border-b border-white/5 flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="w-6 h-6 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">1</div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-white text-sm truncate" x-text="title"></h3>
                            <p class="text-xs text-gray-500" x-text="transcript?.length + ' exchanges'"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Save as TXT --}}
                        <button @click="saveAsTxt"
                            class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border bg-white/5 border-white/10 text-gray-300 hover:bg-white/10 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Save TXT
                        </button>

                        {{-- Save as JSON --}}
                        <button @click="saveAsJson"
                            class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border bg-white/5 border-white/10 text-gray-300 hover:bg-white/10 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Save JSON
                        </button>

                        {{-- Start over --}}
                        <button @click="resetAll"
                            class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border bg-white/5 border-white/10 text-gray-400 hover:text-white hover:bg-white/10 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Start Over
                        </button>
                    </div>
                </div>

                {{-- Dialogue list --}}
                <div class="divide-y divide-white/5 max-h-[600px] overflow-y-auto mt-4">
                    <template x-for="(turn, index) in (transcript ?? [])" :key="index">
                        <div class="px-6 py-4 flex gap-3 hover:bg-white/2 transition group"
                             :class="turn.speaker === 'Alex' ? 'flex-row' : 'flex-row-reverse'">

                            {{-- Avatar --}}
                            <div class="flex-shrink-0">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shadow-lg"
                                     :class="turn.speaker === 'Alex' ? 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/20' : 'bg-gradient-to-br from-violet-500 to-purple-600 shadow-violet-500/20'">
                                    <span x-text="turn.speaker === 'Alex' ? 'A' : 'S'"></span>
                                </div>
                            </div>

                            {{-- Bubble --}}
                            <div class="flex-1 min-w-0" :class="turn.speaker !== 'Alex' ? 'text-right' : ''">
                                <div class="text-xs font-semibold mb-1.5"
                                     :class="turn.speaker === 'Alex' ? 'text-blue-400' : 'text-violet-400'"
                                     x-text="turn.speaker + ' · ' + voiceName(turn.speaker === 'Alex' ? selectedVoiceAlex : selectedVoiceSarah)">
                                </div>

                                {{-- View mode --}}
                                <div x-show="!editingLines[index]"
                                     class="inline-block text-sm leading-relaxed text-gray-200 rounded-2xl px-4 py-2.5"
                                     :class="turn.speaker === 'Alex' ? 'bg-blue-600/10 border border-blue-600/20 rounded-tl-sm' : 'bg-violet-600/10 border border-violet-600/20 rounded-tr-sm'"
                                     x-html="formatLine(turn.text)">
                                </div>

                                {{-- Edit mode --}}
                                <div x-show="editingLines[index]" class="w-full">
                                    <textarea
                                        x-model="transcript[index].text"
                                        rows="3"
                                        class="w-full bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-gray-200 leading-relaxed resize-none focus:outline-none focus:ring-2 transition"
                                        :class="turn.speaker === 'Alex'
                                            ? 'border-blue-600/30 focus:ring-blue-500/30 focus:border-blue-500/40'
                                            : 'border-violet-600/30 focus:ring-violet-500/30 focus:border-violet-500/40'"
                                        @input="autoResize($event)"
                                        x-effect="if(editingLines[index]) $nextTick(() => { const el = $el; el.style.height='auto'; el.style.height=el.scrollHeight+'px'; el.focus(); })">
                                    </textarea>

                                    {{-- Edit action buttons --}}
                                    <div class="flex items-center gap-2 mt-2" :class="turn.speaker !== 'Alex' ? 'justify-end' : ''">
                                        {{-- Done --}}
                                        <button @click="closeEdit(index)"
                                            class="flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-green-600/15 border border-green-500/30 text-green-400 hover:bg-green-600/25 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Done
                                        </button>
                                        {{-- Swap speaker --}}
                                        <button @click="swapSpeaker(index)"
                                            class="flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                            Swap speaker
                                        </button>
                                        {{-- Delete --}}
                                        <button @click="removeTurn(index)"
                                            class="flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Per-line edit button (shown on hover when not editing) --}}
                            <div x-show="!editingLines[index]"
                                 class="flex-shrink-0 flex items-start pt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEdit(index)"
                                    class="w-7 h-7 rounded-lg bg-white/5 hover:bg-white/15 border border-white/10 text-gray-500 hover:text-gray-200 flex items-center justify-center transition"
                                    title="Edit this line">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Add line --}}
                <div class="px-6 py-3 border-t border-white/5 flex gap-2">
                    <button @click="addTurn('Alex')"
                        class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-blue-600/10 border border-blue-600/20 text-blue-400 hover:bg-blue-600/20 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Alex line
                    </button>
                    <button @click="addTurn('Sarah')"
                        class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-violet-600/10 border border-violet-600/20 text-violet-400 hover:bg-violet-600/20 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Sarah line
                    </button>
                </div>
            </div>

            {{-- ── STEP 2: Voice picker + Generate Audio ── --}}
            <div x-show="transcript" x-transition class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">2</div>
                        Choose Voices & Generate Audio
                    </h3>
                    <span class="text-xs text-gray-500">Pick one voice per host</span>
                </div>

                {{-- Loading voices --}}
                <div x-show="voicesLoading" class="p-8 flex flex-col items-center gap-3 text-gray-500">
                    <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span class="text-sm">Loading voices…</span>
                </div>

                <div x-show="!voicesLoading">
                    {{-- Voice panels --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/5">

                        {{-- Alex --}}
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold">A</div>
                                <div>
                                    <div class="text-sm font-semibold text-white">Alex's Voice</div>
                                    <div class="text-xs text-gray-500" x-text="voiceName(selectedVoiceAlex)"></div>
                                </div>
                            </div>
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <template x-for="voice in voices" :key="'a-' + voice.voice_id">
                                    <div @click="selectedVoiceAlex = voice.voice_id"
                                         class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                         :class="selectedVoiceAlex === voice.voice_id ? 'bg-blue-600/15 border-blue-500/50 ring-1 ring-blue-500/30' : 'bg-white/3 border-white/5 hover:bg-white/8 hover:border-white/15'">
                                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                             :class="selectedVoiceAlex === voice.voice_id ? 'border-blue-500 bg-blue-500' : 'border-gray-600'">
                                            <div x-show="selectedVoiceAlex === voice.voice_id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-white" x-text="voice.name"></div>
                                            <div class="text-xs text-gray-500 truncate" x-text="voice.description || labelsText(voice.labels)"></div>
                                        </div>
                                        <button type="button" x-show="voice.preview_url" @click.stop="previewVoice(voice)"
                                            class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 transition"
                                            :class="previewingId === voice.voice_id ? 'bg-violet-600 text-white' : 'bg-white/10 hover:bg-white/20 text-gray-400 hover:text-white'">
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

                        {{-- Sarah --}}
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-xs font-bold">S</div>
                                <div>
                                    <div class="text-sm font-semibold text-white">Sarah's Voice</div>
                                    <div class="text-xs text-gray-500" x-text="voiceName(selectedVoiceSarah)"></div>
                                </div>
                            </div>
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <template x-for="voice in voices" :key="'s-' + voice.voice_id">
                                    <div @click="selectedVoiceSarah = voice.voice_id"
                                         class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                         :class="selectedVoiceSarah === voice.voice_id ? 'bg-violet-600/15 border-violet-500/50 ring-1 ring-violet-500/30' : 'bg-white/3 border-white/5 hover:bg-white/8 hover:border-white/15'">
                                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                             :class="selectedVoiceSarah === voice.voice_id ? 'border-violet-500 bg-violet-500' : 'border-gray-600'">
                                            <div x-show="selectedVoiceSarah === voice.voice_id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-white" x-text="voice.name"></div>
                                            <div class="text-xs text-gray-500 truncate" x-text="voice.description || labelsText(voice.labels)"></div>
                                        </div>
                                        <button type="button" x-show="voice.preview_url" @click.stop="previewVoice(voice)"
                                            class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 transition"
                                            :class="previewingId === voice.voice_id ? 'bg-violet-600 text-white' : 'bg-white/10 hover:bg-white/20 text-gray-400 hover:text-white'">
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

                    {{-- Same voice warning --}}
                    <div x-show="selectedVoiceAlex && selectedVoiceSarah && selectedVoiceAlex === selectedVoiceSarah"
                         class="mx-5 mb-4 flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs px-4 py-2.5 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Both hosts have the same voice — pick different voices for a better experience.
                    </div>

                    {{-- Generate Audio button --}}
                    <div class="px-5 pb-5">
                        <button @click="runGenerateAudio" :disabled="audioLoading"
                            class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 disabled:bg-violet-800 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl transition-all shadow-lg shadow-violet-600/25">
                            <template x-if="!audioLoading">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2H3v2a9 9 0 0 0 8 8.94V23h2v-2.06A9 9 0 0 0 21 12v-2h-2z"/>
                                </svg>
                            </template>
                            <template x-if="audioLoading">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </template>
                            <span x-text="audioLoading ? 'Generating audio… this may take a minute' : 'Generate Audio'"></span>
                        </button>

                        {{-- Audio error --}}
                        <div x-show="audioError" x-transition class="mt-3 flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-2.5 rounded-lg">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="audioError"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── STEP 3: Audio Player ── --}}
            <div x-show="audioUrl" x-transition class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">
                <div class="p-5 border-b border-white/5 flex items-center gap-2 flex-wrap">
                    <div class="w-6 h-6 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">3</div>
                    <h3 class="font-semibold text-white text-sm" x-text="title"></h3>
                    <div x-show="saved" class="flex items-center gap-1 text-xs text-green-400 bg-green-500/10 border border-green-500/20 px-2 py-0.5 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Saved to history
                    </div>
                    <div class="ml-auto flex items-center gap-2 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600"></div><span x-text="voiceName(selectedVoiceAlex)"></span></span>
                        <span>&amp;</span>
                        <span class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-gradient-to-br from-violet-500 to-purple-600"></div><span x-text="voiceName(selectedVoiceSarah)"></span></span>
                    </div>
                </div>

                <div class="p-6 bg-black/20 space-y-4">

                    {{-- Waveform (click to seek) --}}
                    <div>
                        <div id="waveform" class="w-full rounded-lg overflow-hidden min-h-16 cursor-pointer"></div>
                        <p x-show="wsReady" class="text-center text-xs text-gray-600 mt-1">Click anywhere on the waveform to seek</p>
                    </div>

                    {{-- Scrub slider --}}
                    <div x-show="wsReady" class="space-y-1">
                        <input type="range" min="0" :max="duration || 100" step="0.1"
                            :value="currentTime"
                            @input="scrubTo($event.target.value)"
                            @mousedown="scrubbing = true"
                            @mouseup="scrubbing = false"
                            @touchstart="scrubbing = true"
                            @touchend="scrubbing = false"
                            class="w-full h-1.5 rounded-full appearance-none cursor-pointer accent-violet-500"
                            :style="`background: linear-gradient(to right, #7c3aed 0%, #7c3aed ${duration ? (currentTime/duration)*100 : 0}%, rgba(255,255,255,0.1) ${duration ? (currentTime/duration)*100 : 0}%, rgba(255,255,255,0.1) 100%)`">
                        <div class="flex justify-between text-xs font-mono text-gray-500">
                            <span x-text="formatTime(currentTime)">0:00</span>
                            <span x-text="formatTime(duration)">0:00</span>
                        </div>
                    </div>

                    {{-- Controls row --}}
                    <div class="flex items-center gap-3">

                        {{-- Skip back 10s --}}
                        <button @click="skip(-10)" :disabled="!wsReady"
                            class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 disabled:opacity-30 flex items-center justify-center transition flex-shrink-0"
                            title="Back 10s">
                            <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.99 5V1l-5 5 5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6h-2c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                                <text x="7.5" y="14.5" font-size="5" fill="currentColor" font-family="sans-serif" font-weight="bold">10</text>
                            </svg>
                        </button>

                        {{-- Play / Pause --}}
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

                        {{-- Skip forward 10s --}}
                        <button @click="skip(10)" :disabled="!wsReady"
                            class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 disabled:opacity-30 flex items-center justify-center transition flex-shrink-0"
                            title="Forward 10s">
                            <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.01 5V1l5 5-5 5V7c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6h2c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8z"/>
                                <text x="7.5" y="14.5" font-size="5" fill="currentColor" font-family="sans-serif" font-weight="bold">10</text>
                            </svg>
                        </button>

                        {{-- Playback speed --}}
                        <div class="flex items-center gap-1 ml-1">
                            <template x-for="spd in [0.75, 1, 1.25, 1.5]" :key="spd">
                                <button @click="setSpeed(spd)" :disabled="!wsReady"
                                    class="text-xs px-2 py-1 rounded-md border transition disabled:opacity-30"
                                    :class="playbackSpeed === spd
                                        ? 'bg-violet-600/30 border-violet-500/50 text-violet-300'
                                        : 'bg-white/5 border-white/10 text-gray-500 hover:text-gray-200 hover:bg-white/10'"
                                    x-text="spd + 'x'">
                                </button>
                            </template>
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
                        <a :href="audioUrl" :download="(title ?? 'podcast') + '.mp3'"
                           class="flex items-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 hover:text-white text-xs font-medium px-4 py-2.5 rounded-xl transition flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download MP3
                        </a>
                    </div>
                </div>
            </div>

            {{-- How it works --}}
            <div x-show="!transcript" x-transition class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-600/10 border border-violet-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white text-sm mb-1">1. Generate Transcript</h4>
                    <p class="text-gray-500 text-xs leading-relaxed">Paste a product URL. Groq AI writes the two-host script.</p>
                </div>
                <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-600/10 border border-violet-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white text-sm mb-1">2. Review & Edit</h4>
                    <p class="text-gray-500 text-xs leading-relaxed">Read the transcript, edit any line, swap speakers, add or remove turns.</p>
                </div>
                <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-600/10 border border-violet-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5 text-violet-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2H3v2a9 9 0 0 0 8 8.94V23h2v-2.06A9 9 0 0 0 21 12v-2h-2z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white text-sm mb-1">3. Generate Audio</h4>
                    <p class="text-gray-500 text-xs leading-relaxed">Pick voices, click Generate Audio, then play or download the MP3.</p>
                </div>
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
            // Step 1
            url: '',
            conversationLength: 12,
            extraInstructions: '',
            scriptLoading: false,
            scriptError: null,

            // Images
            images: [],        // { file: File, name: string, preview: string }
            imageUrls: [],     // strings
            imageUrlInput: '',
            isDragging: false,

            // Transcript
            transcript: null,
            title: '',
            editingLines: {}, // { [index]: true } for lines currently being edited

            // Voices
            voices: [],
            voicesLoading: true,
            selectedVoiceAlex:  'JBFqnCBsd6RMkjVDRZzb',
            selectedVoiceSarah: 'EXAVITQu4vr4xnSDxMaL',
            previewingId: null,
            previewAudio: null,

            // Step 2
            audioLoading: false,
            audioError: null,
            audioUrl: null,
            podcastId: null,
            saved: false,

            // History
            history: [],
            historyOpen: false,
            historyLoading: false,

            // WaveSurfer
            ws: null,
            wsReady: false,
            wsLoading: false,
            playing: false,
            currentTime: 0,
            duration: 0,
            scrubbing: false,
            playbackSpeed: 1,

            get lengthLabel() {
                if (this.conversationLength <= 4)  return '~1 min';
                if (this.conversationLength <= 8)  return '~2 min';
                if (this.conversationLength <= 12) return '~3 min';
                if (this.conversationLength <= 16) return '~5 min';
                if (this.conversationLength <= 20) return '~6 min';
                return '~7 min';
            },

            async loadHistory() {
                this.historyLoading = true;
                try {
                    const res = await fetch('/history');
                    this.history = await res.json();
                } catch (e) {
                    console.error('Failed to load history', e);
                } finally {
                    this.historyLoading = false;
                }
            },

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

            // ── Step 1: generate transcript ──
            async runGenerateScript() {
                this.scriptError  = null;
                this.transcript   = null;
                this.audioUrl     = null;
                this.editingLines = {};
                this.scriptLoading = true;

                if (this.ws) { this.ws.destroy(); this.ws = null; }
                if (this.previewAudio) { this.previewAudio.pause(); this.previewAudio = null; this.previewingId = null; }

                try {
                    const fd = new FormData();
                    fd.append('url', this.url);
                    fd.append('conversation_length', this.conversationLength);
                    fd.append('extra_instructions', this.extraInstructions);
                    this.images.forEach(img => fd.append('images[]', img.file));
                    if (this.imageUrls.length) fd.append('image_urls', this.imageUrls.join('\n'));

                    const res = await fetch('/generate-script', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.scriptError = data.error || 'Failed to generate transcript.';
                    } else {
                        this.title      = data.title;
                        this.transcript = data.dialogue;
                    }
                } catch (e) {
                    this.scriptError = 'Network error. Please try again.';
                } finally {
                    this.scriptLoading = false;
                }
            },

            // ── Step 2: generate audio ──
            async runGenerateAudio() {
                this.audioError  = null;
                this.audioUrl    = null;
                this.audioLoading = true;

                if (this.ws) { this.ws.destroy(); this.ws = null; }

                try {
                    const res = await fetch('/generate-audio', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            title:               this.title,
                            product_url:         this.url,
                            conversation_length: this.conversationLength,
                            dialogue:            this.transcript,
                            voice_alex:          this.selectedVoiceAlex,
                            voice_sarah:         this.selectedVoiceSarah,
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.audioError = data.error || 'Audio generation failed.';
                    } else {
                        this.audioUrl  = data.audio_url;
                        this.podcastId = data.podcast_id;
                        this.saved     = true;
                        await this.$nextTick();
                        this.initWaveSurfer(data.audio_url);
                    }
                } catch (e) {
                    this.audioError = 'Network error. Please try again.';
                } finally {
                    this.audioLoading = false;
                }
            },

            // ── Transcript editing ──
            openEdit(index) {
                this.editingLines = { ...this.editingLines, [index]: true };
            },

            closeEdit(index) {
                const lines = { ...this.editingLines };
                delete lines[index];
                this.editingLines = lines;
            },

            swapSpeaker(index) {
                this.transcript[index].speaker = this.transcript[index].speaker === 'Alex' ? 'Sarah' : 'Alex';
            },

            removeTurn(index) {
                this.transcript.splice(index, 1);
                const lines = { ...this.editingLines };
                delete lines[index];
                this.editingLines = lines;
            },

            addTurn(speaker) {
                this.transcript.push({ speaker, text: '' });
                const newIndex = this.transcript.length - 1;
                this.$nextTick(() => { this.openEdit(newIndex); });
            },

            autoResize(e) {
                e.target.style.height = 'auto';
                e.target.style.height = e.target.scrollHeight + 'px';
            },

            // ── Save transcript ──
            saveAsTxt() {
                const lines = this.transcript.map(t => `${t.speaker}: ${t.text}`).join('\n\n');
                const content = `${this.title}\n${'='.repeat(this.title.length)}\n\n${lines}`;
                this.download(content, (this.title || 'transcript') + '.txt', 'text/plain');
            },

            saveAsJson() {
                const content = JSON.stringify({ title: this.title, dialogue: this.transcript }, null, 2);
                this.download(content, (this.title || 'transcript') + '.json', 'application/json');
            },

            download(content, filename, type) {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([content], { type }));
                a.download = filename;
                a.click();
                URL.revokeObjectURL(a.href);
            },

            // ── Image helpers ──
            addDroppedItems(event) {
                const items = [...(event.dataTransfer.items || [])];
                // Check for image URL drop (e.g. dragging an image from browser)
                const urlItem = items.find(i => i.kind === 'string' && i.type === 'text/uri-list');
                if (urlItem) {
                    urlItem.getAsString(url => {
                        url = url.trim();
                        if (url && /^https?:\/\//i.test(url) && !this.imageUrls.includes(url)) {
                            this.imageUrls.push(url);
                        }
                    });
                }
                // Also handle dropped files
                const files = [...(event.dataTransfer.files || [])].filter(f => f.type.startsWith('image/'));
                files.forEach(f => this.images.push({ file: f, name: f.name, preview: URL.createObjectURL(f) }));
            },

            addFileInputImages(event) {
                [...event.target.files].forEach(f => {
                    this.images.push({ file: f, name: f.name, preview: URL.createObjectURL(f) });
                });
                event.target.value = '';
            },

            addImageUrl() {
                const url = this.imageUrlInput.trim();
                if (url && !this.imageUrls.includes(url)) this.imageUrls.push(url);
                this.imageUrlInput = '';
            },

            removeImage(index) {
                URL.revokeObjectURL(this.images[index].preview);
                this.images.splice(index, 1);
            },

            removeImageUrl(index) {
                this.imageUrls.splice(index, 1);
            },

            // ── Voice helpers ──
            voiceName(voiceId) {
                const v = this.voices.find(v => v.voice_id === voiceId);
                return v ? v.name : '';
            },

            labelsText(labels) {
                if (!labels || typeof labels !== 'object') return '';
                return Object.values(labels).filter(Boolean).join(' · ');
            },

            previewVoice(voice) {
                if (this.previewAudio) { this.previewAudio.pause(); this.previewAudio = null; }
                if (this.previewingId === voice.voice_id) { this.previewingId = null; return; }
                if (!voice.preview_url) return;
                this.previewingId = voice.voice_id;
                this.previewAudio = new Audio(voice.preview_url);
                this.previewAudio.play();
                this.previewAudio.onended = () => { this.previewingId = null; };
                this.previewAudio.onerror = () => { this.previewingId = null; };
            },

            // ── WaveSurfer ──
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
                this.ws.on('ready', () => { this.wsLoading = false; this.wsReady = true; this.duration = this.ws.getDuration(); });
                this.ws.on('timeupdate', t => { if (!this.scrubbing) this.currentTime = t; });
                this.ws.on('finish', () => { this.playing = false; });
                this.ws.on('error', err => { console.error('WaveSurfer:', err); this.wsLoading = false; });
            },

            togglePlay() {
                if (!this.ws || !this.wsReady) return;
                this.ws.playPause();
                this.playing = this.ws.isPlaying();
            },

            skip(seconds) {
                if (!this.ws || !this.wsReady) return;
                const next = Math.min(Math.max(this.currentTime + seconds, 0), this.duration);
                this.ws.seekTo(next / this.duration);
            },

            scrubTo(value) {
                if (!this.ws || !this.wsReady || !this.duration) return;
                this.ws.seekTo(parseFloat(value) / this.duration);
            },

            setSpeed(speed) {
                if (!this.ws) return;
                this.playbackSpeed = speed;
                this.ws.setPlaybackRate(speed);
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

            resetAll() {
                if (this.ws) { this.ws.destroy(); this.ws = null; }
                if (this.previewAudio) { this.previewAudio.pause(); this.previewAudio = null; }
                this.images.forEach(img => URL.revokeObjectURL(img.preview));
                Object.assign(this, {
                    url: '', extraInstructions: '', transcript: null, title: '', editingLines: {},
                    scriptError: null, scriptLoading: false,
                    images: [], imageUrls: [], imageUrlInput: '', isDragging: false,
                    audioUrl: null, audioError: null, audioLoading: false, podcastId: null, saved: false,
                    playing: false, wsReady: false, wsLoading: false, currentTime: 0, duration: 0, scrubbing: false, playbackSpeed: 1,
                    previewingId: null,
                });
            },
        };
    }
    </script>

</body>
</html>
