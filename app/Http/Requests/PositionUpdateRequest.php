<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PositionUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'code'               =>      ['required', 'unique:positions,code,' . $this->position->code . ',code'],
            'position_name'      =>      ['required', 'string', 'max:255', 'unique:positions,position_name,' . $this->position->code . ',code'],
        ];
    }
}
