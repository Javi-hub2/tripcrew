@csrf
<x-field name="name" label="Naam" :value="old('name', $trip->name ?? '')" required />
<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="start_date" label="Begindatum" type="date" required
             :value="old('start_date', isset($trip) ? $trip->start_date->format('Y-m-d') : '')" />
    <x-field name="end_date" label="Einddatum" type="date" required
             :value="old('end_date', isset($trip) ? $trip->end_date->format('Y-m-d') : '')" />
</div>
<p class="text-sm text-slate-600">Voor elke dag in deze periode wordt automatisch een reisdag aangemaakt.</p>
{{-- Briefing: praktische informatie die de reiziger bij het dagprogramma ziet. --}}
<x-field name="practical_info" label="Praktische informatie (optioneel)" type="textarea"
         :value="old('practical_info', $trip->practical_info ?? '')"
         placeholder="Bijv. verzamelplaats en -tijd, verblijf, noodnummer, wat je mee moet nemen" />
<x-button type="submit">Opslaan</x-button>
