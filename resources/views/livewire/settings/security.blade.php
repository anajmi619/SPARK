<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Tetapan Keselamatan') }}</flux:heading>

    <x-settings.layout :heading="__('Kemas Kini Kata Laluan')" :subheading="__('Gunakan kata laluan yang kukuh untuk melindungi akaun anda')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Kata Laluan Semasa')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:input
                wire:model="password"
                :label="__('Kata Laluan Baharu')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Sahkan Kata Laluan')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" icon="check" class="spark-settings-save" wire:loading.attr="disabled" data-test="update-password-button">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
