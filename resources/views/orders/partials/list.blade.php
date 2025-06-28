{{-- resources/views/orders/partials/list.blade.php --}}
<table id="order-table" class="table table-bordered table-hover align-middle">
    <thead>
        <tr>
                    <th style="width:10%">編號</th>
                    <th style="width:6%">客戶姓名</th>
                    <th style="width:6%">用車日期</th>
                    <th style="width:6%">用車時間</th>
                    <th style="width:20%">上車地址</th>
                    <th style="width:20%">下車地址</th>
                    <th style="width:6%">特殊狀態</th>
                    <th style="width:6%">訂單狀態</th>
                    <th style="width:6%">建單人員</th>
                    <th>操作</th>
        </tr>
    </thead>
    <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->ride_date }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->ride_time)->format('H:i') }}</td>
                    <td>{{ $order->pickup_address }}</td>
                    <td>{{ $order->dropoff_address }}</td>
                    <td>{{ $order->special_order }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->created_by }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">檢視</a>
                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-warning">編輯</a>
                        {{-- 刪除按鈕可以之後再補上 --}}
                    </td>
                </tr>
                @empty

                @endforelse
    </tbody>
</table>
