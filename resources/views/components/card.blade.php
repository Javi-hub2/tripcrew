@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white p-6 shadow-kaart ring-1 ring-black/5']) }}>
    @if ($title)
        <h2 class="mb-4 text-lg font-bold text-brand-darker">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
