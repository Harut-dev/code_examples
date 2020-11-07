<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamsListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page' => 'nullable|integer',
            'pageSize' => 'nullable|integer',
            'status' => 'nullable|integer',
            'name' => 'nullable|string',
            'statuses' => !is_array($this->statuses) ? 'nullable|integer' : 'nullable|array',
            'created_at' => 'nullable|array',
        ];
    }
}
