@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
    $styles = [
        'primary' => 'bg-accent text-white hover:bg-accent-dark focus-visible:ring-accent',
        'secondary' => 'border border-brand/30 bg-white text-brand hover:bg-brand/5 focus-visible:ring-brand',
        'danger' => 'bg-danger text-white hover:opacity-90 focus-visible:ring-danger',
    ];
    $classes = $base.' '.($styles[$variant] ?? $styles['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
