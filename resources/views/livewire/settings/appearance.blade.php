<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Tetapan Paparan') }}</flux:heading>

    <x-settings.layout :heading="__('Paparan')" :subheading=" __('Kemas kini paparan sistem untuk akaun anda')">
        @if (auth()->user()->role === \App\Enums\UserRole::Student)
            <fieldset x-data class="spark-appearance-options">
                <legend class="sr-only">Tema paparan</legend>
                @foreach ([['light', 'sun', 'Cerah'], ['dark', 'moon', 'Gelap'], ['system', 'computer-desktop', 'Sistem']] as [$value, $icon, $label])
                    <label class="spark-appearance-option">
                        <span class="spark-appearance-preview spark-preview-{{ $value }}" aria-hidden="true">
                            <span class="spark-preview-bar"></span>
                            <span class="spark-preview-lines"><span></span><span></span><span></span></span>
                        </span>
                        <span class="flex items-center gap-2">
                            <flux:icon :name="$icon" class="size-5 shrink-0" />
                            <span class="font-extrabold">{{ $label }}</span>
                            <input type="radio" name="appearance" value="{{ $value }}" x-model="$flux.appearance" class="ml-auto size-4 shrink-0" />
                        </span>
                    </label>
                @endforeach
            </fieldset>
        @else
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Cerah') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Gelap') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('Sistem') }}</flux:radio>
        </flux:radio.group>
        @endif
    </x-settings.layout>
</section>
