<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Tetapan Profil') }}</flux:heading>

    <x-settings.layout :heading="__('Profil')" :subheading="__('Kemas kini nama dan nama pengguna anda')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="full_name" :label="__('Nama Penuh')" type="text" required autocomplete="name" />

            <flux:input wire:model="username" :label="__('Nama Pengguna')" type="text" required autocomplete="username" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" icon="check" class="spark-settings-save" wire:loading.attr="disabled">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
