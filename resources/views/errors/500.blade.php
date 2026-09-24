@extends('errors.layout')
@section('code', '500')
@section('title', 'Er ging iets mis')
{{-- Nooit de foutmelding zelf tonen: die kan technische details bevatten. --}}
@section('message', 'Er ging aan onze kant iets mis. Probeer het later opnieuw.')
