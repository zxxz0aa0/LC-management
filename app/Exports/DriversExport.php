<?php

namespace App\Exports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DriversExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Driver::all()->map(function ($driver) {
            return [
                'name' => $driver->name,
                'phone' => $driver->phone,
                'id_number' => $driver->id_number,
                'fleet_number' => $driver->fleet_number ?? '',
                'plate_number' => $driver->plate_number ?? '',
                'car_brand' => $driver->car_brand ?? '',
                'car_vehicle_style' => $driver->car_vehicle_style ?? '',
                'car_color' => $driver->car_color ?? '',
                'lc_company' => $driver->lc_company ?? '',
                'order_type' => $driver->order_type ?? '',
                'service_type' => $driver->service_type ?? '',
                'status' => $this->getStatusText($driver->status),
                'created_at' => $driver->created_at->format('Y-m-d H:i:s'),
            ];
        });
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
            '建立時間',
        ];
    }

    private function getStatusText($status)
    {
        $statusMap = [
            'active' => '在職',
            'inactive' => '離職',
            'blacklist' => '黑名單',
        ];

        return $statusMap[$status] ?? $status;
    }
}
