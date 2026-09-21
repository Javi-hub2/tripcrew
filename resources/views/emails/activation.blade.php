<x-app-mail::layout>
    <p>Hallo {{ $name }},</p>
    <p>Je account voor TripCrew staat klaar. Stel hieronder je eigen wachtwoord in.</p>
    <p style="margin:24px 0;">
        <a href="{{ $url }}" style="background:#C2410C;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">
            Wachtwoord instellen
        </a>
    </p>
    <p style="font-size:13px;color:#64748B;">Werkt de knop niet? Kopieer deze link naar je browser:<br>{{ $url }}</p>
</x-app-mail::layout>
