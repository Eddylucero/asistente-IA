@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head', ['title' => $title])
    <style>
        html, body { margin: 0; padding: 0; }
        body { overscroll-behavior: none; }
        main > :first-child { margin-top: 0 !important; }
        main > :last-child { margin-bottom: 0 !important; }
        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-background font-body-md text-on-background min-h-screen flex flex-col">
    <header class="fixed top-0 w-full z-50 bg-surface-container-lowest/80 backdrop-blur-xl shadow-[0_10px_30px_rgba(0,0,0,0.04)] pt-safe">
        <div class="h-20 max-w-container-max mx-auto px-margin-mobile lg:px-margin-desktop flex items-center justify-between">
            <a class="flex items-center gap-3" href="{{ route('home') }}">
                <img alt="Mente Logo" class="h-8 w-auto object-contain" src="{{ config('brand.logo') }}"/>
                <span class="font-headline-md text-headline-md text-primary tracking-tight">Mente</span>
            </a>

            <div class="flex items-center gap-2">
                <x-theme-toggle />

                @auth
                    <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                        @csrf
                        <button class="inline-flex items-center gap-2 pl-4 pr-5 py-2.5 rounded-full bg-surface-container/70 hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface font-label-md text-label-md transition-colors shadow-sm" type="submit">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                            Salir
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main class="w-full pt-20 flex-1 flex flex-col">
        {{ $slot }}
    </main>

    <x-site-footer class="py-3 pb-safe" />

    @include('partials.swal-flash')
</body>
</html>
