<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>T9 管理系統</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- AdminLTE + Bootstrap + DataTables + Flatpickr 的 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/bootstrap.css">
</head>

<body class="hold-transition  sidebar-collapse">

<div class="wrapper">

    <!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- 左上角漢堡按鈕 -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
                <span class="navbar-brand">T9 管理系統</span>
            </a>
        </li>
    </ul>

    <!-- 右邊其他功能 -->
    <ul class="navbar-nav ms-auto">
        <!-- 用戶資訊下拉選單 -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                <i class="far fa-user"></i>
                <span class="ms-1">{{ Auth::user()->name ?? '用戶' }}</span>
                <i class="fas fa-angle-down ms-1"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                    <i class="fas fa-user-edit me-2"></i>個人設定
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger" 
                            onclick="return confirm('確定要登出嗎？')">
                        <i class="fas fa-sign-out-alt me-2"></i>登出
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="" class="brand-link text-center">
            <span class="brand-text font-weight-light">長照管理</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                    <li class="nav-item">
                        <a href="{{ route('customers.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>客戶管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('drivers.index') }}" class="nav-link {{ request()->is('admin/drivers*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-car"></i>
                            <p>駕駛管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('orders.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>訂單管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manual-dispatch.index') }}" 
                        class="nav-link {{ request()->routeIs('manual-dispatch.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>排趟管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dispatch-records.index') }}"
                        class="nav-link {{ request()->routeIs('dispatch-records.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>排趟記錄</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('carpool-groups.index') }}" class="nav-link {{ request()->is('carpool-groups*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>共乘群組管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('landmarks.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>地標管理</p>
                        </a>
                    </li>
                    {{-- 未來可加入：訂單管理、駕駛管理 --}}
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-3">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="main-footer text-center">
        <strong>&copy; {{ date('Y') }} LC 管理系統</strong>
    </footer>
</div>

</body>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>

<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/zh-tw.js"></script>

<!-- Flatpickr 全域繁體中文設定 -->
<script>
(function() {
    // 等待腳本完全載入
    if (typeof flatpickr !== 'undefined') {
        // 檢查語言包是否已載入
        if (flatpickr.l10ns && flatpickr.l10ns['zh-tw']) {
            console.log('Flatpickr 繁體中文語言包已載入');

            // 自訂繁體中文翻譯（台灣用語）
            flatpickr.l10ns['zh-tw'].weekdays = {
                shorthand: ["日", "一", "二", "三", "四", "五", "六"],
                longhand: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"]
            };

            flatpickr.l10ns['zh-tw'].months = {
                shorthand: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                longhand: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"]
            };

            // 設定全域預設選項
            flatpickr.setDefaults({
                locale: flatpickr.l10ns['zh-tw'],
                dateFormat: "Y-m-d",
                time_24hr: true
            });
        } else {
            console.warn('Flatpickr 繁體中文語言包未載入');
        }
    }
})();
</script>

<!-- 你的自訂 jQuery 行為（例如更新事件） -->
<script>
$(document).ready(function () {
    $('.update-event-btn').on('click', function () {
        const id = $(this).data('id');
        const input = $(`input[data-id='${id}']`);
        const newValue = input.val();

        $.ajax({
            url: '/customer-events/' + id,
            method: 'PUT',
            data: {
                event: newValue,
                _token: '{{ csrf_token() }}',
            },
            success: function (response) {
                if (response.success) {
                    alert('已更新成功');
                }
            },
            error: function () {
                alert('更新失敗，請檢查輸入或稍後再試');
            }
        });
    });
});
</script>

<!-- 留給每頁個別 @push('scripts') 使用 -->
@stack('scripts')


</html>



