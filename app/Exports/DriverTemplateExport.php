<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DriverTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                '姓名' => '王小明',
                '手機' => '0912345678',
                '身分證字號' => 'A123456789',
                '車隊編號' => 'LC001',
                '車牌號碼' => 'ABC-1234',
                '車品牌' => 'Toyota',
                '車輛樣式' => 'Camry',
                '車色' => '白色',
                '所屬公司' => '台北長照服務有限公司',
                '可接訂單種類' => '一般,復康',
                '服務類型' => '短程,長程',
                '狀態' => '在職',
            ],
            [
                '姓名' => '李小華',
                '手機' => '0987654321',
                '身分證字號' => 'B987654321',
                '車隊編號' => 'LC002',
                '車牌號碼' => 'DEF-5678',
                '車品牌' => 'Honda',
                '車輛樣式' => 'Civic',
                '車色' => '黑色',
                '所屬公司' => '新北長照服務有限公司',
                '可接訂單種類' => '復康,輪椅',
                '服務類型' => '短程,中程',
                '狀態' => '在職',
            ],
            [
                '姓名' => '陳小美',
                '手機' => '0955123456',
                '身分證字號' => 'C555666777',
                '車隊編號' => 'LC003',
                '車牌號碼' => 'GHI-9012',
                '車品牌' => 'Nissan',
                '車輛樣式' => 'Sentra',
                '車色' => '銀色',
                '所屬公司' => '桃園長照服務有限公司',
                '可接訂單種類' => '一般',
                '服務類型' => '短程',
                '狀態' => '離職',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            '姓名',
            '手機',
            '身分證字號',
            '車隊編號',
            '車牌號碼',
            '車品牌',
            '車輛樣式',
            '車色',
            '所屬公司',
            '可接訂單種類',
            '服務類型',
            '狀態',
        ];
    }
}
