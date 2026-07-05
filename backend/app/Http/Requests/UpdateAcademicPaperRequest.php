<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicPaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->check();
        return true; // Allow all for now, implement proper auth later
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'lecturer_id' => 'required|exists:lecturers,id',
            'year' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'publication' => 'required|string|max:255',
            'indexed' => 'required|string|max:100',
            'citation' => 'nullable|integer|min:0',
            'funding' => 'nullable|string|max:255',
            'collaboration' => 'nullable|string|max:255',
            'language' => 'required|string|max:100',
            'status' => 'required|string|max:100',
            'author_position' => 'required|string|max:100',
        ];
    }
}
