<div class="card">
    <div class="card-header bg-info">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>訂單列表
            </h5>
            <!--<a href="{{ route('orders.create') }}" class="btn btn-primary" id="createOrderBtn">
                <i class="fas fa-plus me-2"></i>新增訂單
            </a>-->
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead class="table-secondary">
                    <tr>
                        <th>客戶姓名</th>
                        <th>電話</th>
                        <th>用車日期</th>
                        <th>用車時間</th>
                        <th>上車地址</th>
                        <th>下車地址</th>
                        <th>共乘姓名</th>
                        <th>特殊狀態</th>
                        <th>駕駛</th>
                        <th>訂單狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->customer_phone }}</td>
                        <td>{{ $order->ride_date ? (is_string($order->ride_date) ? $order->ride_date : $order->ride_date->format('Y-m-d')) : 'N/A' }}</td>
                        <td>{{ $order->ride_time ? \Illuminate\Support\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}</td>
                        <td>{{ Str::limit($order->pickup_address, 30) }}</td>
                        <td>{{ Str::limit($order->dropoff_address, 30) }}</td>
                        <td>{{ $order->carpool_name }}</td>
                        <td>
                            @if($order->stair_machine == '是')
                                <span class="badge bg-warning">爬梯機</span>
                            @elseif($order->stair_machine == '未知')
                                <span class="badge bg-secondary">爬梯機未知</span>
                            @endif
                            @if($order->wheelchair == '是')
                                <span class="badge bg-info">輪椅</span>
                            @elseif($order->wheelchair == '未知')
                                <span class="badge bg-secondary">輪椅未知</span>
                            @endif
                            @switch($order->special_status)
                                @case('一般')
                                    <span class="badge bg-success"></span>
                                    @break
                                @case('網頁單')
                                    <span class="badge bg-danger">網頁單</span>
                                    @break
                                @case('個管單')
                                    <span class="badge bg-info">個管單</span>
                                    @break
                                @case('黑名單')
                                    <span class="badge bg-dark">黑名單</span>
                                    @break
                                @case('共乘')
                                    <span class="badge bg-primary">共乘</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                        <td>{{ $order->driver_fleet_number ?: '未指派' }}</td>
                        <td>
                            @switch($order->status)
                                @case('open')
                                    <span class="badge bg-success">可派遣</span>
                                    @break
                                @case('assigned')
                                    <span class="badge bg-primary">已指派</span>
                                    @break
                                @case('replacement')
                                    <span class="badge bg-warning">候補</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">已取消</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('orders.show', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id']))) }}" class="btn btn-info btn-sm" title="檢視">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('orders.edit', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id']))) }}" class="btn btn-warning btn-sm" title="編輯">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <!--<button type="button" class="btn btn-danger btn-sm" onclick="deleteOrder({{ $order->id }})" title="刪除">
                                    <i class="fas fa-trash"></i>
                                </button>-->
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="no-data-row">
                        <td colspan="10" class="text-center">
                            <div class="py-4">
                                <i class="fas fa-inbox text-muted mb-2" style="font-size: 3rem;"></i>
                                <p class="text-muted mb-0">目前沒有訂單資料</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 分頁 --}}
        @if(method_exists($orders, 'links'))
            <div class="d-flex justify-content-center mt-4">
                {{ $orders->appends(request()->only(['keyword', 'start_date', 'end_date', 'customer_id']))->links() }}
            </div>
        @endif
    </div>
</div>
