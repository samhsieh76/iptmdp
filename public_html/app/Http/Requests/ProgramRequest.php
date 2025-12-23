<?php

namespace App\Http\Requests;

use App\Utils\ErrorCodeUtils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ProgramRequest extends FormRequest {
    /**
     * Check if it's a PUT request.
     *
     * @var bool $isUpdating
     */
    protected $isUpdating = false;

    public function validationData() {
        $this->isUpdating = $this->isMethod('put');
        return $this->all();
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return $this->isUpdating ? $this->updateRules() : $this->createRules();
    }

    /**
     * Get the create validation rules.
     *
     * @return array
     */
    public function createRules(): array {
        return [
            'name' => 'required|string|max:45',
            'display_name' => 'required|string|max:45'
        ];
    }

    /**
     * Get the update validation rules.
     *
     * @return array
     */
    public function updateRules(): array {
        return [
            'name' => 'required|string|max:45',
            'display_name' => 'required|string|max:45'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'name' => trans('messages.program_name'),
            'display_name' => trans('messages.program_display_name')
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors()));
    }
}
