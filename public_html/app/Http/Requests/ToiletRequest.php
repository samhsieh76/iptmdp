<?php

namespace App\Http\Requests;

use App\Utils\ErrorCodeUtils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ToiletRequest extends FormRequest {
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
            'type' => 'required|numeric',
            'code' => 'required|max:20',
            'name' => 'required|max:20',
            'notification_start' => 'required|date_format:H:i',
            'notification_end' => 'required|date_format:H:i',
            'image' => 'nullable|max:1024|file|image',
            'alert_token' => 'nullable|string',
        ];
    }

    /**
     * Get the update validation rules.
     *
     * @return array
     */
    public function updateRules(): array {
        return [
            'type' => 'required|numeric',
            'code' => 'required|max:20',
            'name' => 'required|max:20',
            'notification_start' => 'required|date_format:H:i',
            'notification_end' => 'required|date_format:H:i',
            'image' => 'nullable|max:1024|file|image',
            'alert_token' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'type' => trans('messages.toilet_type'),
            'code' => trans('messages.toilet_code'),
            'name' => trans('messages.toilet_name'),
            'image' => trans('messages.toilet_image'),
            'notification_start' => trans('messages.toilet_notification_start'),
            'notification_end' => trans('messages.toilet_notification_end'),
            'alert_token' => trans('messages.toilet_alert_token'),
        ];
    }

    protected function failedValidation(Validator $validator) {
        throw new ValidationException($validator, ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors()));
    }
}
