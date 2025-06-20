<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'id_number',
        'birthday',
        'gender',
        'phone_number',
        'addresses',
        'contact_person',
        'contact_phone',
        'contact_relationship',
        'email',
        'wheelchair',
        'stair_climbing_machine',
        'ride_sharing',
        'identity',
        'note',
        'a_mechanism',
        'a_manager',
        'special_status',
        'county_care',   
        'service_company',  
        'created_by',
        'updated_by',
    ];

    // 自動將 json 欄位轉換為 array
    protected $casts = [
        'phone_number' => 'array',
        'addresses' => 'array',
    ];

    // 定義與 User 事件關聯
    public function events()
    {
        return $this->hasMany(CustomerEvent::class);
    }
}
// 注意：此模型假設已經有對應的資料表 'customers'，並且資料表結構符合上述的欄位定義。
