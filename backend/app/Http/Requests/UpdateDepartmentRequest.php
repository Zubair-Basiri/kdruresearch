<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        // return auth()->check();
        return true; // Allow all for now, implement proper auth later
    }

    public function rules(): array
    {
        return [
            'deptname' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
            'university_id' => 'required|exists:universities,id',
        ];
    }
}
