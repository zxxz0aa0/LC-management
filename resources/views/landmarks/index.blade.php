@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">地標管理</h3>
        </div>

        <div class="card-body">
            <!-- 搜尋和篩選 -->
            <form method="GET" action="{{ route('landmarks.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" name="keyword" class="form-control" 
                                   placeholder="搜尋地標名稱或地址" value="{{ request('keyword') }}">
                            <button class="btn btn-primary" type="submit">搜尋</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-control">
                            <option value="">所有分類</option>
                            @foreach(App\Models\Landmark::CATEGORIES as $key => $value)
                                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="is_active" class="form-control">
                            <option value="">所有狀態</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>啟用</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>停用</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="sort" class="form-control">
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>名稱排序</option>
                            <option value="usage_count" {{ request('sort') == 'usage_count' ? 'selected' : '' }}>使用次數</option>
                            <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>建立時間</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary">篩選</button>
                        <a href="{{ route('landmarks.index') }}" class="btn btn-outline-secondary">重置</a>
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

            <!-- 操作按鈕 -->
            <div class="mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <a href="{{ route('landmarks.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>新增地標
                        </a>
                        <button type="button" class="btn btn-success" onclick="batchToggle(true)">
                            <i class="fas fa-check me-1"></i>批量啟用
                        </button>
                        <button type="button" class="btn btn-warning" onclick="batchToggle(false)">
                            <i class="fas fa-times me-1"></i>批量停用
                        </button>
                        <button type="button" class="btn btn-danger" onclick="batchDelete()">
                            <i class="fas fa-trash me-1"></i>批量刪除
                        </button>
                    </div>
                    <div class="col-md-4 text-end">
                        <!-- 匯入匯出功能 -->
                        <div class="btn-group">
                            <a href="{{ route('landmarks.export') }}" class="btn btn-outline-success">
                                <i class="fas fa-download me-1"></i>匯出 Excel
                            </a>
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload me-1"></i>匯入 Excel
                            </button>
                            <a href="{{ route('landmarks.template') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-excel me-1"></i>下載範例
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 地標列表 -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-success">
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>地標名稱</th>
                            <th>完整地址</th>
                            <th>分類</th>
                            <th>使用次數</th>
                            <th>狀態</th>
                            <th>建立者</th>
                            <th>建立時間</th>
                            <th width="200">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($landmarks as $landmark)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $landmark->id }}">
                                </td>
                                <td>{{ $landmark->name }}</td>
                                <td>{{ $landmark->city }}{{ $landmark->district }}{{ $landmark->address }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $landmark->category_name }}</span>
                                </td>
                                <td>{{ $landmark->usage_count }}</td>
                                <td>
                                    @if($landmark->is_active)
                                        <span class="badge bg-success">啟用</span>
                                    @else
                                        <span class="badge bg-secondary">停用</span>
                                    @endif
                                </td>
                                <td>{{ $landmark->created_by }}</td>
                                <td>{{ $landmark->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('landmarks.show', $landmark) }}" class="btn btn-sm btn-info">檢視</a>
                                    <a href="{{ route('landmarks.edit', $landmark) }}" class="btn btn-sm btn-warning">編輯</a>
                                    <form method="POST" action="{{ route('landmarks.destroy', $landmark) }}" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('確定要刪除此地標嗎？')">刪除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">暫無地標資料</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 分頁 -->
            <div class="d-flex justify-content-center">
                {{ $landmarks->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
// 全選/取消全選
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// 批量啟用/停用
function batchToggle(status) {
    const checkedIds = getCheckedIds();
    if (checkedIds.length === 0) {
        alert('請選擇要操作的地標');
        return;
    }
    
    if (confirm(`確定要${status ? '啟用' : '停用'}選中的地標嗎？`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('landmarks.batchToggle') }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status ? '1' : '0';
        form.appendChild(statusInput);
        
        checkedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 批量刪除
function batchDelete() {
    const checkedIds = getCheckedIds();
    if (checkedIds.length === 0) {
        alert('請選擇要刪除的地標');
        return;
    }
    
    if (confirm('確定要刪除選中的地標嗎？此操作無法復原！')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('landmarks.batchDestroy') }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        checkedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 獲取選中的ID
function getCheckedIds() {
    const checkboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}
</script>

<!-- 匯入 Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('landmarks.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="fas fa-upload me-2"></i>匯入地標資料
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
                            <li>必填欄位：地標名稱、地址、城市、區域、分類</li>
                            <li>分類選項：醫療、交通、教育、政府機關、商業、一般</li>
                            <li>是否啟用：填入 1（啟用）或 0（停用）</li>
                            <li>座標為選填，格式需為數字</li>
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