<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name', // 姓名
        'id_number', // 身份證號
        'birthday', // 生日
        'gender', // 性別
        'phone_number', // 電話號碼
        'addresses', // 地址
        'contact_person', // 緊急聯絡人
        'contact_phone', // 緊急聯絡電話
        'contact_relationship', // 緊急聯絡人關係
        'email', // 電子郵件
        'wheelchair', // 輪椅
        'stair_climbing_machine', // 登樓機
        'ride_sharing', // 共乘
        'identity', // 身份別
        'note', // 備註
        'a_mechanism', // 機構
        'a_manager', // 個管師
        'special_status', // 特殊狀態
        'county_care',   // 縣市照顧
        'service_company',  // 服務公司
        'referral_date', // 照會日期
        'created_by', // 建立者
        'updated_by', // 更新者
        'status', // 狀態
    ];

    // 自動將 json 欄位轉換為 array
    protected $casts = [
        'phone_number' => 'array',
        'addresses' => 'array',
        'birthday' => 'date',
        'referral_date' => 'date',
    ];

    // 定義與 User 事件關聯
    public function events()
    {
        return $this->hasMany(CustomerEvent::class);
    }

    // 定義與 Order 的關聯
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
// 注意：此模型假設已經有對應的資料表 'customers'，並且資料表結構符合上述的欄位定義。
