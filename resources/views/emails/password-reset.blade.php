<x-app-mail::layout>
    <p>Hallo {{ $name }},</p>
    <p>Je hebt een nieuw wachtwoord aangevraagd voor TripCrew.</p>
    <p style="margin:24px 0;">
        <a href="{{ $url }}" style="background:#C2410C;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">
            Nieuw wachtwoord instellen
        </a>
    </p>
    <p style="font-size:13px;color:#64748B;">
        Deze link verloopt na {{ $minutes }} minuten. Heb je dit niet aangevraagd, dan hoef je niets te doen.
    </p>
</x-app-mail::layout>
