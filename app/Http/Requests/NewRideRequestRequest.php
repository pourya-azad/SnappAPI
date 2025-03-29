<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewRideRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pickup_latitude'=> 'required|numeric',
            'pickup_longitude'=> 'required|numeric',
            'dest_latitude'=> 'required|numeric',
            'dest_longitude'=> 'required|numeric',
            'user_id'=> 'required|exists:users,id',
        ];
    }
}
