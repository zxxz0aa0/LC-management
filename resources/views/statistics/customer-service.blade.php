@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="customerServiceAnalysis()">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-user-friends mr-2"></i>客服人員統計分析
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">首頁</a></li>
                        <li class="breadcrumb-item active">客服統計</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- 客服統計專用篩選器 -->
            @include('statistics.components.customer-service-filter')

            <hr class="my-4">

            <!-- 圖表展示區 -->
            <!-- 第一行：人員建單統計 + 當天/預約訂單統計 -->
            <div class="row">
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '人員建單總數量',
                        'chartId' => 'userOrderCountChart',
                        'icon' => 'fas fa-users',
                        'tooltip' => '顯示每位客服人員的建單總數與獨特客戶數',
                        'height' => '500px'
                    ])
                </div>
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '人員當天/預約訂單統計',
                        'chartId' => 'userOrderTypesChart',
                        'icon' => 'fas fa-calendar-alt',
                        'tooltip' => '顯示每位客服人員建立的當天訂單與預約訂單數量',
                        'height' => '500px'
                    ])
                </div>
            </div>

            <!-- 第二行：每小時建單數量 + 當天/預約訂單總數 -->
            <div class="row mt-4">
                <div class="col-md-8">
                    @include('statistics.components.chart-card', [
                        'title' => '每小時建單數量',
                        'chartId' => 'hourlyChart',
                        'icon' => 'fas fa-clock',
                        'tooltip' => '顯示24小時各時段的建單數量分布',
                        'height' => '400px'
                    ])
                </div>
                <div class="col-md-4">
                    @include('statistics.components.chart-card', [
                        'title' => '當天/預約訂單總數',
                        'chartId' => 'orderTypeSummaryChart',
                        'icon' => 'fas fa-chart-pie',
                        'tooltip' => '顯示當天訂單與預約訂單的比例',
                        'height' => '400px'
                    ])
                </div>
            </div>

            <!-- 第三行：訂單狀態分布 -->
            <div class="row mt-4">
                <div class="col-md-12">
                    @include('statistics.components.chart-card', [
                        'title' => '訂單狀態分布',
                        'chartId' => 'statusDistributionChart',
                        'icon' => 'fas fa-tasks',
                        'tooltip' => '顯示各種訂單狀態的數量分布',
                        'height' => '450px'
                    ])
                </div>
            </div>

            <!-- 匯出按鈕 -->
            <div class="card card-primary card-outline mt-4">
                <div class="card-footer">
                    @include('statistics.components.export-button', [
                        'text' => '匯出客服統計報表'
                    ])
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- 客服統計 JavaScript -->
<script src="{{ asset('js/statistics/customer-service.js') }}"></script>
@endpush
