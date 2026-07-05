<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->check();
        return true; // For development, allow all. Implement auth later.
    }

    public function rules(): array
    {
        return ['name' => 'required|string|unique:universities,name'];
    }
}
