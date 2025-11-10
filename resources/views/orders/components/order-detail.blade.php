<div class="row">
    <div class="col-md-6">
        {{-- 訂單基本資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>訂單基本資訊
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>訂單編號：</strong></td>
                        <td>{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>用車日期：</strong></td>
                        <td>{{ $order->ride_date ? (is_string($order->ride_date) ? $order->ride_date : $order->ride_date->format('Y-m-d')) : 'N/A' }}

                            {{ $order->ride_time ? \Carbon\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>上車地址：</strong></td>
                        <td>{{ $order->pickup_address }}</td>
                    </tr>
                    <tr>
                        <td><strong>下車地址：</strong></td>
                        <td>{{ $order->dropoff_address }}</td>
                    </tr>
                    <tr>
                        <td><strong>是否輪椅：</strong></td>
                        <td>
                            @if($order->wheelchair == '是')
                                <span class="badge bg-warning">是</span>
                            @elseif($order->wheelchair == '未知')
                                <span class="badge bg-secondary">未知</span>
                            @else
                                <span class="badge bg-secondary">否</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>爬梯機：</strong></td>
                        <td>
                            @if($order->stair_machine == '是')
                                <span class="badge bg-danger">是</span>
                            @elseif($order->stair_machine == '未知')
                                <span class="badge bg-secondary">未知</span>
                            @else
                                <span class="badge bg-secondary">否</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>陪同人數：</strong></td>
                        <td>{{ $order->companions }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘對象：</strong></td>
                        <td>{{ $order->carpool_name }} / {{ $order->carpool_id }}</td>
                    </tr>
                    <tr>
                        <td><strong>特殊狀態：</strong></td>
                        <td>
                            @switch($order->special_status)
                                @case('網頁')
                                    <span class="badge bg-warning">網頁</span>
                                    @break
                                @case('個管單')
                                    <span class="badge bg-info">個管單</span>
                                    @break
                                @case('黑名單')
                                    <span class="badge bg-danger">黑名單</span>
                                    @break
                                @default
                                    <span class="badge bg-success">一般</span>
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        <td><strong>訂單狀態：</strong></td>
                        <td>
                            @switch($order->status)
                                @case('open')
                                    <span class="badge bg-success">可派遣</span>
                                    @break
                                @case('assigned')
                                    <span class="badge bg-primary">已指派</span>
                                    @break
                                @case('bkorder')
                                    <span class="badge bg-warning">已候補</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">已取消</span>
                                    @break
                                @case('cancelledOOC')
                                    <span class="badge bg-danger">已取消-9999</span>
                                    @break
                                @case('cancelledNOC')
                                    <span class="badge bg-danger">取消！</span>
                                    @break
                                @case('cancelledCOTD')
                                    <span class="badge bg-danger">取消 X</span>
                                    @break
                                @case('blocked')
                                    <span class="badge bg-info">無人承接</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        {{-- 取消原因顯示 --}}
                        @if(in_array($order->status, ['cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD']) && $order->cancellation_reason)
                        <td><strong>取消原因：</strong></td>
                        <td class="mt-3">
                            <strong style="color : red">{{ $order->cancellation_reason }}</strong>
                        </td>
                        @endif
                    </tr>
                </table>
            </div>
        </div>

    </div>

    <div class="col-md-6">
        {{-- 客戶資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>客戶資訊
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>客戶姓名：</strong></td>
                        <td>{{ $order->customer_name }}</td>
                    </tr>
                    <tr>
                        <td><strong>身分證字號：</strong></td>
                        <td>{{ $order->customer_id_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>電話：</strong></td>
                        <td>{{ $order->customer_phone }}</td>
                    </tr>
                    <tr>
                        <td><strong>身份別：</strong></td>
                        <td>{{ $order->identity }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- 駕駛資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i>駕駛資訊
                </h5>
            </div>
            <div class="card-body">
                @if($order->driver_name)
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>駕駛姓名：</strong></td>
                            <td>{{ $order->driver_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>隊員編號：</strong></td>
                            <td>{{ $order->driver_fleet_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>車牌號碼：</strong></td>
                            <td>{{ $order->driver_plate_number }}</td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted">
                        <i class="fas fa-exclamation-triangle me-2"></i>尚未指派駕駛
                    </p>
                @endif
            </div>
        </div>

        {{-- 共乘資訊 --}}
        @if($order->carpool_with)
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>共乘資訊
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>共乘對象：</strong></td>
                        <td>{{ $order->carpool_with }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘ID：</strong></td>
                        <td>{{ $order->carpool_id_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘電話：</strong></td>
                        <td>{{ $order->carpool_phone_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘地址：</strong></td>
                        <td>{{ $order->carpool_addresses }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- 備註資訊 --}}
@if($order->remark)
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-comment me-2"></i>備註資訊
        </h5>
    </div>
    <div class="card-body">
        <p class="mb-0">{{ $order->remark }}</p>
    </div>
</div>
@endif

{{-- 時間戳記 --}}
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-clock me-2"></i>系統資訊
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>建立時間：</strong><br>
                {{ $order->created_at->format('Y-m-d H:i:s') }}
            </div>
            <div class="col-md-3">
                <strong>建立人員：</strong><br>
                {{ $order->created_by ?: 'N/A' }}
            </div>
            <div class="col-md-3">
                <strong>更新時間：</strong><br>
                {{ $order->updated_at->format('Y-m-d H:i:s') }}
            </div>

            @if($order->updated_by)
            <div class="col-md-3">
                <strong>更新人員：</strong><br>
                {{ $order->updatedBy->name ?? '未知使用者' }}
            </div>
            @endif

        </div>
    </div>
</div>