<?php

namespace App\Http\Requests;

use App\Utils\ErrorCodeUtils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class RelativeHumidityRequest extends FormRequest {
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
            'is_notification' => ['required', 'boolean']
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
            'is_notification' => ['required', 'boolean']
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'name' => trans('messages.sensor_name'),
            'is_notification' => trans('messages.is_notification')
        ];
    }

    protected function failedValidation(Validator $validator) {
        throw new ValidationException($validator, ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors()));
    }
}