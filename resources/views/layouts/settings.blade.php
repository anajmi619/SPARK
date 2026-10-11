@if (auth()->user()->role === \App\Enums\UserRole::Student)
    <x-student-layout :title="$title ?? __('Tetapan')" settings>
        {{ $slot }}
    </x-student-layout>
@else
    <x-layouts::app :title="$title ?? __('Tetapan')">
        {{ $slot }}
    </x-layouts::app>
@endif
