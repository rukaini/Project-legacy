<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $availableUntilRules = ['nullable', 'date'];

        if ($this->filled('available_from')) {
            $availableUntilRules[] = 'after:available_from';
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['quiz', 'submission'])],
            'instruction_text' => ['nullable', 'required_if:type,submission', 'string'],
            'available_from' => ['nullable', 'date'],
            'available_until' => $availableUntilRules,
            'accepted_format' => [
                'nullable',
                Rule::in(['PDF Only', 'Document Only', 'Image Only', 'ZIP Only']),
            ],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'weight_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_marks' => ['nullable', 'numeric', 'min:0'],
            'questions' => ['nullable', 'array', 'max:100'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.question_id' => ['nullable', 'integer', 'min:1'],
            'questions.*.question_type' => [
                'required',
                Rule::in(['mcq', 'checkbox', 'true_false', 'short_answer', 'essay']),
            ],
            'questions.*.options_json' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.correct_answers_json' => ['nullable', 'string'],
            'questions.*.marks' => ['required', 'numeric', 'min:0'],
            'questions.*.grading_rubric' => ['nullable', 'string'],
            'questions.*.question_order' => ['nullable', 'integer', 'min:1'],
            'questions.*.question_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'questions.*.existing_question_image_path' => [
                'nullable',
                'string',
                'max:255',
            ],
            'questions.*.remove_question_image' => ['nullable', 'boolean'],
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'available_until.after' => 'Available Until must be after Available From.',
            'accepted_format.in' => 'Choose one of the supported submission formats.',
            'instruction_text.required_if' => 'Add clear instructions for the submission task.',
            'questions.*.question_image.image' => 'Each question image must be a valid image.',
            'questions.*.question_image.mimes' => 'Question images must be JPG, PNG, or WebP files.',
            'questions.*.question_image.max' => 'Question images must be 5 MB or smaller.',
            'questions.*.question_text.required' => 'Every quiz question needs question text.',
            'questions.*.marks.numeric' => 'Every quiz question must have valid numeric marks.',
            'questions.*.marks.min' => 'Quiz question marks cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->attributes->set(
            'assessment_questions_supplied',
            $this->has('questions')
        );
        $this->attributes->set(
            'assessment_scoring_fields_supplied',
            collect([
                'time_limit_minutes',
                'max_attempts',
                'weight_percent',
                'total_marks',
            ])->filter(fn (string $field): bool => $this->has($field))->values()->all()
        );

        $type = strtolower(trim((string) $this->input('type')));
        $questions = collect($type === 'submission' ? [] : $this->input('questions', []))
            ->map(function ($question): mixed {
                if (! is_array($question)) {
                    return $question;
                }

                foreach (['question_text', 'correct_answer', 'grading_rubric'] as $field) {
                    if (array_key_exists($field, $question) && is_string($question[$field])) {
                        $question[$field] = trim($question[$field]);
                    }
                }

                return $question;
            })
            ->all();

        $this->merge([
            'type' => $type,
            'title' => trim((string) $this->input('title', '')),
            'instruction_text' => $this->filled('instruction_text')
                ? trim((string) $this->input('instruction_text'))
                : null,
            'questions' => $questions,
            'accepted_format' => $type === 'submission'
                ? ($this->filled('accepted_format') ? $this->input('accepted_format') : null)
                : null,
            'time_limit_minutes' => $type === 'quiz' ? $this->input('time_limit_minutes') : null,
            'max_attempts' => $type === 'quiz' ? $this->input('max_attempts') : null,
            'weight_percent' => $type === 'quiz' ? $this->input('weight_percent') : null,
            'total_marks' => $type === 'quiz' ? $this->input('total_marks') : null,
        ]);
    }
}
