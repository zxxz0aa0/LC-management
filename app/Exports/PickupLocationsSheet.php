<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PickupLocationsSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];
        $rank = 1;

        foreach ($this->data as $item) {
            $result[] = [
                'rank' => $rank++,
                'area' => $item['area'],
                'order_count' => $item['order_count'],
                'unique_customers' => $item['unique_customers'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '排名',
            '上車區域',
            '訂單數',
            '獨特客戶數',
        ];
    }

    public function title(): string
    {
        return '上車區域統計';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
