@props(['name', 'label', 'path' => null])
<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }} <span class="font-normal">(Pilihan; maksimum {{ (int) (config('quiz.image_max_size_kilobytes') / 1024) }} MB)</span></label>
    @if ($path)
        <x-quiz-image :path="$path" :alt="$label" class="max-h-40 w-full object-contain" />
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remove_{{ $name }}" value="1" @checked(old('remove_'.$name)) />
            {{ __('Buang imej sedia ada') }}
        </label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="file" accept=".jpg,.jpeg,.png,.webp" class="block w-full min-w-0 border border-zinc-300 p-2 text-sm" />
    @error($name)<p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
</div>
