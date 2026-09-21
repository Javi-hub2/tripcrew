{{-- FE-09: iedere actie toont een melding met kleur én tekst — nooit kleur alleen. --}}
@if (session('success'))
    <div role="status" class="mb-4 flex items-center gap-2 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-success">
        <span aria-hidden="true">&#10003;</span>
        <span class="font-medium">Gelukt:</span> {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-4 flex items-center gap-2 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-danger">
        <span aria-hidden="true">&#9888;</span>
        <span class="font-medium">Let op:</span> {{ session('error') }}
    </div>
@endif
