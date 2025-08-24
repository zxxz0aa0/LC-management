<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SimpleOrdersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Order::with(['customer', 'driver'])
            ->orderBy('ride_date', 'desc')
            ->orderBy('ride_time', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'order_code' => $order->order_number,
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'unit_number' => $order->customer_id_number ?: $order->order_number,
                    'type' => $order->order_type,
                    'date' => $order->ride_date ? $order->ride_date->format('Y-m-d') : '',
                    'time' => $order->ride_time ? \Carbon\Carbon::parse($order->ride_time)->format('H:i') : '',
                    'origin_area' => $order->pickup_district,
                    'origin_address' => $this->formatAddress($order->pickup_address, $order->pickup_district),
                    'dest_area' => $order->dropoff_district,
                    'dest_address' => $this->formatAddress($order->dropoff_address, $order->dropoff_district),
                    'remark' => $order->remark ?: '',
                    'assigned_user_id' => $order->driver_fleet_number ?: '',
                    'special_status' => $this->mapSpecialStatus($order->special_status),
                ];
            });
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

    private function formatAddress($fullAddress, $district)
    {
        if (empty($fullAddress)) return '';
        
        // 如果地址已經包含區域，移除重複的區域部分
        $address = $fullAddress;
        if (!empty($district) && str_contains($address, $district)) {
            $address = str_replace($district, '', $address);
            $address = preg_replace('/^[縣市]+/', '', $address);
        }
        
        return trim($address);
    }

    private function mapSpecialStatus($status)
    {
        // 對應到規格文件的特殊狀態格式
        $statusMap = [
            '一般' => '',
            '網頁單' => '網頁',
            'Line' => 'Line',
            '個管單' => '個管',
            '黑名單' => '黑名單',
            '共乘單' => '共乘',
        ];
        
        return $statusMap[$status] ?? '';
    }
}