<x-layouts::auth :title="__('Sahkan Kata Laluan')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Sahkan Kata Laluan')"
            :description="__('Sila sahkan kata laluan anda sebelum meneruskan.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('Kata Laluan')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Kata Laluan')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Sahkan') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
