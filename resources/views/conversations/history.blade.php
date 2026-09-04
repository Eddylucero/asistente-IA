<x-panel-layout title="Historial">
    <main class="mx-auto flex h-full w-full max-w-[1200px] flex-col gap-stack-lg overflow-y-auto p-stack-md lg:p-margin-desktop" data-history>
        <header class="flex flex-col gap-stack-md lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-start gap-2 sm:gap-3">
                <x-drawer-toggle class="-ml-2 lg:hidden" />
                <div>
                    <span class="text-label-md font-label-md uppercase tracking-widest text-primary">Tus registros</span>
                    <h1 class="mt-1 text-headline-md font-headline-md text-on-surface sm:text-3xl">Historial de conversaciones</h1>
                    <p class="mt-2 max-w-2xl text-body-md font-body-md text-on-surface-variant">Revisa tus reflexiones anteriores y el progreso de tu bienestar emocional.</p>
                </div>
            </div>
            <a
                class="flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-primary px-5 text-label-md font-label-md text-on-primary shadow-sm transition hover:-translate-y-0.5 hover:opacity-90"
                href="{{ route('conversations.create') }}"
            >
                <span class="material-symbols-outlined">add</span>
                Nueva conversación
            </a>
        </header>

        <div class="grid grid-cols-1 gap-gutter lg:grid-cols-12">
            <section class="flex min-w-0 flex-col gap-stack-md lg:col-span-8">
                <label class="group flex items-center gap-2 rounded-2xl bg-surface-container-low px-3 py-2 shadow-sm ring-1 ring-outline-variant/10 transition-colors focus-within:bg-surface-container-lowest focus-within:ring-2 focus-within:ring-primary/40 hover:bg-surface-container">
                    <span class="material-symbols-outlined text-on-surface-variant transition-colors group-focus-within:text-primary">search</span>
                    <span class="sr-only">Buscar conversaciones</span>
                    <input class="min-w-0 flex-1 appearance-none border-0 bg-transparent text-body-md font-body-md text-on-surface caret-primary outline-none placeholder:text-on-surface-variant focus:border-0 focus:ring-0" placeholder="Buscar temas, fechas o palabras clave..." type="search" data-history-search>
                </label>

                @forelse ($conversations->groupBy(fn ($conversation) => $conversation->updated_at->isToday() ? 'Hoy' : ($conversation->updated_at->isYesterday() ? 'Ayer' : $conversation->updated_at->translatedFormat('d/m/Y'))) as $dateLabel => $dateConversations)
                    <div class="flex flex-col gap-stack-sm" data-history-group>
                        <h2 class="pl-2 text-label-sm font-label-sm uppercase tracking-widest text-on-surface-variant">{{ $dateLabel }}</h2>
                        @foreach ($dateConversations as $conversation)
                            @php
                                $title = $conversation->title === 'Acompañamiento emocional'
                                    ? $conversation->thematicTitle()
                                    : ($conversation->title ?: 'Sesión sin título');
                                $summary = $conversation->summary();
                            @endphp
                            <article class="group relative flex flex-col gap-3 overflow-hidden rounded-2xl bg-surface-container-lowest p-stack-md shadow-sm ring-1 ring-outline-variant/10 transition hover:-translate-y-0.5 hover:shadow-md" data-history-item data-search-text="{{ strtolower($title . ' ' . $summary . ' ' . $conversation->updated_at->format('d/m/Y')) }}">                                
                                <div class="flex items-start justify-between gap-3 pl-2">
                                    <a class="min-w-0 flex-1" href="{{ route('conversations.show', $conversation) }}">
                                        <h3 class="truncate text-headline-md font-headline-md text-on-surface transition group-hover:text-primary">{{ $title }}</h3>
                                        <p class="mt-1 flex items-center gap-2 text-label-md font-label-md text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[16px]">schedule</span>
                                            {{ $conversation->updated_at->format('g:i A') }} · {{ $conversation->messages_count }} {{ $conversation->messages_count === 1 ? 'mensaje' : 'mensajes' }}
                                        </p>
                                    </a>
                                    <div class="relative shrink-0" data-menu-wrapper>
                                        <button class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-highest hover:text-on-surface" type="button" aria-label="Más opciones" aria-expanded="false" data-menu-button>
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>
                                        <div class="absolute right-0 top-11 z-20 hidden w-44 rounded-xl bg-surface p-1 shadow-lg ring-1 ring-outline-variant/20" data-menu>
                                            <form method="POST" action="{{ route('conversations.destroy', $conversation) }}" data-confirm-conversation-delete>
                                                @csrf
                                                @method('DELETE')
                                                <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-label-md text-error transition hover:bg-error-container/40" type="submit">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <p class="line-clamp-2 pl-2 text-body-md font-body-md text-on-surface-variant">{{ $summary }}</p>
                            </article>
                        @endforeach
                    </div>
                @empty
                    <div class="rounded-2xl bg-surface-container-lowest p-8 text-center shadow-sm ring-1 ring-outline-variant/10">
                        <span class="material-symbols-outlined text-4xl text-primary">forum</span>
                        <p class="mt-2 text-body-md font-body-md text-on-surface-variant">Aún no tienes conversaciones guardadas.</p>
                    </div>
                @endforelse
                <p class="hidden rounded-xl bg-surface-container-low p-4 text-center text-body-md text-on-surface-variant" data-history-empty-search>No encontramos conversaciones con ese criterio.</p>
            </section>

            <aside class="flex flex-col gap-stack-md lg:col-span-4">
                <section class="relative overflow-hidden rounded-2xl bg-primary p-stack-md text-on-primary shadow-md">
                    <h2 class="text-headline-md font-headline-md">Resumen semanal</h2>
                    <div class="mt-stack-md grid grid-cols-2 gap-4">
                        <div><strong class="block text-4xl font-headline-md">{{ $weeklySessions }}</strong><span class="text-label-sm uppercase tracking-wider opacity-80">Sesiones</span></div>
                        <div><strong class="block text-4xl font-headline-md">{{ $conversations->count() }}</strong><span class="text-label-sm uppercase tracking-wider opacity-80">Totales</span></div>
                    </div>
                </section>

                <section class="rounded-2xl bg-surface-container-lowest p-stack-md shadow-sm ring-1 ring-outline-variant/10">
                    <h2 class="text-label-md font-label-md uppercase tracking-widest text-on-surface-variant">Actividad reciente</h2>
                    <div class="mt-5 flex h-24 items-end justify-between gap-2" aria-label="Actividad de los últimos siete días">
                        @php $maxActivity = max(1, $activity->max('count')); @endphp
                        @foreach ($activity as $day)
                            <div class="group flex h-full flex-1 flex-col items-center justify-end gap-1">
                                <div class="w-full rounded-t-sm bg-primary/20 transition group-hover:bg-primary" style="height: {{ max(8, ($day['count'] / $maxActivity) * 100) }}%" title="{{ $day['count'] }} sesiones"></div>
                                <span class="text-label-sm text-outline">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl bg-surface-container-lowest p-stack-md shadow-sm ring-1 ring-outline-variant/10">
                    <h2 class="text-label-md font-label-md uppercase tracking-widest text-on-surface-variant">Temas frecuentes</h2>
                    <div class="mt-4 flex items-start gap-3 rounded-xl bg-secondary/10 p-3 text-on-secondary-container">
                        <span class="material-symbols-outlined shrink-0">auto_awesome</span>
                        <p class="text-body-md font-body-md">Aún no hay suficiente información para identificar tus temas frecuentes.</p>
                    </div>
                </section>

                <section
                    class="group relative h-48 w-full overflow-hidden rounded-2xl bg-cover bg-center shadow-sm"
                    style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuC9ThBubqOHu2tHfKhljrTZht0dj7iQlrvBUwoK6wuIghxQD_La9hVWe33JeZP2FCYVeo7pxuI11_j2vp-5OM3IYKq2TDDGW0oaCBOWTzI2XZ-_g1evuUGaS1Qdu5do3nUxlX2sth1beh783XqgAw7eZhmGHDXcGFU782ZYWTasGo73JBf1eGbWFsKqxwW12jdoLrud2PcGHS9Ycj9ImFpSjg8xB2D_NfzFuKj_jjiYd48n-J5T2cU4')"
                    aria-label="Espacio seguro de Mente"
                >
                    <div class="absolute inset-0 bg-gradient-to-t from-surface/90 via-surface/35 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4">
                        <span class="flex items-center gap-2 text-label-md font-label-md text-on-surface">
                            <span class="material-symbols-outlined text-secondary">insights</span>
                            Espacio seguro
                        </span>
                        <p class="mt-1 text-body-md text-on-surface-variant">Un lugar privado para volver a tus reflexiones cuando lo necesites.</p>
                    </div>
                </section>
            </aside>
        </section>
    </main>
    <script>
        document.querySelector('[data-history-search]')?.addEventListener('input', (event) => {
            const query = event.target.value.trim().toLowerCase();
            document.querySelectorAll('[data-history-group]').forEach((group) => {
                let visible = 0;
                group.querySelectorAll('[data-history-item]').forEach((item) => {
                    const matches = !query || item.dataset.searchText.includes(query);
                    item.classList.toggle('hidden', !matches);
                    visible += matches ? 1 : 0;
                });
                group.classList.toggle('hidden', visible === 0);
            });
            document.querySelector('[data-history-empty-search]')?.classList.toggle('hidden', !query || [...document.querySelectorAll('[data-history-item]')].some((item) => !item.classList.contains('hidden')));
        });
    </script>
</x-panel-layout>