<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEvent extends Model
{
    protected $fillable = ['customer_id', 'event_date', 'event', 'created_by'];

    // 每筆事件紀錄屬於一位客戶
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 建立這筆事件的使用者（建立人）
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
