<?php

namespace App\Http\Requests;

use App\Rules\UniqueOrderDateTime;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 我們使用 Laravel 的 policies 來做授權，這裡先設為 true
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_id_number' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:255',
            'customer_id' => 'required|integer',
            'ride_date' => 'required|date',
            'ride_time' => [
                'required',
                'date_format:H:i',
                new UniqueOrderDateTime(
                    $this->input('customer_id'),
                    $this->input('ride_date'),
                    $this->input('back_time'),
                    $this->route('order')->id ?? null
                ),
            ],
            'back_time' => 'nullable|date_format:H:i',
            'pickup_address' => [
                'required',
                'string',
                'regex:/^(.+市|.+縣)(.+區|.+鄉|.+鎮).+$/u',
            ],
            'dropoff_address' => [
                'required',
                'string',
                'regex:/^(.+市|.+縣)(.+區|.+鄉|.+鎮).+$/u',
            ],
            'status' => 'required|in:open,assigned,replacement,blocked,cancelled',
            'companions' => 'required|integer|min:0',
            'order_type' => 'required|string',
            'service_company' => 'required|string',
            'wheelchair' => 'required|in:是,否,未知',
            'stair_machine' => 'required|in:是,否,未知',
            'remark' => 'nullable|string',
            'created_by' => 'required|string',
            'identity' => 'nullable|string',
            'carpoolSearchInput' => 'nullable|string',
            'special_status' => 'nullable|string',
            'carpool_customer_id' => 'nullable|integer',
            'carpool_id_number' => 'nullable|string',
            'driver_id' => 'nullable|integer',
            'driver_name' => 'nullable|string',
            'driver_plate_number' => 'nullable|string',
            'driver_fleet_number' => 'nullable|string',
            'carpool_phone_number' => 'nullable|string',
            'carpool_addresses' => 'nullable|string',
            'carpool_with' => 'nullable|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pickup_address.regex' => '上車地址必須包含「市/縣」與「區/鄉/鎮」',
            'dropoff_address.regex' => '下車地址必須包含「市/縣」與「區/鄉/鎮」',
            'back_time.date_format' => '回程時間格式錯誤，請使用 HH:MM 格式',
        ];
    }
}
