@csrf
<div>
    <label for="trip_day_id" class="block text-sm font-medium">Dag</label>
    <select id="trip_day_id" name="trip_day_id" required
            class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
        @foreach ($days as $day)
            <option value="{{ $day->id }}" @selected(old('trip_day_id', $activity->trip_day_id ?? null) == $day->id)>
                {{ $day->date->format('d-m-Y') }}
            </option>
        @endforeach
    </select>
    @error('trip_day_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label for="name" class="block text-sm font-medium">Naam</label>
    <input id="name" name="name" value="{{ old('name', $activity->name ?? '') }}" required
           class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="capacity" class="block text-sm font-medium">Capaciteit (aantal plekken)</label>
        <input id="capacity" name="capacity" type="number" min="1" step="1" required
               value="{{ old('capacity', $activity->capacity ?? '') }}"
               aria-describedby="capacity-error"
               class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
        {{-- FE-07: melding direct naast het veld (client + server) --}}
        <p id="capacity-error" class="mt-1 hidden text-sm text-red-600">&#10007; Capaciteit moet een geheel getal groter dan 0 zijn.</p>
        @error('capacity')<p class="mt-1 text-sm text-red-600">&#10007; {{ $message }}</p>@enderror
    </div>
    <div>
        <label for="deadline" class="block text-sm font-medium">Deadline</label>
        <input id="deadline" name="deadline" type="datetime-local" required
               value="{{ old('deadline', isset($activity) ? $activity->deadline->format('Y-m-d\TH:i') : '') }}"
               class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
        @error('deadline')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<button id="save-activity" class="rounded bg-[#0C4A6E] px-4 py-2 font-medium text-white disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600">
    Opslaan
</button>

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
