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

                <a class="group relative inline-flex items-center justify-center gap-3 bg-primary text-on-primary px-8 py-4 rounded-full font-label-md text-label-md transition-all duration-300 hover:bg-primary-container hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/20" href="{{ route('conversations.index') }}">
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
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-[300px] h-[300px] lg:w-[450px] lg:h-[450px] rounded-full bg-surface-container/50 blur-2xl animate-[spin_20s_linear_infinite]"></div>
                    <div class="absolute w-[350px] h-[350px] lg:w-[500px] lg:h-[500px] rounded-full border border-primary-fixed-dim/20 scale-90 animate-[ping_3s_cubic-bezier(0,0,0.2,1)_infinite]"></div>
                </div>

                <div class="relative w-[320px] h-[320px] lg:w-[480px] lg:h-[480px] z-20 transition-transform duration-300 ease-out" data-parallax="40">
                    <div class="w-full h-full relative transition-transform duration-700 hover:scale-105">
                        <div class="absolute top-10 right-10 text-on-surface p-3 rounded-2xl shadow-lg shadow-on-background/5 animate-[bounce_4s_infinite] flex items-center gap-2 backdrop-blur-md bg-surface/90 z-30">
                            <span class="material-symbols-outlined text-tertiary-container" style="font-variation-settings: 'FILL' 1;">spa</span>
                        </div>
                        <div class="absolute bottom-16 left-4 text-on-surface p-3 rounded-2xl shadow-lg shadow-on-background/5 animate-[bounce_5s_infinite_0.5s] flex items-center gap-2 backdrop-blur-md bg-surface/90 z-30">
                            <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">favorite</span>
                        </div>

                        <img
                            alt="Avatar del asistente virtual Mente"
                            class="w-full h-full object-contain drop-shadow-2xl z-20 relative"
                            src="{{ config('brand.home_avatar') }}"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-main-layout>
