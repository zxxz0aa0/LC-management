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

        // 簡化格式只匯出特定狀態的訂單
        $query->whereIn('status', ['open', 'assigned', 'bkorder']);

        // 只匯出主訂單（避免共乘重複）
        $query->where('is_main_order', true);

        // 如果有篩選參數，應用篩選條件
        if ($this->request) {
            // 先應用通用篩選條件（如果有的話）
            $query = $query->filter($this->request);

            // 取得篩選模式
            $filterMode = $this->request->input('filter_mode', 'created_at');

            // 根據篩選模式應用不同的篩選條件
            switch ($filterMode) {
                case 'created_at':
                    // 只篩選建立時間
                    if ($this->request->has('created_at_start') && $this->request->has('created_at_end')) {
                        $query->whereBetween('created_at', [
                            $this->request->input('created_at_start'),
                            $this->request->input('created_at_end'),
                        ]);
                    }
                    break;

                case 'ride_date':
                    // 只篩選用車日期
                    if ($this->request->has('ride_date')) {
                        $query->whereDate('ride_date', $this->request->input('ride_date'));
                    }
                    break;

                case 'both':
                    // 同時篩選建立時間和用車日期（AND 條件）
                    if ($this->request->has('created_at_start') && $this->request->has('created_at_end')) {
                        $query->whereBetween('created_at', [
                            $this->request->input('created_at_start'),
                            $this->request->input('created_at_end'),
                        ]);
                    }
                    if ($this->request->has('ride_date')) {
                        $query->whereDate('ride_date', $this->request->input('ride_date'));
                    }
                    break;
            }
        }

        // 根據篩選模式決定排序方式
        if ($this->request && $this->request->input('filter_mode') === 'created_at') {
            $query->orderBy('created_at', 'desc');
        } elseif ($this->request && $this->request->input('filter_mode') === 'ride_date') {
            $query->orderBy('ride_date', 'desc')->orderBy('ride_time', 'desc');
        } elseif ($this->request && $this->request->input('filter_mode') === 'both') {
            // 兩者都要時，優先依建立時間排序，再依用車日期
            $query->orderBy('created_at', 'desc')->orderBy('ride_date', 'desc');
        } else {
            $query->orderBy('ride_date', 'desc')->orderBy('ride_time', 'desc');
        }

        return $query->get()
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
                    'origin_address' => $this->formatAddress($order->pickup_address, $order->pickup_district, $order->carpool_member_count > 1),
                    'dest_area' => $order->dropoff_district,
                    'dest_address' => $this->formatAddress($order->dropoff_address, $order->dropoff_district),
                    'remark' => $order->remark ?: '',
                    'assigned_user_id' => $order->driver_fleet_number ?: '',
                    'special_status' => $this->mapSpecialStatus($order->special_status),
                    'wheelchair' => $order->wheelchair,
                    'stair_machine' => $order->stair_machine,
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
            '輪椅',
            '爬梯機',
        ];
    }

    /**
     * 安全地修剪多位元組字符串的空白符號
     * 使用正則表達式處理，避免 trim() 的邊界問題
     *
     * @param  string  $str  要修剪的字符串
     * @return string 修剪後的字符串
     */
    private function mbTrim($str)
    {
        if (empty($str)) {
            return '';
        }

        // 移除首尾的空白符號（包括所有 Unicode 空白）
        return preg_replace('/^\s+|\s+$/u', '', $str);
    }

    private function formatAddress($fullAddress, $district, $isCarpool = false)
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

        // 3) 清理前後多餘符號/空白（使用 mbTrim 避免多位元組字符截斷問題）
        // 舊代碼: $address = trim($address, " \t\n\r\0\x0B-、，,／/");
        // 問題: trim() 會在字節級別處理，可能截斷 UTF-8 字符（如「區」字的最後字節 0x80）
        $address = $this->mbTrim($address);

        // 4) 如果是共乘訂單，在地址前添加標記
        if ($isCarpool) {
            $address = '(共乘)'.$address;
        }

        // 5) 限制地址長度，避免 Excel 儲存格問題（最大 255 字元，安全起見設為 200）
        if (mb_strlen($address) > 200) {
            $address = mb_substr($address, 0, 197).'...';
        }

        return $this->mbTrim($address);
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
