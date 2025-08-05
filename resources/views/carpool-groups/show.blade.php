@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 頁面標題和操作按鈕 --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-users me-2"></i>共乘群組詳情
            </h2>
            <small class="text-muted">群組 ID：{{ $groupInfo['id'] }}</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('carpool-groups.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>返回列表
            </a>
            @if($groupInfo['status'] !== 'cancelled' && $groupInfo['status'] !== 'completed')
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>群組操作
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$groupInfo['main_order']->driver_id)
                            <li><a class="dropdown-item" href="#" onclick="showAssignDriverModal()">
                                <i class="fas fa-user-plus me-2"></i>指派司機
                            </a></li>
                        @else
                            <li><a class="dropdown-item" href="#" onclick="showAssignDriverModal()">
                                <i class="fas fa-user-edit me-2"></i>更換司機
                            </a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="showUpdateStatusModal()">
                            <i class="fas fa-edit me-2"></i>更新狀態
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-warning" href="#" onclick="showCancelModal()">
                            <i class="fas fa-times me-2"></i>取消群組
                        </a></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="showDissolveModal()">
                            <i class="fas fa-unlink me-2"></i>解除群組
                        </a></li>
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        {{-- 群組基本資訊 --}}
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>群組資訊
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">群組 ID</dt>
                        <dd class="col-sm-8">
                            <code>{{ $groupInfo['id'] }}</code>
                        </dd>

                        <dt class="col-sm-4">成員數量</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-primary">{{ $groupInfo['member_count'] }} 人</span>
                        </dd>

                        <dt class="col-sm-4">群組狀態</dt>
                        <dd class="col-sm-8">
                            @php
                                $statusClass = match($groupInfo['status']) {
                                    'open' => 'bg-secondary',
                                    'assigned' => 'bg-success',
                                    'replacement' => 'bg-warning',
                                    'blocked' => 'bg-danger',
                                    'cancelled' => 'bg-dark',
                                    'completed' => 'bg-primary',
                                    default => 'bg-secondary'
                                };
                                $statusText = match($groupInfo['status']) {
                                    'open' => '待派遣',
                                    'assigned' => '已派遣',
                                    'replacement' => '替代司機',
                                    'blocked' => '暫停',
                                    'cancelled' => '已取消',
                                    'completed' => '已完成',
                                    default => $groupInfo['status']
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                        </dd>

                        <dt class="col-sm-4">建立時間</dt>
                        <dd class="col-sm-8">{{ $groupInfo['created_at'] }}</dd>

                        @if($groupInfo['main_order']->driver_name)
                            <dt class="col-sm-4">指派司機</dt>
                            <dd class="col-sm-8">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">{{ $groupInfo['main_order']->driver_name }}</span>
                                    @if($groupInfo['main_order']->driver_fleet_number)
                                        <small class="text-muted">車號：{{ $groupInfo['main_order']->driver_fleet_number }}</small>
                                    @endif
                                    @if($groupInfo['main_order']->driver_plate_number)
                                        <small class="text-muted">車牌：{{ $groupInfo['main_order']->driver_plate_number }}</small>
                                    @endif
                                </div>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- 服務需求資訊 --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-wheelchair me-2"></i>服務需求
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">輪椅服務</dt>
                        <dd class="col-sm-7">
                            @if($groupInfo['main_order']->wheelchair)
                                <span class="badge bg-success">需要</span>
                            @else
                                <span class="badge bg-secondary">不需要</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">爬梯機</dt>
                        <dd class="col-sm-7">
                            @if($groupInfo['main_order']->stair_machine)
                                <span class="badge bg-success">需要</span>
                            @else
                                <span class="badge bg-secondary">不需要</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">陪同人數</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-info">{{ $groupInfo['main_order']->companions }} 人</span>
                        </dd>

                        @if($groupInfo['main_order']->remark)
                            <dt class="col-sm-5">備註</dt>
                            <dd class="col-sm-7">{{ $groupInfo['main_order']->remark }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- 群組成員和訂單詳情 --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>群組成員與訂單
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($groupInfo['all_orders'] as $order)
                        <div class="border rounded p-3 mb-3 {{ $order->is_main_order ? 'border-primary' : 'border-secondary' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        @if($order->is_main_order)
                                            <span class="badge bg-primary me-2">主訂單</span>
                                        @else
                                            <span class="badge bg-secondary me-2">成員</span>
                                        @endif
                                        <h6 class="mb-0">{{ $order->customer_name }}</h6>
                                    </div>

                                    <dl class="row small">
                                        <dt class="col-sm-4">訂單編號</dt>
                                        <dd class="col-sm-8">
                                            <code>{{ $order->order_number }}</code>
                                        </dd>

                                        <dt class="col-sm-4">客戶電話</dt>
                                        <dd class="col-sm-8">{{ $order->customer_phone }}</dd>

                                        <dt class="col-sm-4">身分證號</dt>
                                        <dd class="col-sm-8">{{ $order->customer_id_number }}</dd>

                                        <dt class="col-sm-4">用車時間</dt>
                                        <dd class="col-sm-8">
                                            <span class="text-primary">{{ \Carbon\Carbon::parse($order->ride_date)->format('Y-m-d') }}</span>
                                            <span class="text-primary">{{ \Carbon\Carbon::parse($order->ride_time)->format('H:i') }}</span>
                                        </dd>
                                    </dl>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label small text-muted">路線資訊</label>
                                    </div>

                                    <div class="route-info">
                                        <div class="d-flex align-items-start mb-2">
                                            <i class="fas fa-map-marker-alt text-success me-2 mt-1"></i>
                                            <div>
                                                <small class="text-muted">上車地點</small>
                                                <div>{{ $order->pickup_address }}</div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-flag-checkered text-danger me-2 mt-1"></i>
                                            <div>
                                                <small class="text-muted">下車地點</small>
                                                <div>{{ $order->dropoff_address }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 共乘資訊 --}}
                            @if($order->special_status === '共乘')
                                <div class="mt-3 pt-2 border-top">
                                    <small class="text-muted">共乘對象：</small>
                                    <span class="text-info">{{ $order->carpool_name }}</span>
                                    <small class="text-muted ms-2">({{ $order->carpool_id }})</small>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 指派司機 Modal --}}
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">指派司機</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignDriverForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">選擇司機</label>
                        <select name="driver_id" class="form-select" required>
                            <option value="">請選擇司機</option>
                            @foreach($availableDrivers as $driver)
                                <option value="{{ $driver->id }}">
                                    {{ $driver->name }}
                                    @if($driver->fleet_number)
                                        (車號：{{ $driver->fleet_number }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        指派司機後，群組內所有訂單都會分配給該司機
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">確認指派</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 更新狀態 Modal --}}
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">更新群組狀態</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateStatusForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">新狀態</label>
                        <select name="status" class="form-select" required>
                            <option value="">請選擇狀態</option>
                            <option value="open">待派遣</option>
                            <option value="assigned">已派遣</option>
                            <option value="replacement">替代司機</option>
                            <option value="blocked">暫停</option>
                            <option value="completed">已完成</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">備註</label>
                        <textarea name="remark" class="form-control" rows="3" placeholder="請輸入狀態更新的原因或備註（可選）"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">確認更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 取消群組 Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>取消群組
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>注意：</strong>取消群組將會將所有訂單狀態設為「已取消」，此操作不可復原！
                    </div>
                    <div class="mb-3">
                        <label class="form-label">取消原因</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="請輸入取消原因" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-warning">確認取消群組</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 解除群組 Modal --}}
<div class="modal fade" id="dissolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-unlink me-2"></i>解除群組
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="dissolveForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>危險操作：</strong>解除群組將會把共乘訂單拆分為獨立訂單，此操作不可復原！
                    </div>
                    <div class="mb-3">
                        <label class="form-label">解除原因</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="請輸入解除原因" required></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="force" id="forceDissolve">
                        <label class="form-check-label" for="forceDissolve">
                            強制解除（即使有進行中的訂單）
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-danger">確認解除群組</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        const groupId = '{{ $groupInfo['id'] }}';
    </script>
    <script src="{{ asset('js/carpool-groups/show.js') }}"></script>
@endpush