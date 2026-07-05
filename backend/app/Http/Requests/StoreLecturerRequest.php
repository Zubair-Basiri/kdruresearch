<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->check();
        return true; // Allow all for now, implement proper auth later
    }

    public function rules(): array
    {
        return [
            'lecturername' => 'required|string|max:255',
            'university_id' => 'required|exists:universities,id',
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
            'grade' => 'required|string|max:100',
            'qualification' => 'required|string|max:255',
            'specialized_area' => 'required|array',
            'specialized_area.*' => 'string|max:200',
        ];
    }
}
