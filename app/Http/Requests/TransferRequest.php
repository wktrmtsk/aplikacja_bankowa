<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
            'recipient_account_number' => [
                'required',
                'string',
                'size:26',
                'exists:users,account_number',
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()->account_number) {
                        $fail('Nie możesz wykonać przelewu na swoje konto');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recipient_account_number.required' => 'Numer konta odbiorcy jest wymagany',
            'recipient_account_number.size' => 'Numer konta musi mieć 26 znaków',
            'recipient_account_number.exists' => 'Podany numer konta nie istnieje',
            'amount.required' => 'Kwota jest wymagana',
            'amount.numeric' => 'Kwota musi być liczbą',
            'amount.min' => 'Minimalna kwota to 0.01 PLN',
            'amount.max' => 'Maksymalna kwota to 999,999.99 PLN',
            'title.max' => 'Tytuł przelewu może mieć maksymalnie 255 znaków',
            'description.max' => 'Opis może mieć maksymalnie 1000 znaków',
        ];
    }
}
