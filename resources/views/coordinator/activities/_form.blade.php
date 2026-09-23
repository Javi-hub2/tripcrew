@csrf
<x-field name="trip_day_id" label="Dag" type="select" required
         :options="$days->mapWithKeys(fn ($day) => [$day->id => $day->date->translatedFormat('l j F Y')])"
         :value="old('trip_day_id', $activity->trip_day_id ?? null)" />
<x-field name="name" label="Naam" :value="old('name', $activity->name ?? '')" required />
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="capacity" class="block text-sm font-semibold text-slate-700">Capaciteit (aantal plekken)</label>
        <input id="capacity" name="capacity" type="number" min="1" step="1" required
               value="{{ old('capacity', $activity->capacity ?? '') }}"
               aria-describedby="capacity-error"
               class="veld">
        {{-- FE-07: melding direct naast het veld (client + server) --}}
        <p id="capacity-error" class="mt-1 hidden text-sm text-danger">&#10007; Capaciteit moet een geheel getal groter dan 0 zijn.</p>
        @error('capacity')<p class="mt-1 text-sm text-danger">&#10007; {{ $message }}</p>@enderror
    </div>
    <x-field name="deadline" label="Deadline" type="datetime-local" required
             :value="old('deadline', isset($activity) ? $activity->deadline->format('Y-m-d\TH:i') : '')" />
</div>
<x-button type="submit" id="save-activity">
    Opslaan
</x-button>

<script>
    // FE-07: "Opslaan" blijft uitgeschakeld tot de capaciteit een geheel getal > 0 is.
    // Dit is alleen gebruiksgemak; de echte controle gebeurt serverside (StoreActivityRequest, TE-06).
    (function () {
        const input = document.getElementById('capacity');
        const error = document.getElementById('capacity-error');
        const button = document.getElementById('save-activity');
        const check = () => {
            const valid = /^[1-9]\d*$/.test(input.value.trim());
            button.disabled = !valid;
            error.classList.toggle('hidden', valid || input.value === '');
        };
        input.addEventListener('input', check);
        check();
    })();
</script>
