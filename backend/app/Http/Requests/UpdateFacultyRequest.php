<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->check();
        return true; // Allow all for now, implement proper auth later
    }

    public function rules(): array
    {
        return [
            'facultyname' => 'required|string|max:255',
            'university_id' => 'required|exists:universities,id',
        ];
    }
}
