<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CrossCountySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [
            [
                'metric' => '總訂單數',
                'count' => $this->data['total_orders'],
                'percentage' => '100%',
            ],
            [
                'metric' => '同縣市訂單',
                'count' => $this->data['same_county_orders'],
                'percentage' => number_format($this->data['same_county_percentage'], 2).'%',
            ],
            [
                'metric' => '跨縣市訂單',
                'count' => $this->data['cross_county_orders'],
                'percentage' => number_format($this->data['cross_county_percentage'], 2).'%',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            '統計項目',
            '訂單數',
            '占比',
        ];
    }

    public function title(): string
    {
        return '跨縣市訂單統計';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
