<div id="orders-list" class="table-responsive p-3">
    <table id="order-table" class="table table-bordered table-hover align-middle" style="width:100%">
        <thead class="table-success">
            <tr>
                <th class="align-middle text-center" style="width:5%">客戶姓名</th>
                <th class="align-middle text-center" style="width:5%">用車日期</th>
                <th class="align-middle text-center" style="width:5%">用車時間</th>
                <th class="align-middle text-center" style="width:20%">上車地址</th>
                <th class="align-middle text-center" style="width:20%">下車地址</th>
                <th class="align-middle text-center" style="width:6%">爬梯機</th>
                <th class="align-middle text-center" style="width:5%">特殊單</th>
                <th class="align-middle text-center" style="width:5%">車隊編號</th>
                <th class="align-middle text-center" style="width:5%">訂單狀態</th>
                <th class="align-middle text-center" style="width:12%">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>{{ $order->customer_name }}</td>
                <td>{{ $order->ride_date ? \Carbon\Carbon::parse($order->ride_date)->format('m/d') : 'N/A' }}</td>
                <td>{{ $order->ride_time ? \Carbon\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}</td>
                <td>{{ $order->pickup_address }}</td>
                <td>{{ $order->dropoff_address }}</td>
                <td>
                    @if($order->stair_machine == 1)
                        爬梯單
                    @endif
                </td>
                <td>
                    @switch($order->special_status)
                    @case('一般')
                        <span class="badge bg-success">一般</span>
                        @break
                    @case('VIP')
                        <span class="badge bg-pink">VIP</span>
                        @break
                    @case('個管單')
                        <span class="badge bg-pink">個管單</span>
                        @break
                    @default
                        <span class="badge bg-light text-dark" >未知狀態</span>
                @endswitch
                </td>
                <td>{{ $order->driver_fleet_number }}</td>
                <td>
                    @switch($order->status)
                        @case('open')
                            <span class="badge bg-success">可派遣</span>
                            @break
                        @case('assigned')
                            <span class="badge bg-primary">已指派</span>
                            @break
                        @case('replacement')
                            <span class="badge bg-warning">已後補</span>
                            @break
                        @case('blocked')
                            <span class="badge bg-danger">黑名單</span>
                            @break
                        @case('cancelled')
                            <span class="badge bg-danger">已取消</span>
                            @break
                        @default
                            <span class="badge bg-light text-dark">未知狀態</span>
                    @endswitch
                </td>
                <td>
                    <button type="button" class="btn btn-info btn-sm view-order-btn" data-order-id="{{ $order->id }}">檢視</button>
                    <button type="button" class="btn btn-sm btn-primary edit-order-btn" data-id="{{ $order->id }}">編輯</button>
                    {{-- 刪除按鈕可以之後再補上 --}}
                </td>
            </tr>
            @empty

            @endforelse
        </tbody>
    </table>