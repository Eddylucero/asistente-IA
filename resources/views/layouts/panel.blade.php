@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head', ['title' => $title])
    <style>
        html, body { margin: 0; padding: 0; }
        body { overscroll-behavior: none; }
        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-background font-body-md text-on-surface">
    @php
        $user = auth()->user();

        $navBase = 'group flex items-center gap-3 h-12 px-stack-md rounded-xl font-label-md text-label-md transition-all duration-soft ease-calm motion-reduce:transition-none';
        $navIdle = $navBase.' text-on-surface-variant hover:bg-surface-container-highest/60 hover:text-on-surface hover:translate-x-1 motion-reduce:hover:translate-x-0';
        $navActive = $navBase.' bg-primary/10 text-primary font-semibold hover:bg-primary/15';
        $navIcon = 'material-symbols-outlined text-[20px] transition-transform duration-soft ease-calm group-hover:scale-110 motion-reduce:transition-none';

        $sections = [
            ['route' => 'home', 'label' => 'Inicio', 'icon' => 'home', 'active' => ['home']],
            ['route' => 'conversations.index', 'label' => 'Conversación', 'icon' => 'forum', 'active' => ['conversations.index', 'conversations.show', 'conversations.create']],
            ['route' => 'conversations.history', 'label' => 'Historial', 'icon' => 'history', 'active' => ['conversations.history']],
            ['route' => 'profile.edit', 'label' => 'Perfil', 'icon' => 'person', 'active' => ['profile.edit']],
        ];
    @endphp

    <div
        class="fixed inset-0 z-40 bg-on-surface/25 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-calm ease-calm lg:hidden motion-reduce:transition-none"
        data-drawer-scrim
        aria-hidden="true"
    ></div>

    <aside
        class="fixed left-0 top-0 bottom-0 w-64 bg-surface-container/55 backdrop-blur-3xl border-r border-outline-variant/15 flex flex-col z-50 -translate-x-full lg:translate-x-0 transition-transform duration-calm ease-calm motion-reduce:transition-none"
        data-drawer
        id="panel-drawer"
        aria-label="Menú principal"
    >
        <button
            class="lg:hidden absolute top-stack-sm right-stack-sm w-10 h-10 flex items-center justify-center rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest/70 hover:rotate-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 transition-all duration-soft ease-calm motion-reduce:transition-none motion-reduce:hover:rotate-0"
            type="button"
            data-drawer-close
            aria-label="Cerrar menú"
        >
            <span class="material-symbols-outlined">close</span>
        </button>

        <a class="p-stack-md flex items-center gap-3 mb-stack-lg" href="{{ route('home') }}">
            <img alt="Mente" class="h-8 w-auto" src="{{ config('brand.logo') }}"/>
            <span class="font-headline-md text-headline-md text-primary tracking-tight">Mente</span>
        </a>

        <nav class="flex-1 px-stack-sm flex flex-col gap-1">
            @foreach ($sections as $section)
                @php($isActive = request()->routeIs(...$section['active']))
                <a class="{{ $isActive ? $navActive : $navIdle }}"
                   @if ($isActive) aria-current="page" @endif
                   href="{{ route($section['route']) }}">
                    <span class="{{ $navIcon }}">{{ $section['icon'] }}</span>
                    {{ $section['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="p-stack-sm border-t border-outline-variant/10">
            <div class="flex items-center gap-3 p-stack-sm rounded-xl">
                <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-label-md text-label-md shrink-0">
                    {{ $user?->initials ?: '?' }}
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-label-md font-label-md text-on-surface truncate">{{ $user?->name }}</span>
                    <span class="text-label-sm font-label-sm text-on-surface-variant truncate">{{ $user?->email }}</span>
                </div>
            </div>

            <form class="hidden lg:block" method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button class="group w-full flex items-center gap-3 h-11 px-stack-md rounded-xl text-on-surface-variant hover:bg-surface-container-highest/60 hover:text-on-surface hover:translate-x-1 motion-reduce:hover:translate-x-0 transition-all duration-soft ease-calm motion-reduce:transition-none font-label-md text-label-md" type="submit">
                    <span class="{{ $navIcon }}">logout</span>
                    Salir
                </button>
            </form>
        </div>
    </aside>

    <main class="lg:pl-64 h-screen-safe w-full overflow-hidden">
        {{ $slot }}
    </main>

    @include('partials.swal-flash')
</body>
</html>
