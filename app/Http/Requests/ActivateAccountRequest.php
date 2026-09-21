<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// FE-01
class ActivateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // toegang wordt bepaald door het activatie-token in de route, niet door een ingelogde user
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'De wachtwoorden komen niet overeen.',
            'password.min' => 'Het wachtwoord moet minstens 8 tekens bevatten.',
        ];
    }
}
