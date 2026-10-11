@props(['path' => null, 'alt'])
@if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->class('rounded-lg') }} />
@elseif ($path)
    <p class="text-sm text-zinc-600">{{ __('Imej tidak tersedia.') }}</p>
@endif
