<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // openbare pagina; de guest-middleware bewaakt de toegang
    }

    public function rules(): array
    {
        // Bewust géén unique-regel: een bestaand adres mag niet uitlekken.
        // De controller handelt dat stil af.
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
        ];
    }
}
