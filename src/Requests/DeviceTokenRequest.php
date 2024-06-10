<?php

namespace Tocaan\FcmFirebase\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceTokenRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            "user_id" => "nullable|exists:users,id",
            "device_token" => "nullable",
            "platform" => "nullable|in:IOS,ANDROID",
            "lang"    => "nullable",
            "model"   => "nullable",
            "app_version" => "nullable",
            "os_version"  => "nullable"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
