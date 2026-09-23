{{-- Lege toestand: "nog niets hier". Tekst plus icoon, nooit alleen kleur. --}}
<div role="status" {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-2xl border border-dashed border-brand/30 bg-white/70 px-4 py-4 text-slate-600']) }}>
    <span aria-hidden="true" class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand/10 text-brand">&#8505;</span>
    <span>{{ $slot }}</span>
</div>
