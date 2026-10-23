<x-panel-layout title="Conversación">
    <div
        class="flex flex-col w-full h-full overflow-hidden"
        data-chat
        data-endpoint="{{ route('conversations.messages.store') }}"
        data-conversation="{{ $conversation?->id ?? '' }}"
        data-start-new-conversation="{{ $conversation ? 'false' : 'true' }}"
        data-initials="{{ auth()->user()->initials }}"
        data-tts-voice="{{ auth()->user()->tts_voice ?: app(\App\Services\LocalSpeech::class)->defaultVoice() }}"
        data-speech-endpoint="{{ route('speech.store') }}"
        data-voice-endpoint="{{ route('speech.preference') }}"
    >
        <div class="flex flex-1 h-full max-w-[1400px] mx-auto w-full gap-stack-lg p-stack-md lg:p-stack-lg min-h-0">

            <div class="hidden lg:flex w-[400px] min-h-0 flex-col rounded-3xl bg-[#ECECE5] shadow-sm relative overflow-hidden flex-shrink-0">
                <div class="relative flex-1 min-h-0 overflow-hidden rounded-t-3xl" data-chat-greeting-home>
                    <x-chat-avatar class="absolute inset-0 w-full h-full" />
                </div>

                <div class="relative mx-stack-md mb-stack-md shrink-0 bg-surface/90 backdrop-blur-md rounded-xl p-stack-sm flex items-center gap-stack-sm shadow-md z-20">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-container text-on-primary-container shrink-0">
                        <span class="material-symbols-outlined text-[20px]">volume_up</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-label-md font-label-md text-on-surface" data-chat-speech-status aria-live="polite">Lista para conversar</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 flex-col h-full min-h-0 bg-surface rounded-3xl shadow-sm relative overflow-hidden">

                <div class="px-stack-sm sm:px-stack-md py-stack-sm bg-surface/90 backdrop-blur-sm z-10 flex items-center justify-between shrink-0 border-b border-outline-variant/10">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <x-drawer-toggle class="-ml-2" />

                        <div class="relative lg:hidden w-10 h-10 rounded-full overflow-hidden shrink-0 bg-[#ECECE5]" data-chat-greeting-home>
                            <x-chat-avatar class="absolute inset-0 w-full h-full" fit="object-cover" />
                        </div>
                        <div>
                            <h2 class="text-lg font-headline-md text-on-surface" data-chat-title>{{ $conversation?->title ?: 'Sesión Actual' }}</h2>
                            <span class="text-label-sm font-label-sm text-primary flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-secondary-fixed"></span> En línea
                            </span>
                        </div>
                    </div>
                    <x-panel-actions />
                </div>

                <div class="flex-1 min-h-0 overflow-y-auto p-stack-md flex flex-col gap-stack-md scroll-smooth no-scrollbar focus:outline-none" data-chat-messages tabindex="-1">
                    @php
                        $messages = $messages->sortBy('created_at');
                        $lastDateKey = null;
                    @endphp

                    @foreach ($messages as $message)
                        @php
                            $isUser = $message->role === 'user';
                            $dateKey = $message->created_at->toDateString();
                            $dateLabel = $message->created_at->isToday()
                                ? 'Hoy'
                                : ($message->created_at->isYesterday() ? 'Ayer' : $message->created_at->translatedFormat('d/m/Y'));
                        @endphp

                        @if ($loop->first || $dateKey !== $lastDateKey)
                            @php $lastDateKey = $dateKey; @endphp
                            <div class="flex justify-center my-stack-sm">
                                <span class="text-label-sm font-label-sm text-outline px-3 py-1 bg-surface-container-lowest rounded-full shadow-sm">{{ $dateLabel }}</span>
                            </div>
                        @endif

                        <div class="flex gap-3 max-w-[85%] {{ $isUser ? 'self-end flex-row-reverse' : 'self-start' }}">
                            <div class="hidden md:flex w-8 h-8 rounded-full {{ $isUser ? 'bg-secondary-container text-on-secondary-container text-label-sm font-label-sm' : 'bg-primary-container text-on-primary-container' }} items-center justify-center shrink-0 mt-1">
                                @if ($isUser)
                                    {{ auth()->user()->initials }}
                                @else
                                    <span class="material-symbols-outlined text-[18px]">psychiatry</span>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1 {{ $isUser ? 'items-end' : '' }}">
                                <div class="{{ $isUser ? 'bg-primary text-on-primary rounded-tr-sm shadow-md shadow-primary/20' : 'bg-surface-container text-on-surface rounded-tl-sm shadow-sm' }} p-4 rounded-2xl" data-message-role="{{ $message->role }}">
                                    <div class="message-content text-body-md font-body-md">{{ $message->content }}</div>
                                </div>
                                @if ($message->role === 'assistant')
                                    <button class="self-start inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-label-sm font-label-sm text-primary hover:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 transition-colors disabled:opacity-50" type="button" data-chat-speech-replay>
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">volume_up</span>
                                        Escuchar respuesta
                                    </button>
                                @endif
                                <span class="text-label-sm font-label-sm text-on-surface-variant {{ $isUser ? 'mr-1' : 'ml-1' }}">{{ $message->created_at->format('g:i A') }}</span>
                            </div>
                        </div>
                    @endforeach

                    @if ($messages->isEmpty())
                        <div class="flex flex-1 items-center justify-center text-center px-stack-md">
                            <p class="text-body-md font-body-md text-on-surface-variant">Comienza escribiendo cómo te sientes.</p>
                        </div>
                    @endif
                </div>

                <div class="p-stack-md bg-surface-container-lowest shrink-0">
                    <div class="flex flex-wrap items-center gap-2 mb-3 text-label-sm font-label-sm text-on-surface-variant">
                        <span class="lg:hidden" data-chat-speech-status aria-live="polite">Lista para conversar</span>
                    </div>

                    <div class="relative bg-surface rounded-[24px] shadow-sm flex items-end min-h-[52px] p-1.5 ring-1 ring-outline-variant/30 focus-within:ring-2 focus-within:ring-primary focus-within:shadow-md transition-all sm:min-h-[64px] sm:p-2">
                        <label class="sr-only" for="chat-input">Escribe tu mensaje</label>
                        <textarea
                            class="flex-1 min-w-0 bg-transparent border-none outline-none resize-none max-h-[150px] min-h-[36px] py-2 px-3 text-body-md font-body-md leading-[1.4] text-on-surface placeholder:text-on-surface/60 placeholder:text-sm sm:placeholder:text-base sm:py-3 sm:px-4 focus:ring-0"
                            id="chat-input"
                            placeholder="Escribe tu mensaje..."
                            rows="1"
                            data-chat-input></textarea>

                        <div class="flex items-center gap-1 shrink-0 ml-2">
                            <a class="w-12 h-12 flex items-center justify-center rounded-full text-primary hover:bg-primary/10 transition-colors" href="{{ route('voice.index', $conversation ? ['conversation' => $conversation->id] : ['new' => 1]) }}" aria-label="Hablar">
                                <span class="material-symbols-outlined">mic</span>
                            </a>
                            <button class="w-12 h-12 flex items-center justify-center rounded-full bg-primary text-on-primary shadow-sm hover:opacity-90 transition-opacity disabled:opacity-50" type="button" aria-label="Enviar" data-chat-send>
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">send</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-2">
                        <span class="text-label-sm font-label-sm text-outline">Lumi puede cometer errores. Considera verificar información importante.</span>
                    </div>
                </div>
            </div>
        </div>

        <dialog class="chat-greeting-dialog" data-chat-greeting-dialog aria-labelledby="chat-greeting-title">
            <div class="chat-greeting-scrim" data-chat-greeting-scrim aria-hidden="true"></div>
            <div class="chat-greeting-stage" data-chat-greeting-stage>
                <video data-chat-greeting-video src="{{ asset('images/mente/saludo-move.mp4') }}" muted loop playsinline preload="auto" aria-hidden="true"></video>
                <div class="chat-greeting-copy" data-chat-greeting-copy>
                    <h2 class="text-headline-sm font-headline-sm" id="chat-greeting-title">¡Hola! Soy Lumi</h2>
                    <p class="mt-1 text-body-md">Estoy aquí para acompañarte.</p>
                </div>
            </div>
        </dialog>

        <dialog class="chat-speech-dialog" data-chat-speech-dialog aria-labelledby="chat-speech-title" aria-describedby="chat-speech-description">
            <div class="relative flex flex-col items-center p-5 sm:p-7">
                <button class="absolute top-3 right-3 z-10 w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest transition-colors" type="button" data-chat-prefer-reading aria-label="Cerrar y leer la respuesta">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
                <span class="px-3 py-1 rounded-full bg-primary/10 text-primary text-label-md font-label-md">Un momento contigo</span>
                <x-chat-avatar class="chat-speech-avatar w-full mt-3" />
                <h2 class="text-headline-sm font-headline-sm text-on-surface mt-2 text-center" id="chat-speech-title">Lumi está hablando contigo</h2>
                <p class="text-body-md text-on-surface-variant mt-2 text-center" id="chat-speech-description">Escucha con calma. También puedes leer la respuesta en el chat.</p>
                <p class="text-label-sm text-on-surface-variant mt-2 text-center" data-chat-video-error hidden>No se pudo cargar la animación. Puedes seguir escuchando la respuesta.</p>
                <button class="mt-5 px-6 py-3 rounded-full bg-primary text-on-primary font-label-md shadow-sm hover:opacity-90 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/30 transition-all" type="button" data-chat-prefer-reading autofocus>Mejor leer</button>
            </div>
        </dialog>
    </div>
</x-panel-layout>
