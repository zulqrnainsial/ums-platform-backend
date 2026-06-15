<?php

namespace App\Modules\Subject\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAssignCurriculumSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curriculum_id' => ['required', 'integer', 'exists:curriculums,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'term_number' => ['required', 'integer', 'min:1'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'items.*.subject_code' => ['nullable', 'string', 'max:100'],
            'items.*.subject_name' => ['nullable', 'string', 'max:255'],

            'items.*.subject_nature' => [
                'required',
                Rule::in([
                    'theory',
                    'practical',
                    'theory_practical',
                    'viva',
                    'project',
                    'internship',
                    'other',
                ]),
            ],

            'items.*.credit_hours' => ['required', 'numeric', 'min:0'],
            'items.*.theory_hours' => ['nullable', 'integer', 'min:0'],
            'items.*.practical_hours' => ['nullable', 'integer', 'min:0'],
            'items.*.tutorial_hours' => ['nullable', 'integer', 'min:0'],

            'items.*.total_marks' => ['required', 'integer', 'min:0'],
            'items.*.passing_marks' => ['required', 'integer', 'min:0'],

            'items.*.is_compulsory' => ['required', 'boolean'],
            'items.*.is_credit_subject' => ['required', 'boolean'],

            'items.*.display_order' => ['nullable', 'integer', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],

            'overwrite_existing' => ['nullable', 'boolean'],
        ];
    }
}