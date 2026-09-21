@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-black/5 bg-white p-6 shadow-sm']) }}>
    @if ($title)
        <h2 class="mb-4 text-lg font-semibold text-brand">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
