<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerServiceStatisticsExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new OrderCountByUserSheet($this->data['order_count_by_user']),
            new OrderTypesByUserSheet($this->data['order_types_by_user']),
            new OrdersByHourSheet($this->data['orders_by_hour']),
            new OrderTypeSummarySheet($this->data['order_type_summary']),
            new StatusDistributionSheet($this->data['status_distribution']),
        ];
    }
}

/**
 * 人員建單總數工作表
 */
class OrderCountByUserSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];

        foreach ($this->data as $item) {
            $result[] = [
                'rank' => $item['rank'],
                'user_name' => $item['user_name'],
                'total_orders' => $item['total_orders'],
                'unique_customers' => $item['unique_customers'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '排名',
            '建單人員',
            '總訂單數',
            '獨特客戶數',
        ];
    }

    public function title(): string
    {
        return '人員建單總數';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

/**
 * 人員當天/預約訂單工作表
 */
class OrderTypesByUserSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];

        foreach ($this->data['users_data'] as $item) {
            $result[] = [
                'user_name' => $item['user_name'],
                'same_day_orders' => $item['same_day_orders'],
                'advance_orders' => $item['advance_orders'],
                'total' => $item['same_day_orders'] + $item['advance_orders'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '建單人員',
            '當天訂單',
            '預約訂單',
            '總計',
        ];
    }

    public function title(): string
    {
        return '人員當天預約訂單';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

/**
 * 每小時建單數量工作表
 */
class OrdersByHourSheet implements FromArray, WithHeadings, WithStyles, WithTitle
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
                'hour' => $item['hour'].'時',
                'order_count' => $item['order_count'],
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            '時段',
            '訂單數',
        ];
    }

    public function title(): string
    {
        return '每小時建單數量';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

/**
 * 當天/預約訂單總數工作表
 */
class OrderTypeSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
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
                'type' => '當天訂單',
                'count' => $this->data['same_day_count'],
                'percentage' => $this->data['same_day_percentage'].'%',
            ],
            [
                'type' => '預約訂單',
                'count' => $this->data['advance_count'],
                'percentage' => $this->data['advance_percentage'].'%',
            ],
            [
                'type' => '總計',
                'count' => $this->data['total_count'],
                'percentage' => '100%',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            '訂單類型',
            '訂單數',
            '占比',
        ];
    }

    public function title(): string
    {
        return '當天預約訂單總數';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

/**
 * 訂單狀態分布工作表
 */
class StatusDistributionSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];

        foreach ($this->data['status_data'] as $item) {
            $result[] = [
                'status' => $item['status'],
                'order_count' => $item['order_count'],
                'percentage' => $item['percentage'].'%',
            ];
        }

        // 新增總計行
        $result[] = [
            'status' => '總計',
            'order_count' => $this->data['total_count'],
            'percentage' => '100%',
        ];

        return $result;
    }

    public function headings(): array
    {
        return [
            '訂單狀態',
            '訂單數',
            '占比',
        ];
    }

    public function title(): string
    {
        return '訂單狀態分布';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
