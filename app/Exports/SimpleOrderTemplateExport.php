<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SimpleOrderTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'order_code' => 'ORD001',
                'name' => '王小明',
                'phone' => '0912345678',
                'unit_number' => 'A001',
                'type' => '新北長照',
                'date' => '2025-08-24',
                'time' => '14:30',
                'origin_area' => '板橋區',
                'origin_address' => '民族路100號',
                'dest_area' => '新莊區',
                'dest_address' => '中正路200號',
                'remark' => '輪椅乘客',
                'assigned_user_id' => '',
                'special_status' => '',
            ],
            [
                'order_code' => 'ORD002',
                'name' => '李小華',
                'phone' => '0923456789',
                'unit_number' => 'A002',
                'type' => '新北長照',
                'date' => '2025-08-24',
                'time' => '15:00',
                'origin_area' => '三重區',
                'origin_address' => '重新路50號',
                'dest_area' => '蘆洲區',
                'dest_address' => '中山路300號',
                'remark' => '',
                'assigned_user_id' => 'D001',
                'special_status' => '',
            ],
            [
                'order_code' => '訂單編號（必填，不可重複）',
                'name' => '姓名（必填）',
                'phone' => '電話（必填）',
                'unit_number' => '編號（必填）',
                'type' => '類型（必填）',
                'date' => '日期（必填，格式：YYYY-MM-DD）',
                'time' => '時間（必填，格式：HH:MM）',
                'origin_area' => '上車區（必填）',
                'origin_address' => '上車地址（必填）',
                'dest_area' => '下車區（必填）',
                'dest_address' => '下車地址（必填）',
                'remark' => '備註（可空白）',
                'assigned_user_id' => '隊員編號（可空白，有值=已指派，無值=待搶單）',
                'special_status' => '特殊狀態（可空白，網頁/Line/個管/黑名單/共乘）',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            '姓名',
            '電話',
            '編號',
            '類型',
            '日期',
            '時間',
            '上車區',
            '上車地址',
            '下車區',
            '下車地址',
            '備註',
            '隊員編號',
            '特殊狀態',
        ];
    }
}
