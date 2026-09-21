@props(['name', 'label', 'type' => 'text', 'value' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input id="{{ $name }}"
           name="{{ $name }}"
           type="{{ $type }}"
           value="{{ $type === 'password' ? '' : $value }}"
           {{ $attributes->merge(['class' => 'mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand focus:ring-brand']) }}>
    @error($name)
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
