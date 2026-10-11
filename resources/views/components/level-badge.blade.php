@props([
    'number',
    'name',
    'size' => 'size-24',
])

<img
    src="{{ asset('images/badges/level-'.$number.'.png') }}"
    alt="{{ __('Lencana Tahap :number: :name', ['number' => $number, 'name' => $name]) }}"
    width="256"
    height="256"
    decoding="async"
    {{ $attributes->class([$size, 'shrink-0 object-contain drop-shadow-lg']) }}
>
