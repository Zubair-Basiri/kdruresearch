<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->check();
        return true; // For development, allow all. Implement auth later.
    }

    public function rules()
    {
        $id = $this->route('university')->id ?? $this->route('university');
        return ['name' => 'required|string|unique:universities,name,'.$id];
    }
}
