<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlannedMealEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date'],
            'entries' => ['required', 'array'],
            'entries.*.food_name' => ['nullable', 'string', 'max:100'],
            'entries.*.grams' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'entries.*.calories' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'entries.*.protein' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'entries.*.carbs' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'entries.*.fat' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $entries = collect($this->input('entries', []));
                $hasAtLeastOneEntry = false;

                foreach ($entries as $mealSlot => $entry) {
                    $foodName = trim((string) ($entry['food_name'] ?? ''));
                    $grams = $entry['grams'] ?? null;
                    $calories = $entry['calories'] ?? null;
                    $protein = $entry['protein'] ?? null;
                    $carbs = $entry['carbs'] ?? null;
                    $fat = $entry['fat'] ?? null;
                    $hasAnyValue = $foodName !== '' || $grams !== null || $calories !== null || $protein !== null || $carbs !== null || $fat !== null;

                    if (! $hasAnyValue) {
                        continue;
                    }

                    $hasAtLeastOneEntry = true;

                    if ($foodName === '' || $grams === null || $calories === null || $protein === null || $carbs === null || $fat === null) {
                        $validator->errors()->add("entries.{$mealSlot}", 'Each planned meal needs a food name, grams, calories, protein, carbs, and fat.');
                    }
                }

                if (! $hasAtLeastOneEntry) {
                    $validator->errors()->add('entries', 'Add at least one meal for the selected day.');
                }
            },
        ];
    }
}
