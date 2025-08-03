@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 頁面標題和操作按鈕 --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-users me-2"></i>共乘群組管理
        </h2>
        <div class="btn-group">
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>建立新訂單
            </a>
        </div>
    </div>

    {{-- 搜尋和篩選區域 --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('carpool-groups.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">關鍵字搜尋</label>
                    <input type="text" name="keyword" class="form-control"
                           placeholder="群組ID、客戶姓名、訂單編號"
                           value="{{ request('keyword') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">狀態</label>
                    <select name="status" class="form-select">
                        <option value="">全部狀態</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>待派遣</option>
                        <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>已派遣</option>
                        <option value="replacement" {{ request('status') === 'replacement' ? 'selected' : '' }}>替代司機</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>暫停</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>已取消</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>已完成</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">開始日期</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">結束日期</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>搜尋
                    </button>
                    <a href="{{ route('carpool-groups.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>清除
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- 批量操作區域 --}}
    <div class="card mb-4" id="batch-actions" style="display: none;">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">批量操作</label>
                    <select id="batch-action" class="form-select">
                        <option value="">選擇操作</option>
                        <option value="assign_driver">指派司機</option>
                        <option value="cancel">取消群組</option>
                        <option value="dissolve">解除群組</option>
                    </select>
                </div>
                <div class="col-md-3" id="driver-select-container" style="display: none;">
                    <label class="form-label">選擇司機</label>
                    <select id="batch-driver" class="form-select">
                        <option value="">請選擇司機</option>
                        {{-- 動態載入司機選項 --}}
                    </select>
                </div>
                <div class="col-md-4" id="reason-input-container" style="display: none;">
                    <label class="form-label">原因說明</label>
                    <input type="text" id="batch-reason" class="form-control" placeholder="請輸入原因（可選）">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-warning" onclick="executeBatchAction()">
                        <i class="fas fa-play me-2"></i>執行
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-1" onclick="clearSelection()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 群組列表 --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">共乘群組列表</h5>
            <div class="d-flex align-items-center">
                <span class="text-muted me-3">總計 {{ $groups->total() }} 個群組</span>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all">
                    <label class="form-check-label" for="select-all">全選</label>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($groups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="select-all-header">
                                </th>
                                <!--<th>群組資訊</th>-->
                                <th>訂單編號</th>
                                <th>主要客戶</th>
                                <th>共乘對象</th>
                                <th>用車時間</th>
                                <th>路線</th>
                                <th>司機</th>
                                <th>狀態</th>
                                <th width="120">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $group)
                                @php
                                    $groupInfo = $group->group_info;
                                    $mainOrder = $groupInfo['main_order'] ?? $group;
                                    $members = $groupInfo['members'] ?? collect();
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input group-checkbox"
                                               value="{{ $group->carpool_group_id }}">
                                    </td>
                                    <!--<td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-primary">{{ $group->carpool_group_id }}</span>
                                            <small class="text-muted">{{ $groupInfo['member_count'] ?? 2 }} 人群組</small>
                                            <small class="text-muted">{{ $group->created_at->format('Y-m-d H:i') }}</small>
                                        </div>
                                    </td>-->
                                    <td>
                                        <small>{{ $mainOrder->order_number }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ $mainOrder->customer_name }}</span>
                                            <small class="text-muted">{{ $mainOrder->customer_id_number }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($members->count() > 0)
                                            @foreach($members as $member)
                                                <div class="mb-1">
                                                    <span>{{ $member->customer_name }}</span>
                                                    <small class="text-muted d-block">{{ $member->customer_id_number }}</small>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">無共乘成員</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ \Carbon\Carbon::parse($mainOrder->ride_date)->format('Y-m-d') }}</span>
                                            <span class="text-primary">{{ \Carbon\Carbon::parse($mainOrder->ride_time)->format('H:i') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="text-success">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                {{ Str::limit($mainOrder->pickup_address, 20) }}
                                            </small>
                                            <small class="text-danger">
                                                <i class="fas fa-flag-checkered me-1"></i>
                                                {{ Str::limit($mainOrder->dropoff_address, 20) }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($mainOrder->driver_name)
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $mainOrder->driver_name }}</span>
                                                @if($mainOrder->driver_fleet_number)
                                                    <small class="text-muted">車號：{{ $mainOrder->driver_fleet_number }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">未指派</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($mainOrder->status) {
                                                'open' => 'bg-secondary',
                                                'assigned' => 'bg-success',
                                                'replacement' => 'bg-warning',
                                                'blocked' => 'bg-danger',
                                                'cancelled' => 'bg-dark',
                                                'completed' => 'bg-primary',
                                                default => 'bg-secondary'
                                            };
                                            $statusText = match($mainOrder->status) {
                                                'open' => '待派遣',
                                                'assigned' => '已派遣',
                                                'replacement' => '替代司機',
                                                'blocked' => '暫停',
                                                'cancelled' => '已取消',
                                                'completed' => '已完成',
                                                default => $mainOrder->status
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('carpool-groups.show', $group->carpool_group_id) }}"
                                               class="btn btn-outline-primary" title="檢視詳情">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($mainOrder->status !== 'cancelled' && $mainOrder->status !== 'completed')
                                                <button type="button" class="btn btn-outline-warning dropdown-toggle dropdown-toggle-split"
                                                        data-bs-toggle="dropdown" title="更多操作">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    @if(!$mainOrder->driver_id)
                                                        <li><a class="dropdown-item" href="#" onclick="showAssignDriverModal('{{ $group->carpool_group_id }}')">
                                                            <i class="fas fa-user-plus me-2"></i>指派司機
                                                        </a></li>
                                                    @endif
                                                    <li><a class="dropdown-item" href="#" onclick="showCancelModal('{{ $group->carpool_group_id }}')">
                                                        <i class="fas fa-times me-2"></i>取消群組
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="showDissolveModal('{{ $group->carpool_group_id }}')">
                                                        <i class="fas fa-unlink me-2"></i>解除群組
                                                    </a></li>
                                                </ul>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 分頁 --}}
                <div class="d-flex justify-content-center mt-3">
                    {{ $groups->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">尚無共乘群組</h5>
                    <p class="text-muted">建立新的共乘訂單後，群組將顯示在這裡</p>
                    <a href="{{ route('orders.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>建立新訂單
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- 操作確認 Modal --}}
@include('carpool-groups.components.action-modal')

@endsection

@push('scripts')
    <script src="{{ asset('js/carpool-groups/index.js') }}"></script>
@endpush