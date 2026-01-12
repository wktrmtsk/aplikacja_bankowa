<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'pesel' => 'required|string|size:11|unique:users',
            'birth_date' => 'required|date|before:today',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Imię jest wymagane',
            'last_name.required' => 'Nazwisko jest wymagane',
            'email.required' => 'Email jest wymagany',
            'email.email' => 'Podaj poprawny adres email',
            'email.unique' => 'Ten email jest już zarejestrowany',
            'password.required' => 'Hasło jest wymagane',
            'password.min' => 'Hasło musi mieć minimum 8 znaków',
            'password.confirmed' => 'Hasła nie są identyczne',
            'pesel.required' => 'PESEL jest wymagany',
            'pesel.size' => 'PESEL musi mieć 11 cyfr',
            'pesel.unique' => 'Ten PESEL jest już zarejestrowany',
            'birth_date.required' => 'Data urodzenia jest wymagana',
            'birth_date.before' => 'Data urodzenia musi być wcześniejsza niż dzisiaj',
        ];
    }
}
