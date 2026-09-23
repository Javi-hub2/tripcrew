{{-- Reisposter-kop: teal kleurverloop met een golfrand in de zandkleur van de pagina. --}}
<header {{ $attributes->merge(['class' => 'relative bg-linear-to-br from-brand from-10% to-brand-darker text-white']) }}>
    <div class="mx-auto max-w-5xl px-4 pt-4 pb-20">
        {{ $nav ?? '' }}
        {{ $slot }}
    </div>
    <svg aria-hidden="true" class="absolute inset-x-0 -bottom-px h-8 w-full text-sand" viewBox="0 0 400 30" preserveAspectRatio="none">
        <path fill="currentColor" d="M0,20 C80,0 160,30 240,14 C300,2 360,10 400,18 L400,30 L0,30 Z"/>
    </svg>
</header>
