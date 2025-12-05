<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyTrendsSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];

        foreach ($this->data['monthly_data'] as $item) {
            $result[] = [
                'month' => $item['month_name'],
                'order_count' => $item['order_count'],
                'assigned_count' => $item['assigned_count'],
                'open_count' => $item['open_count'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '月份',
            '總訂單數',
            '已指派',
            '待派遣',
        ];
    }

    public function title(): string
    {
        return '月份趨勢';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
