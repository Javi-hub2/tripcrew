@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => []])

<div>
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    @if ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'veld']) }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $name }}"
               name="{{ $name }}"
               type="{{ $type }}"
               value="{{ $type === 'password' ? '' : $value }}"
               {{ $attributes->merge(['class' => 'veld']) }}>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
