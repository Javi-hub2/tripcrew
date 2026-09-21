{{-- FE-09: iedere actie toont een melding met kleur én tekst — nooit kleur alleen. --}}
@if (session('success'))
    <div role="status" class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800">
        <span aria-hidden="true">&#10003;</span>
        <span class="font-medium">Gelukt:</span> {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-4 flex items-center gap-2 rounded-lg border border-[#F97316]/40 bg-orange-50 px-4 py-3 text-orange-900">
        <span aria-hidden="true">&#9888;</span>
        <span class="font-medium">Let op:</span> {{ session('error') }}
    </div>
@endif
