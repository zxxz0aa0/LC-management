<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'district',
        'category',
        'description',
        'coordinates',
        'is_active',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'is_active' => 'boolean',
    ];

    // 地標分類常數
    const CATEGORIES = [
        'medical' => '醫療',
        'transport' => '交通',
        'education' => '教育',
        'government' => '政府機關',
        'commercial' => '商業',
        'general' => '一般',
    ];

    // 搜尋範圍
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('address', 'like', "%{$keyword}%")
              ->orWhere('city', 'like', "%{$keyword}%")
              ->orWhere('district', 'like', "%{$keyword}%");
        });
    }

    // 只取啟用的地標
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 依分類篩選
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // 熱門排序
    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    // 增加使用次數
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    // 取得分類中文名稱
    public function getCategoryNameAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    // 取得完整地址（包含城市區域）
    public function getFullAddressAttribute()
    {
        return $this->city . $this->district . $this->address;
    }
}
