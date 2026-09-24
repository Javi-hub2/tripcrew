@extends('errors.layout')
@section('code', '403')
@section('title', 'Geen toegang')
@php
    // Een eigen melding uit abort(403, '...') tonen (bijv. EnsureRole). Een policy-weigering
    // via authorize() heeft Laravels vaste Engelse tekst; die vervangen we door een algemene.
    $melding = $exception->getMessage();
    if ($melding === '' || $melding === 'This action is unauthorized.') {
        $melding = 'Je hebt geen toegang tot deze pagina of actie.';
    }
@endphp
@section('message', $melding)
