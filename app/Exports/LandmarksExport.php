<?php

namespace App\Exports;

use App\Models\Landmark;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LandmarksExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Landmark::all()->map(function ($landmark) {
            return [
                'name' => $landmark->name,
                'address' => $landmark->address,
                'city' => $landmark->city,
                'district' => $landmark->district,
                'category' => $landmark->category_name, // 使用中文分類名稱
                'description' => $landmark->description ?? '',
                'longitude' => $landmark->coordinates['lng'] ?? '',
                'latitude' => $landmark->coordinates['lat'] ?? '',
                'is_active' => $landmark->is_active ? '1' : '0',
                'usage_count' => $landmark->usage_count,
                'created_by' => $landmark->created_by ?? '',
                'created_at' => $landmark->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            '地標名稱',
            '地址',
            '城市',
            '區域',
            '分類',
            '描述',
            '經度',
            '緯度',
            '是否啟用',
            '使用次數',
            '建立者',
            '建立時間',
        ];
    }
}