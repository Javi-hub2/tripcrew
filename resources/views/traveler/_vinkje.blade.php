{{-- Afvinkknop binnen een checklistformulier. Verwacht $checked (bool) en $label. --}}
<button type="submit"
        class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-sand focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
        aria-pressed="{{ $checked ? 'true' : 'false' }}">
    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2 font-bold transition {{ $checked ? 'border-success bg-success text-white' : 'border-slate-300 bg-white' }}">
        @if ($checked)&#10003;@endif
    </span>
    <span class="{{ $checked ? 'text-slate-500 line-through' : '' }}">{{ $label }}</span>
    <span class="sr-only">({{ $checked ? 'afgevinkt' : 'nog niet afgevinkt' }})</span>
</button>
