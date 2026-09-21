<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // toegang wordt bepaald door het token, niet door een ingelogde user
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
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
