@csrf
<div>
    <label for="name" class="block text-sm font-medium">Naam</label>
    <input id="name" name="name" value="{{ old('name', $trip->name ?? '') }}" required
           class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="start_date" class="block text-sm font-medium">Begindatum</label>
        <input id="start_date" type="date" name="start_date" required
               value="{{ old('start_date', isset($trip) ? $trip->start_date->format('Y-m-d') : '') }}"
               class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
        @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="end_date" class="block text-sm font-medium">Einddatum</label>
        <input id="end_date" type="date" name="end_date" required
               value="{{ old('end_date', isset($trip) ? $trip->end_date->format('Y-m-d') : '') }}"
               class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
        @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<p class="text-sm text-slate-600">Voor elke dag in deze periode wordt automatisch een reisdag aangemaakt.</p>
<button class="rounded bg-[#0C4A6E] px-4 py-2 font-medium text-white">Opslaan</button>
