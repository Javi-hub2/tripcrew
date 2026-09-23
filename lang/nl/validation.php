<?php

/*
 * Nederlandse validatiemeldingen (eindreview-punt 4). Laravel levert zelf alleen
 * Engels mee; zonder dit bestand kreeg een bezoeker Engelse meldingen zodra een
 * FormRequest geen eigen `messages()` voor die regel had.
 *
 * Alleen de regels die dit project gebruikt (plus de meest voorkomende) staan
 * hier. Wat ontbreekt valt terug op APP_FALLBACK_LOCALE=en.
 */

return [
    'accepted' => 'Het veld :attribute moet geaccepteerd zijn.',
    'after' => 'Het veld :attribute moet een datum na :date zijn.',
    'after_or_equal' => 'Het veld :attribute moet een datum na of gelijk aan :date zijn.',
    'array' => 'Het veld :attribute moet een lijst zijn.',
    'before' => 'Het veld :attribute moet een datum voor :date zijn.',
    'before_or_equal' => 'Het veld :attribute moet een datum voor of gelijk aan :date zijn.',
    'between' => [
        'array' => 'Het veld :attribute moet tussen :min en :max items bevatten.',
        'file' => 'Het veld :attribute moet tussen :min en :max kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet tussen :min en :max liggen.',
        'string' => 'Het veld :attribute moet tussen :min en :max tekens bevatten.',
    ],
    'boolean' => 'Het veld :attribute moet ja of nee zijn.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => 'Het veld :attribute moet een geldige datum zijn.',
    'date_format' => 'Het veld :attribute moet het formaat :format hebben.',
    'different' => 'De velden :attribute en :other moeten verschillend zijn.',
    'digits' => 'Het veld :attribute moet :digits cijfers bevatten.',
    'email' => 'Het veld :attribute moet een geldig e-mailadres zijn.',
    'exists' => 'Het geselecteerde :attribute bestaat niet.',
    'file' => 'Het veld :attribute moet een bestand zijn.',
    'filled' => 'Het veld :attribute moet ingevuld zijn.',
    'gt' => [
        'numeric' => 'Het veld :attribute moet groter dan :value zijn.',
        'string' => 'Het veld :attribute moet meer dan :value tekens bevatten.',
    ],
    'gte' => [
        'numeric' => 'Het veld :attribute moet groter dan of gelijk aan :value zijn.',
        'string' => 'Het veld :attribute moet minstens :value tekens bevatten.',
    ],
    'image' => 'Het veld :attribute moet een afbeelding zijn.',
    'in' => 'Het geselecteerde :attribute is ongeldig.',
    'integer' => 'Het veld :attribute moet een geheel getal zijn.',
    'lt' => [
        'numeric' => 'Het veld :attribute moet kleiner dan :value zijn.',
        'string' => 'Het veld :attribute moet minder dan :value tekens bevatten.',
    ],
    'lte' => [
        'numeric' => 'Het veld :attribute moet kleiner dan of gelijk aan :value zijn.',
        'string' => 'Het veld :attribute mag niet meer dan :value tekens bevatten.',
    ],
    'max' => [
        'array' => 'Het veld :attribute mag niet meer dan :max items bevatten.',
        'file' => 'Het veld :attribute mag niet groter dan :max kilobytes zijn.',
        'numeric' => 'Het veld :attribute mag niet groter dan :max zijn.',
        'string' => 'Het veld :attribute mag niet meer dan :max tekens bevatten.',
    ],
    'min' => [
        'array' => 'Het veld :attribute moet minstens :min items bevatten.',
        'file' => 'Het veld :attribute moet minstens :min kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet minstens :min zijn.',
        'string' => 'Het veld :attribute moet minstens :min tekens bevatten.',
    ],
    'numeric' => 'Het veld :attribute moet een getal zijn.',
    'present' => 'Het veld :attribute moet aanwezig zijn.',
    'prohibited' => 'Het veld :attribute is niet toegestaan.',
    'required' => 'Het veld :attribute is verplicht.',
    'required_if' => 'Het veld :attribute is verplicht als :other gelijk is aan :value.',
    'required_with' => 'Het veld :attribute is verplicht in combinatie met :values.',
    'same' => 'De velden :attribute en :other moeten overeenkomen.',
    'size' => [
        'array' => 'Het veld :attribute moet :size items bevatten.',
        'file' => 'Het veld :attribute moet :size kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet :size zijn.',
        'string' => 'Het veld :attribute moet :size tekens bevatten.',
    ],
    'string' => 'Het veld :attribute moet tekst zijn.',
    'unique' => 'Het veld :attribute is al in gebruik.',
    'url' => 'Het veld :attribute moet een geldige URL zijn.',

    // Eigen meldingen per veld horen in de FormRequest zelf (messages()).
    'custom' => [],

    // Zodat ":attribute" een Nederlandse veldnaam wordt in plaats van de kolomnaam.
    'attributes' => [
        'name' => 'naam',
        'email' => 'e-mailadres',
        'password' => 'wachtwoord',
        'password_confirmation' => 'wachtwoordbevestiging',
        'capacity' => 'capaciteit',
        'deadline' => 'deadline',
        'start_date' => 'startdatum',
        'end_date' => 'einddatum',
        'destination' => 'bestemming',
        'title' => 'titel',
        'trip_day_id' => 'dag',
        'label' => 'checklist-item',
        'practical_info' => 'praktische informatie',
        'time' => 'tijd',
        'location' => 'locatie',
        'token' => 'token',
    ],
];
