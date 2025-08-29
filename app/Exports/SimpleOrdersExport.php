<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SimpleOrdersExport implements FromCollection, WithHeadings
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
                    'order_code' => $order->order_number,
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'unit_number' => $order->customer_id_number ? (($order->order_type === '新北長照' ? 'NT' : ($order->order_type === '台北長照' ? 'TP' : '')).substr($order->customer_id_number, -5)) : '',
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
            '身分證',
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
        if (empty($fullAddress)) {
            return '';
        }

        $address = $fullAddress;

        // 1) 若地址中已含指定區名，先只移除「第一次」出現，避免路名誤刪
        if (! empty($district) && str_contains($address, $district)) {
            $safeDistrict = preg_quote($district, '/');
            $address = preg_replace('/'.$safeDistrict.'/', '', $address, 1);
        }

        // 2) 無論是否有區名，一律移除開頭的「XXX市 / XXX縣」
        //    例：新北市板橋區XX路100號 -> 板橋區XX路100號
        //        宜蘭縣羅東鎮中山路一段10號 -> 羅東鎮中山路一段10號
        $address = preg_replace('/^[\p{Han}]+(?:縣|市)/u', '', $address);

        // 3) 清理前後多餘符號/空白
        $address = trim($address, " \t\n\r\0\x0B-、，,／/");

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
