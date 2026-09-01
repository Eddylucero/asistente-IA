@props([
    'header' => 'Comenzar',
    'subheader' => 'Crea tu santuario digital personal.',
    'wide' => false,
])
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
</head>
<body class="bg-surface font-body-md text-on-surface min-h-screen flex flex-col overflow-x-hidden">
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary-fixed-dim/20 rounded-full blur-[100px]"></div>
        <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] bg-secondary-fixed/20 rounded-full blur-[120px]"></div>
    </div>

    <main class="flex-1 w-full flex flex-col relative z-10">
        <div class="flex-1 flex flex-col lg:flex-row justify-center lg:justify-start {{ $wide ? 'max-w-[1320px]' : 'max-w-container-max' }} mx-auto w-full px-margin-mobile lg:px-margin-desktop py-stack-md lg:py-stack-lg gap-stack-md lg:gap-stack-lg items-center">
            <div class="w-full {{ $wide ? 'lg:w-[58%]' : 'lg:w-1/2' }} flex flex-col justify-center relative">
                <div class="bg-surface-container-lowest p-8 lg:p-10 rounded-[32px] shadow-[0_20px_40px_rgba(0,0,0,0.03)] relative z-10 w-full {{ $wide ? 'max-w-2xl' : 'max-w-lg' }} mx-auto lg:mx-0">
                    <div class="space-y-1 mb-6">
                        <h1 class="font-display-lg text-headline-lg lg:text-display-lg text-on-surface">{{ $header }}</h1>
                        <p class="font-body-lg text-body-md lg:text-body-lg text-on-surface-variant">{{ $subheader }}</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>

            <div class="hidden lg:flex {{ $wide ? 'lg:w-[42%]' : 'lg:w-1/2' }} h-full items-center justify-center relative">
                <div class="relative w-full max-w-lg aspect-square">
                    <div class="absolute inset-0 bg-gradient-to-br from-tertiary-fixed/30 to-secondary-fixed/30 rounded-full blur-3xl animate-pulse" style="animation-duration: 8s;"></div>
                    <img alt="Avatar asistente" class="w-full h-full object-cover rounded-[48px] shadow-[0_30px_60px_rgba(0,0,0,0.08)] relative z-10 transition-transform duration-700 hover:scale-[1.02]" src="{{ config('brand.auth_avatar') }}"/>
                    <div class="absolute -left-12 top-1/4 bg-surface-container-lowest/90 backdrop-blur-md p-4 rounded-2xl shadow-xl z-20 flex items-center gap-3 animate-[float_4s_ease-in-out_infinite]">
                        <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container">
                            <span class="material-symbols-outlined text-xl">psychology</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface">Apoyo experto</p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">24/7 disponible</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <x-site-footer class="py-2 relative z-10" />

    @include('partials.swal-flash')
</body>
</html>
