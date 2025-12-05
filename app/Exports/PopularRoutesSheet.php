<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PopularRoutesSheet implements FromArray, WithHeadings, WithStyles, WithTitle
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
                'route' => $item['route'],
                'order_count' => $item['order_count'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '排名',
            '區域路線',
            '訂單數',
        ];
    }

    public function title(): string
    {
        return '區域路線統計';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
