<x-panel-layout title="Perfil">
    <main class="mx-auto flex h-full w-full max-w-[900px] flex-col gap-stack-lg overflow-y-auto p-stack-md lg:p-stack-lg">
        <header class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 sm:gap-3">
                <x-drawer-toggle class="-ml-2" />
                <div>
                    <h1 class="text-headline-md font-headline-md text-on-surface">Perfil</h1>
                    <p class="text-label-md font-label-md text-on-surface-variant">Tus datos y el acceso a la cuenta</p>
                </div>
            </div>
            <x-panel-actions />
        </header>

        <section class="flex flex-col gap-stack-md pb-stack-lg">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </section>
    </main>
</x-panel-layout>
