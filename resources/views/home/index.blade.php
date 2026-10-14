<x-main-layout title="Inicio">
    <div class="flex flex-col w-full flex-1 relative overflow-hidden">
        <div class="absolute inset-0 z-0 pointer-events-none opacity-40">
            <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-gradient-to-br from-primary-fixed/20 to-transparent blur-3xl"></div>
            <div class="absolute bottom-[-15%] left-[-10%] w-[800px] h-[800px] rounded-full bg-gradient-to-tr from-surface-container-high/30 to-transparent blur-3xl"></div>
        </div>

        <div class="flex-1 flex flex-col justify-center items-center px-gutter py-stack-lg max-w-container-max mx-auto w-full relative z-10 lg:flex-row lg:justify-between">
            <div class="flex flex-col items-center lg:items-start text-center lg:text-left max-w-2xl lg:w-1/2 lg:pr-gutter z-20">
                <div class="mb-stack-sm inline-flex items-center gap-unit bg-surface-container-high/50 px-4 py-2 rounded-full backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    <span class="font-label-md text-label-md text-on-surface-variant tracking-wider uppercase">Tu Espacio Seguro</span>
                </div>

                <h1 class="font-display-lg text-headline-lg lg:text-display-lg text-on-background mb-stack-md leading-tight">
                    Hola, estoy aquí<br class="hidden lg:block"/> para escucharte.
                </h1>

                <p class="font-body-lg text-body-lg text-on-surface-variant mb-stack-lg max-w-md leading-relaxed">
                    Tómate un momento, respira profundo. Este es un lugar sin juicios, diseñado para tu bienestar emocional y tranquilidad mental.
                </p>

                <a
                    class="group relative inline-flex items-center justify-center gap-3 bg-primary text-on-primary px-8 py-4 rounded-full font-label-md text-label-md transition-all duration-300 hover:bg-primary-container hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/20"
                    href="{{ route('conversations.index') }}"
                    data-new-conversation
                    data-continue-url="{{ route('conversations.index') }}"
                    data-create-url="{{ route('conversations.create') }}"
                    data-confirm-title="¿Qué quieres hacer?"
                    data-confirm-text="Puedes continuar con la conversación anterior o crear una nueva desde cero."
                    data-continue-label="Continuar con la anterior"
                    data-create-label="Crear una nueva"
                >
                    <span>Comenzar conversación</span>
                    <span class="material-symbols-outlined transition-transform duration-300 group-hover:translate-x-1">arrow_forward</span>
                </a>

                <div class="mt-stack-lg pt-stack-md border-t border-outline-variant/30 flex items-center justify-center lg:justify-start gap-stack-md w-full opacity-80">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-outline">lock</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">100% Privado</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-outline">schedule</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Disponible 24/7</span>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex lg:w-1/2 justify-center lg:justify-end items-center relative h-[400px] lg:h-[600px] mt-stack-lg lg:mt-0 z-10 w-full">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                    <div class="w-[300px] h-[300px] lg:w-[450px] lg:h-[450px] rounded-full bg-primary/10 blur-3xl"></div>
                    <div class="absolute bottom-8 right-0 w-[200px] h-[200px] lg:w-[300px] lg:h-[300px] rounded-full bg-secondary-container/30 blur-3xl"></div>
                </div>

                <div class="relative w-[320px] h-[320px] lg:w-[480px] lg:h-[480px] z-20 transition-transform duration-300 ease-out" data-parallax="40">
                    <div class="group w-full h-full relative transition-transform duration-calm ease-calm motion-safe:hover:-translate-y-1 motion-reduce:transition-none">
                        <div class="absolute top-8 -right-3 text-on-surface p-3 rounded-2xl shadow-lg shadow-on-background/10 ring-1 ring-outline-variant/30 flex items-center justify-center backdrop-blur-md bg-surface/95 z-30" aria-hidden="true">
                            <span class="material-symbols-outlined text-tertiary-container" style="font-variation-settings: 'FILL' 1;">spa</span>
                        </div>
                        <div class="absolute bottom-12 -left-3 text-on-surface p-3 rounded-2xl shadow-lg shadow-on-background/10 ring-1 ring-outline-variant/30 flex items-center justify-center backdrop-blur-md bg-surface/95 z-30" aria-hidden="true">
                            <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">favorite</span>
                        </div>

                        <img
                            alt="Avatar del asistente virtual Lumi"
                            class="relative h-full w-full object-contain drop-shadow-[0_18px_24px_rgba(0,0,0,0.12)] dark:hidden"
                            src="{{ asset('images/mente/inicio.png') }}"
                            decoding="async"
                        />
                        <img
                            alt="Avatar del asistente virtual Lumi"
                            class="relative hidden h-full w-full object-contain drop-shadow-[0_18px_24px_rgba(0,0,0,0.12)] dark:block"
                            src="{{ asset('images/mente/inicio-bn.png') }}"
                            decoding="async"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-main-layout>
