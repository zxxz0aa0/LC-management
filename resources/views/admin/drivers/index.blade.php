@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">駕駛管理</h3>
        </div>

        <div class="card-body">
            <!-- 搜尋和篩選 -->
            <form method="GET" action="{{ route('drivers.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" name="keyword" class="form-control" 
                                   placeholder="搜尋姓名、手機、身分證..." value="{{ request('keyword') }}">
                            <button class="btn btn-primary" type="submit">搜尋</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary">篩選</button>
                        <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary">重置</a>
                    </div>
                    <div class="col-md-6 text-end">
                        <!-- 匯入匯出功能 -->
                        <div class="btn-group me-2">
                            <a href="{{ route('drivers.export') }}" class="btn btn-outline-success">
                                <i class="fas fa-download me-1"></i>匯出 Excel
                            </a>
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload me-1"></i>匯入 Excel
                            </button>
                            <a href="{{ route('drivers.template') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-excel me-1"></i>下載範例
                            </a>
                        </div>
                        <a href="{{ route('drivers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>新增駕駛
                        </a>
                    </div>
                </div>
            </form>

            <!-- 成功/錯誤訊息 -->
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>台號</th>
                            <th>姓名</th>
                            <th>手機</th>
                            <th>車牌</th>
                            <th>車輛</th>
                            <th>車型</th>
                            <th>顏色</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $driver)
                        <tr>
                            <td>{{ $driver->fleet_number }}</td>
                            <td>{{ $driver->name }}</td>
                            <td>{{ $driver->phone }}</td>
                            <td>{{ $driver->plate_number }}</td>
                            <td>{{ $driver->car_brand }}</td>
                            <td>{{ $driver->car_vehicle_style }}</td>
                            <td>{{ $driver->car_color }}</td>
                            <td>
                                <a href="{{ route('drivers.edit', $driver->id) }}" class="btn btn-sm btn-warning">編輯</a>
                                <form action="{{ route('drivers.destroy', $driver->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('確定要刪除嗎？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">刪除</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 分頁 -->
            <div class="mt-4">
                {{ $drivers->appends(request()->query())->links('components.pagination') }}
            </div>
        </div>
    </div>
</div>

<!-- 匯入 Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('drivers.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="fas fa-upload me-2"></i>匯入駕駛資料
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">選擇 Excel 檔案</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            支援的檔案格式：.xlsx, .xls
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>匯入須知：</h6>
                        <ul class="mb-0">
                            <li>請使用提供的範例檔案格式</li>
                            <li>必填欄位：姓名、手機、身分證字號</li>
                            <li>狀態選項：在職、離職、黑名單</li>
                            <li>重複的手機或身分證將更新現有資料</li>
                            <li>其他欄位為選填</li>
                        </ul>
                    </div>
                    
                    <!-- 匯入錯誤訊息顯示 -->
                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>匯入錯誤詳情：</h6>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>取消
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i>開始匯入
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

