@props(['activity'])

@php
    // choices_count komt uit withCount('choices'); zonder die telling tellen we zelf.
    $taken = $activity->choices_count ?? $activity->takenSeats();
    $capacity = $activity->capacity;
    $left = max(0, $capacity - $taken);
    // Capaciteit 0: geen deling door nul, de balk staat dan vol.
    $pct = $capacity ? min(100, (int) round($taken / $capacity * 100)) : 100;
    $almostFull = $capacity > 0 && $taken >= 0.75 * $capacity;

    // Voorrang: deadline voorbij > vol > bijna vol > plek vrij.
    [$status, $band] = match (true) {
        $activity->deadlineHasPassed() => ['Deadline voorbij', 'bg-slate-500'],
        $left === 0 => ['Vol', 'bg-slate-500'],
        $almostFull => ['Bijna vol', 'bg-linear-to-r from-accent-dark to-accent'],
        default => ['Plek vrij', 'bg-linear-to-r from-brand-darker to-brand'],
    };
    $bar = $left === 0 ? 'bg-accent-dark' : ($almostFull ? 'bg-accent' : 'bg-brand');
@endphp

{{-- FE-04: kaart met status, capaciteitsmeter en deadline. De status staat er altijd als tekst (FE-09). --}}
<article {{ $attributes->merge(['class' => 'flex flex-col overflow-hidden rounded-2xl bg-white shadow-kaart ring-1 ring-black/5 transition hover:-translate-y-1']) }}>
    <div class="flex items-center justify-between px-4 py-2.5 text-white {{ $band }}">
        <span aria-hidden="true" class="grid h-8 w-8 place-items-center rounded-full bg-white/25 font-bold">{{ mb_strtoupper(mb_substr($activity->name, 0, 1)) }}</span>
        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">{{ $status }}</span>
    </div>

    <div class="flex flex-1 flex-col p-4">
        <h2 class="text-lg font-bold text-brand-darker">{{ $activity->name }}</h2>
        {{ $meta ?? '' }}

        <p class="mt-2 text-sm text-slate-600">{{ $left }} van {{ $capacity }} plekken vrij</p>
        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200"
             role="progressbar" aria-valuenow="{{ $taken }}" aria-valuemin="0" aria-valuemax="{{ $capacity }}"
             aria-label="Bezetting {{ $activity->name }}">
            <div class="balk-vul h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
        </div>

        <p class="mt-3 text-sm text-slate-600">Deadline: {{ $activity->deadline->format('d-m-Y H:i') }}</p>

        @isset($acties)
            <div class="mt-auto pt-4">{{ $acties }}</div>
        @endisset
    </div>
</article>
