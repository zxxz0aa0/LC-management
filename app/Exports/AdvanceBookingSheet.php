<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdvanceBookingSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $categories = $this->data['categories'];
        $percentages = $this->data['percentages'];

        return [
            [
                'category' => '當天預約',
                'order_count' => $categories['same_day'],
                'percentage' => number_format($percentages['same_day_pct'], 2).'%',
            ],
            [
                'category' => '3天內',
                'order_count' => $categories['within_3_days'],
                'percentage' => number_format($percentages['within_3_days_pct'], 2).'%',
            ],
            [
                'category' => '7天內',
                'order_count' => $categories['within_7_days'],
                'percentage' => number_format($percentages['within_7_days_pct'], 2).'%',
            ],
            [
                'category' => '7天以上',
                'order_count' => $categories['more_than_7_days'],
                'percentage' => number_format($percentages['more_than_7_days_pct'], 2).'%',
            ],
            [
                'category' => '平均提前天數',
                'order_count' => number_format($this->data['avg_advance_days'], 2).' 天',
                'percentage' => '-',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            '預約類別',
            '訂單數',
            '占比',
        ];
    }

    public function title(): string
    {
        return '提前預約分析';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
