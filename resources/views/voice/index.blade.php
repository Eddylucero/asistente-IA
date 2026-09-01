<x-panel-layout title="Voz">
    <div
        class="relative flex h-full min-h-0 flex-col overflow-hidden bg-background"
        data-voice
        data-endpoint="{{ route('voice.messages.store') }}"
    >
        <div class="absolute inset-0 z-0 pointer-events-none opacity-40">
            <div class="absolute left-1/2 top-1/4 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-primary-fixed/20 blur-3xl"></div>
            <div class="absolute left-1/4 top-0 h-[360px] w-[360px] rounded-full bg-secondary-fixed/20 blur-3xl"></div>
        </div>

        <header class="relative z-10 mx-auto flex w-full max-w-container-max shrink-0 items-start justify-between p-stack-sm sm:p-stack-md lg:p-stack-lg">
            <div class="flex items-start gap-2 sm:gap-3">
                <x-drawer-toggle class="mt-1 -ml-2" />

                <x-voice-identity class="hidden sm:flex" />
            </div>
            <div class="flex items-center gap-1">
                <x-panel-actions />

                <a class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high text-on-surface shadow-sm hover:bg-primary-container hover:text-on-primary-container hover:scale-105 hover:shadow-md active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 transition-all duration-soft ease-calm motion-reduce:transition-none motion-reduce:hover:scale-100" href="{{ route('conversations.index') }}" aria-label="Volver a conversación">
                    <span class="material-symbols-outlined">forum</span>
                </a>
            </div>
        </header>

        <main class="relative z-10 flex min-h-0 flex-1 flex-col items-center justify-center gap-4 px-stack-md sm:gap-0">
            <x-voice-identity align="center" class="shrink-0 sm:hidden" />

            <div class="relative flex h-full max-h-[520px] w-full items-center justify-center">
                <div class="pointer-events-none absolute aspect-square w-[min(80vw,38vh,600px)]" data-voice-waves>
                    <div class="absolute inset-0 animate-[ping_3s_cubic-bezier(0,0,0.2,1)_infinite] rounded-full border border-primary-fixed/40"></div>
                    <div class="absolute inset-[10%] animate-[ping_4s_cubic-bezier(0,0,0.2,1)_infinite] rounded-full border border-secondary-fixed/40" style="animation-delay: 0.5s"></div>
                    <div class="absolute inset-[20%] animate-[ping_5s_cubic-bezier(0,0,0.2,1)_infinite] rounded-full border border-tertiary-fixed/40" style="animation-delay: 1s"></div>
                </div>

                <div class="relative h-[min(14rem,32vh)] w-[min(14rem,32vh)] rounded-full bg-surface-container-lowest p-3 shadow-2xl md:h-[min(18rem,40vh)] md:w-[min(18rem,40vh)] md:p-4">
                    <img alt="Dra. Mente, asistente virtual" class="h-full w-full rounded-full object-cover shadow-inner" src="{{ config('brand.voice_avatar') }}" />
                    <div class="pointer-events-none absolute inset-0 animate-[spin_20s_linear_infinite] rounded-full border-2 border-dashed border-primary/40"></div>
                </div>
            </div>
        </main>

        <footer class="relative z-20 mx-auto flex w-full max-w-container-max shrink-0 flex-col items-center gap-unit sm:gap-stack-sm p-stack-sm sm:p-stack-md lg:p-stack-lg pb-safe">
            <div class="flex min-h-10 sm:min-h-12 max-w-xl items-center justify-center rounded-full bg-surface-container/70 px-stack-md py-unit sm:py-stack-sm text-center shadow-sm backdrop-blur-md">
                <p class="font-body-md text-label-md sm:text-body-md italic text-on-surface-variant" data-voice-transcript>Pulsa el micrófono y cuéntame cómo te sientes.</p>
            </div>

            <div class="flex items-center gap-2 rounded-[2rem] bg-surface-container-lowest p-stack-sm shadow-xl md:gap-stack-md">
                <a class="group relative flex h-12 w-12 items-center justify-center rounded-full text-on-surface hover:bg-surface-container-high hover:text-primary hover:scale-110 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 transition-all duration-soft ease-calm motion-reduce:transition-none motion-reduce:hover:scale-100 md:h-14 md:w-14" href="{{ route('conversations.index') }}" aria-label="Volver a conversación">
                    <span class="material-symbols-outlined">chat</span>
                    <span class="absolute -top-10 whitespace-nowrap rounded bg-inverse-surface px-3 py-1 font-label-sm text-label-sm text-inverse-on-surface opacity-0 transition-opacity group-hover:opacity-100">Texto</span>
                </a>
                <button class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-on-primary shadow-md hover:scale-105 hover:shadow-lg hover:bg-primary-container active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 transition-all duration-soft ease-calm motion-reduce:transition-none motion-reduce:hover:scale-100 md:h-16 md:w-16" type="button" aria-label="Comenzar a escuchar" data-voice-button>
                    <span class="material-symbols-outlined text-[30px] md:text-[36px]" style="font-variation-settings: 'FILL' 1" data-voice-icon>mic</span>
                </button>
            </div>

            <span class="hidden [@media(min-height:640px)]:block text-center font-label-sm text-label-sm text-outline">La transcripción se guardará en tu conversación.</span>
        </footer>
    </div>
</x-panel-layout>
