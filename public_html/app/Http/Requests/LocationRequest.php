<?php

namespace App\Http\Requests;

use App\Utils\ErrorCodeUtils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class LocationRequest extends FormRequest {
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
            'county_id' => 'required|exists:counties,id',
            'administration_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'longitude' => 'nullable|numeric|max:180|min:-180',
            'latitude' => 'nullable|numeric|max:90|min:-90',
            'business_hours' => 'nullable|string|max:255',
            'image' => 'nullable|max:4096|file|image'
        ];
    }

    /**
     * Get the update validation rules.
     *
     * @return array
     */
    public function updateRules(): array {
        return [
            'county_id' => 'required|exists:counties,id',
            'administration_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'longitude' => 'nullable|numeric|max:180|min:-180',
            'latitude' => 'nullable|numeric|max:90|min:-90',
            'business_hours' => 'nullable|string|max:255',
            'image' => 'nullable|max:4096|file|image'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'county_id' => trans('messages.location_county'),
            'administration_id' => trans('messages.location_administration'),
            'name' => trans('messages.location_name'),
            'address' => trans('messages.location_address'),
            'longitude' => trans('messages.location_longitude'),
            'latitude' => trans('messages.location_latitude'),
            'business_hours' => trans('messages.location_business_hours'),
            'image' => trans('messages.location_image')
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors()));
    }
}
