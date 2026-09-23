{{-- Dagkiezer voor het dagprogramma en de activiteiten. Verwacht $trip, $day en $dayRoute (routenaam). --}}
@if ($trip->days->isNotEmpty())
    <nav class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Dagen">
        @foreach ($trip->days as $d)
            @php $active = $day && $day->id === $d->id; @endphp
            <a href="{{ route($dayRoute, [$trip, $d->id]) }}"
               @class([
                   'rounded-full px-4 py-1.5 font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2',
                   'bg-accent text-white shadow-gloed' => $active,
                   'bg-white text-brand ring-1 ring-brand/20 hover:bg-brand/5' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                {{ $d->date->translatedFormat('D j M') }}
            </a>
        @endforeach
    </nav>
@endif
