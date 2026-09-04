<x-panel-layout title="Conversación">
    <div
        class="flex flex-col w-full h-full overflow-hidden"
        data-chat
        data-endpoint="{{ route('conversations.messages.store') }}"
        data-conversation="{{ $conversation?->id ?? '' }}"
        data-start-new-conversation="{{ $conversation ? 'false' : 'true' }}"
        data-initials="{{ auth()->user()->initials }}"
    >
        <div class="flex flex-1 h-full max-w-[1400px] mx-auto w-full gap-stack-lg p-stack-md lg:p-stack-lg min-h-0">

            <div class="hidden lg:flex w-[400px] flex-col rounded-3xl bg-surface-container-low shadow-sm relative overflow-hidden flex-shrink-0 group">
                <div class="absolute inset-0 bg-gradient-to-tr from-primary/10 via-transparent to-tertiary-fixed-dim/10 opacity-50 mix-blend-multiply z-10 pointer-events-none"></div>

                <img alt="Asistente Mente escuchando" class="w-full h-full object-cover transition-transform duration-[20s] group-hover:scale-105 ease-out" src="{{ config('brand.avatar') }}"/>

                <div class="absolute bottom-stack-md left-stack-md right-stack-md bg-surface/90 backdrop-blur-md rounded-xl p-stack-sm flex items-center gap-stack-sm shadow-md z-20">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-container text-on-primary-container shrink-0">
                        <span class="material-symbols-outlined text-[20px] animate-pulse">mic</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-label-md font-label-md text-on-surface">Mente te escucha...</span>
                        <div class="flex gap-1 items-center h-4 mt-1">
                            <div class="w-1 bg-primary/40 rounded-full h-2 animate-[bounce_1s_infinite_0ms]"></div>
                            <div class="w-1 bg-primary/60 rounded-full h-3 animate-[bounce_1s_infinite_200ms]"></div>
                            <div class="w-1 bg-primary/80 rounded-full h-4 animate-[bounce_1s_infinite_400ms]"></div>
                            <div class="w-1 bg-primary/60 rounded-full h-3 animate-[bounce_1s_infinite_600ms]"></div>
                            <div class="w-1 bg-primary/40 rounded-full h-2 animate-[bounce_1s_infinite_800ms]"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 flex-col h-full min-h-0 bg-surface rounded-3xl shadow-sm relative overflow-hidden">

                <div class="px-stack-sm sm:px-stack-md py-stack-sm bg-surface/90 backdrop-blur-sm z-10 flex items-center justify-between shrink-0 border-b border-outline-variant/10">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <x-drawer-toggle class="-ml-2" />

                        <div class="lg:hidden w-10 h-10 rounded-full overflow-hidden shrink-0">
                            <img alt="Mente" class="w-full h-full object-cover" src="{{ config('brand.avatar') }}"/>
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

                <div class="flex-1 min-h-0 overflow-y-auto p-stack-md flex flex-col gap-stack-md scroll-smooth no-scrollbar" data-chat-messages>
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
                    <div class="flex flex-wrap gap-2 pb-stack-sm mb-2" data-chat-suggestions>
                        <button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container hover:bg-surface-container-highest text-on-surface text-label-md font-label-md transition-colors shadow-sm" type="button" data-chat-suggestion-toggle data-expanded="false">
                            Sugerencias
                        </button>
                        <a class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container hover:bg-surface-container-highest text-on-surface text-label-md font-label-md transition-colors shadow-sm flex items-center gap-1" href="{{ route('voice.index') }}">
                            <span class="material-symbols-outlined text-[16px]">mic</span> Prefiero hablarlo
                        </a>
                    </div>

                    <div class="hidden flex-wrap gap-2 pb-stack-sm no-scrollbar mb-2" data-chat-suggestion-list>
                        @foreach ($suggestions as $suggestion)
                            <button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container hover:bg-surface-container-highest text-on-surface text-label-md font-label-md transition-colors shadow-sm" type="button" data-chat-suggestion>
                                {{ $suggestion }}
                            </button>
                        @endforeach
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
                            <a class="w-12 h-12 flex items-center justify-center rounded-full text-primary hover:bg-primary/10 transition-colors" href="{{ route('voice.index') }}" aria-label="Hablar">
                                <span class="material-symbols-outlined">mic</span>
                            </a>
                            <button class="w-12 h-12 flex items-center justify-center rounded-full bg-primary text-on-primary shadow-sm hover:opacity-90 transition-opacity disabled:opacity-50" type="button" aria-label="Enviar" data-chat-send>
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">send</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-2">
                        <span class="text-label-sm font-label-sm text-outline">Mente puede cometer errores. Considera verificar información importante.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-panel-layout>
