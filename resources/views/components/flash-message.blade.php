{{-- FE-09: iedere actie toont een melding met kleur én tekst — nooit kleur alleen. --}}
@if (session('success'))
    <div role="status" class="mb-6 flex items-center gap-3 rounded-2xl border-l-4 border-success bg-white px-4 py-3 text-success shadow-kaart">
        <span aria-hidden="true" class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-success/10 font-bold">&#10003;</span>
        <p><span class="font-semibold">Gelukt:</span> {{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-6 flex items-center gap-3 rounded-2xl border-l-4 border-danger bg-white px-4 py-3 text-danger shadow-kaart">
        <span aria-hidden="true" class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-danger/10 font-bold">&#9888;</span>
        <p><span class="font-semibold">Let op:</span> {{ session('error') }}</p>
    </div>
@endif
