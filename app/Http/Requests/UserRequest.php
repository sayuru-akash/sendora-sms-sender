<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $targetUser = $this->route('user');

        if ($targetUser instanceof User) {
            return $this->user()->can('update', $targetUser);
        }

        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => ['required', Rule::in(['owner', 'admin', 'manager', 'staff', 'viewer'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
        ];

        if (! $isUpdate) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        } else {
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $actor = $this->user();

                if (! $actor) {
                    return;
                }

                if (! $actor->isOwner() && in_array($this->input('role'), ['owner', 'admin'], true)) {
                    $validator->errors()->add('role', 'Only an owner can create or assign owner and admin roles.');
                }
            },
        ];
    }
}
