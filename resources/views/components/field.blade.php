{{--
    'id'  eigen id als hetzelfde veld vaker op een pagina staat (standaard: de naam)
    'bag' foutenzak waaruit de melding komt (standaard: 'default')
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'id' => null, 'bag' => 'default'])

@php $id ??= $name; @endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'veld']) }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="6" {{ $attributes->merge(['class' => 'veld']) }}>{{ $value }}</textarea>
    @else
        <input id="{{ $id }}"
               name="{{ $name }}"
               type="{{ $type }}"
               value="{{ $type === 'password' ? '' : $value }}"
               {{ $attributes->merge(['class' => 'veld']) }}>
    @endif
    @error($name, $bag)
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
