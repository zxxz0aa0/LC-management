<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Order::with(['customer', 'driver']);

        // 如果有篩選參數，應用篩選條件
        if ($this->request) {
            $query = $query->filter($this->request);
        }

        return $query->orderBy('ride_date', 'desc')
            ->orderBy('ride_time', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_id_number' => $order->customer_id_number,
                    'order_type' => $order->order_type,
                    'service_company' => $order->service_company,
                    'ride_date' => $order->ride_date ? $order->ride_date->format('Y-m-d') : '',
                    'ride_time' => $order->ride_time ? \Carbon\Carbon::parse($order->ride_time)->format('H:i') : '',
                    'pickup_county' => $order->pickup_county,
                    'pickup_district' => $order->pickup_district,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_county' => $order->dropoff_county,
                    'dropoff_district' => $order->dropoff_district,
                    'dropoff_address' => $order->dropoff_address,
                    'wheelchair' => $order->wheelchair,
                    'stair_machine' => $order->stair_machine,
                    'companions' => $order->companions,
                    'carpool_name' => $order->carpool_name,
                    'carpool_id' => $order->carpool_id,
                    'driver_name' => $order->driver_name,
                    'driver_fleet_number' => $order->driver_fleet_number,
                    'driver_plate_number' => $order->driver_plate_number,
                    'status' => $this->getStatusText($order->status),
                    'special_status' => $order->special_status,
                    'remark' => $order->remark,
                    'created_by' => $order->created_by,
                    'identity' => $order->identity,
                    'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            '客戶姓名',
            '客戶電話',
            '客戶身分證',
            '訂單類型',
            '服務公司',
            '用車日期',
            '用車時間',
            '上車縣市',
            '上車區域',
            '上車地址',
            '下車縣市',
            '下車區域',
            '下車地址',
            '輪椅',
            '爬梯機',
            '陪同人數',
            '共乘姓名',
            '共乘身分證',
            '駕駛姓名',
            '駕駛隊編',
            '車牌號碼',
            '訂單狀態',
            '特殊狀態',
            '備註',
            '建立者',
            '身份別',
            '建立時間',
        ];
    }

    private function getStatusText($status)
    {
        $statusMap = [
            'open' => '可派遣',
            'assigned' => '已指派',
            'replacement' => '候補',
            'cancelled' => '已取消',
        ];

        return $statusMap[$status] ?? $status;
    }
}
