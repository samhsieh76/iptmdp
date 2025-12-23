<?php

namespace App\Http\Requests;

use App\Utils\ErrorCodeUtils;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UserRequest extends FormRequest {
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
            'username' => ['required','string', 'min:3', 'max:16', 'alpha_dash', 'regex:/[A-Za-z0-9]+$/', Rule::unique('users')],
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
            'name' => 'required|string|max:45',
            'email' => ['required', 'email'/* , Rule::unique('users') */],
            'phone' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
            'parent_id' => 'nullable|exists:users,id'
        ];
    }

    /**
     * Get the update validation rules.
     *
     * @return array
     */
    public function updateRules(): array {
        return [
            'password' => 'nullable|string|min:6',
            'confirm_password' => 'required_with:password|same:password',
            'name' => 'required|string|max:45',
            'email' => ['required', 'email'/* , Rule::unique('users')->ignore($this->route('user')) */],
            'phone' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
            'parent_id' => 'nullable|exists:users,id'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes() {
        return [
            'username' => trans('messages.username'),
            'password' => trans('messages.password'),
            'confirm_password' => trans('messages.confirm_password'),
            'name' => trans('messages.user_name'),
            'email' => trans('messages.user_email'),
            'phone' => trans('messages.user_phone'),
            'role_id' => trans('messages.user_role'),
            'parent_id' => trans('messages.user_parent')
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors()));
    }
}
