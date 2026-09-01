<x-panel-layout title="Historial">
    <main class="mx-auto flex h-full w-full max-w-[1000px] flex-col gap-stack-lg overflow-y-auto p-stack-md lg:p-stack-lg">
        <header class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 sm:gap-3">
                <x-drawer-toggle class="-ml-2" />
                <div>
                    <h1 class="text-headline-md font-headline-md text-on-surface">Historial</h1>
                    <p class="text-body-sm font-body-sm text-on-surface-variant">Tus conversaciones guardadas</p>
                </div>
            </div>
            <a class="flex h-12 items-center gap-2 rounded-full bg-primary px-4 text-label-md font-label-md text-on-primary shadow-sm transition hover:opacity-90" href="{{ route('conversations.create') }}">
                <span class="material-symbols-outlined">add</span>
                Nueva conversación
            </a>
        </header>

        <section class="flex flex-col gap-3">
            @forelse ($conversations as $conversation)
                <div class="flex items-center justify-between gap-4 rounded-2xl bg-surface p-4 shadow-sm ring-1 ring-outline-variant/10 transition hover:bg-surface-container-low">
                    <a class="flex min-w-0 flex-1 items-center gap-3" href="{{ route('conversations.show', $conversation) }}">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-container text-on-primary-container">
                            <span class="material-symbols-outlined">forum</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-body-lg font-label-md text-on-surface">{{ $conversation->title ?: 'Sesión sin título' }}</h2>
                            <p class="text-label-sm font-label-sm text-on-surface-variant">
                                {{ $conversation->messages_count }} {{ $conversation->messages_count === 1 ? 'mensaje' : 'mensajes' }} · {{ $conversation->updated_at->format('d/m/Y g:i A') }}
                            </p>
                        </div>
                    </div>
                    </a>
                    <div class="relative" data-menu-wrapper>
                        <button class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-highest hover:text-on-surface" type="button" aria-label="Más opciones" aria-expanded="false" data-menu-button>
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                        <div class="absolute right-0 top-11 z-20 hidden w-44 rounded-xl bg-surface p-1 shadow-lg ring-1 ring-outline-variant/20" data-menu>
                            <form method="POST" action="{{ route('conversations.destroy', $conversation) }}" data-confirm-conversation-delete>
                                @csrf
                                @method('DELETE')
                                <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-label-md text-error transition hover:bg-error-container/40" type="submit">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-surface p-8 text-center shadow-sm ring-1 ring-outline-variant/10">
                    <p class="text-body-md font-body-md text-on-surface-variant">Aún no tienes conversaciones guardadas.</p>
                </div>
            @endforelse
        </section>
    </main>
</x-panel-layout>