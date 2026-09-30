<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [];

        foreach (['prelim', 'midterm', 'finals'] as $key) {
            $rules["terms.{$key}.start_date"] = ['nullable', 'date', "required_with:terms.{$key}.end_date"];
            $rules["terms.{$key}.end_date"] = ['nullable', 'date', "after_or_equal:terms.{$key}.start_date", "required_with:terms.{$key}.start_date"];
        }

        return $rules;
    }
}
