<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PeakHoursSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];

        foreach ($this->data['hourly_data'] as $item) {
            $result[] = [
                'hour' => $item['hour'],
                'order_count' => $item['order_count'],
                'unique_customers' => $item['unique_customers'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '時段',
            '訂單數',
            '獨特客戶數',
        ];
    }

    public function title(): string
    {
        return '尖峰時段分析';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
