<x-panel-layout title="Perfil">
    @php
        $initialModal = $errors->getBag('updatePassword')->any()
            ? 'password'
            : ($errors->getBag('userDeletion')->any() ? 'delete' : ($errors->default->any() ? 'information' : ''));
    @endphp
    <main class="mx-auto flex h-full w-full max-w-[1100px] flex-col gap-stack-lg overflow-y-auto p-stack-md lg:p-margin-desktop" data-profile-initial-modal="{{ $initialModal }}">
        <header class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 sm:gap-3">
                <x-drawer-toggle class="-ml-2" />
                <div>
                    <span class="text-label-md font-label-md uppercase tracking-widest text-primary">Tu cuenta</span>
                    <h1 class="mt-1 text-headline-md font-headline-md text-on-surface sm:text-3xl">Perfil</h1>
                    <p class="mt-2 text-body-md font-body-md text-on-surface-variant">Administra tus datos y la seguridad de tu cuenta.</p>
                </div>
            </div>
            <x-panel-actions />
        </header>

        <section class="grid grid-cols-1 gap-gutter lg:grid-cols-12">
            <div class="flex flex-col gap-stack-md lg:col-span-8">
                <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                    <button class="group flex w-full items-center justify-between gap-4 p-stack-md text-left transition hover:bg-surface-container-low" type="button" aria-haspopup="dialog" data-profile-toggle="information">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-surface-container text-primary transition group-hover:bg-primary group-hover:text-on-primary"><span class="material-symbols-outlined">person</span></span>
                            <span class="flex min-w-0 flex-col">
                                <span class="text-body-lg font-label-md text-on-surface">Información personal</span>
                                <span class="text-label-sm font-label-sm text-on-surface-variant">Nombre y correo electrónico</span>
                            </span>
                        </span>
                        <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform" data-profile-chevron>chevron_right</span>
                    </button>
                </section>

                <section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                    <button class="group flex w-full items-center justify-between gap-4 p-stack-md text-left transition hover:bg-surface-container-low" type="button" aria-haspopup="dialog" data-profile-toggle="password">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-surface-container text-on-surface-variant transition group-hover:bg-primary group-hover:text-on-primary"><span class="material-symbols-outlined">lock</span></span>
                            <span class="flex min-w-0 flex-col">
                                <span class="text-body-lg font-label-md text-on-surface">Seguridad de la cuenta</span>
                                <span class="text-label-sm font-label-sm text-on-surface-variant">Actualiza tu contraseña de acceso</span>
                            </span>
                        </span>
                        <span class="material-symbols-outlined shrink-0 text-on-surface-variant">chevron_right</span>
                    </button>
                </section>

                <section class="overflow-hidden rounded-2xl bg-error-container/15 shadow-sm ring-1 ring-error/20">
                    <button class="group flex w-full items-center justify-between gap-4 p-stack-md text-left transition hover:bg-error-container/30" type="button" aria-haspopup="dialog" data-profile-toggle="delete">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-error-container text-on-error-container transition group-hover:bg-error group-hover:text-on-error"><span class="material-symbols-outlined">delete_history</span></span>
                            <span class="flex min-w-0 flex-col">
                                <span class="text-body-lg font-label-md text-on-error-container">Eliminar cuenta</span>
                                <span class="text-label-sm font-label-sm text-on-surface-variant">Borra permanentemente tus datos</span>
                            </span>
                        </span>
                        <span class="material-symbols-outlined shrink-0 text-on-error-container/60">chevron_right</span>
                    </button>
                </section>
            </div>

            <aside class="flex flex-col gap-stack-md lg:col-span-4">
                <section class="rounded-2xl bg-surface-container-low p-stack-md shadow-sm ring-1 ring-outline-variant/10">
                    <h2 class="text-label-md font-label-md uppercase tracking-widest text-primary">Privacidad y seguridad</h2>
                    <div class="mt-4 flex items-start gap-3 rounded-xl bg-secondary/10 p-3 text-on-secondary-container">
                        <span class="material-symbols-outlined shrink-0">shield_lock</span>
                        <p class="text-body-md font-body-md">Tus conversaciones pertenecen a tu cuenta y puedes eliminarlas desde el historial.</p>
                    </div>
                </section>

                <section class="rounded-2xl bg-surface-container-lowest p-stack-md shadow-sm ring-1 ring-outline-variant/10">
                    <h2 class="text-label-md font-label-md uppercase tracking-widest text-primary">Acerca de Mente</h2>
                    <div class="mt-4 flex items-start gap-3">
                        <span class="material-symbols-outlined text-secondary">info</span>
                        <p class="text-body-md font-body-md text-on-surface-variant">Mente ofrece acompañamiento conversacional para bienestar emocional. No reemplaza la atención de un profesional.</p>
                    </div>
                </section>
            </aside>
        </section>
    </main>

    <dialog class="profile-modal w-[min(92vw,640px)] max-w-none rounded-2xl bg-surface p-0 text-on-surface shadow-2xl backdrop:bg-on-surface/35 backdrop:backdrop-blur-sm" data-profile-modal="information">
        <div class="flex items-center justify-between border-b border-outline-variant/20 px-stack-md py-4">
            <div>
                <h2 class="text-headline-md font-headline-md">Información personal</h2>
                <p class="text-label-md text-on-surface-variant">Actualiza tu nombre y correo electrónico.</p>
            </div>
            <button class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-highest" type="button" aria-label="Cerrar" data-profile-close><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-stack-md">@include('profile.partials.update-profile-information-form')</div>
    </dialog>

    <dialog class="profile-modal w-[min(92vw,640px)] max-w-none rounded-2xl bg-surface p-0 text-on-surface shadow-2xl backdrop:bg-on-surface/35 backdrop:backdrop-blur-sm" data-profile-modal="password">
        <div class="flex items-center justify-between border-b border-outline-variant/20 px-stack-md py-4">
            <div>
                <h2 class="text-headline-md font-headline-md">Seguridad de la cuenta</h2>
                <p class="text-label-md text-on-surface-variant">Actualiza tu contraseña de acceso.</p>
            </div>
            <button class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-highest" type="button" aria-label="Cerrar" data-profile-close><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-stack-md">@include('profile.partials.update-password-form')</div>
    </dialog>

    <dialog class="profile-modal w-[min(92vw,640px)] max-w-none rounded-2xl bg-surface p-0 text-on-surface shadow-2xl backdrop:bg-on-surface/35 backdrop:backdrop-blur-sm" data-profile-modal="delete">
        <div class="flex items-center justify-between border-b border-error/20 px-stack-md py-4">
            <div>
                <h2 class="text-headline-md font-headline-md text-error">Eliminar cuenta</h2>
                <p class="text-label-md text-on-surface-variant">Esta acción elimina tus datos permanentemente.</p>
            </div>
            <button class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-error-container/50" type="button" aria-label="Cerrar" data-profile-close><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-stack-md">@include('profile.partials.delete-user-form')</div>
    </dialog>

    <script>
        document.querySelectorAll('[data-profile-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                document.querySelector(`[data-profile-modal="${toggle.dataset.profileToggle}"]`)?.showModal();
            });
        });

        document.querySelectorAll('[data-profile-close]').forEach((close) => {
            close.addEventListener('click', () => close.closest('dialog')?.close());
        });

        document.querySelectorAll('.profile-modal').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.close();
                }
            });
        });

        const initialModal = document.querySelector('[data-profile-initial-modal]')?.dataset.profileInitialModal;
        if (initialModal) {
            document.querySelector(`[data-profile-modal="${initialModal}"]`)?.showModal();
        }
    </script>
</x-panel-layout>
