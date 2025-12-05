@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="timeAnalysis()">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-clock mr-2"></i>時間模式分析
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">首頁</a></li>
                        <li class="breadcrumb-item active">時間分析</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- 日期範圍篩選器 -->
            @include('statistics.components.date-range-filter')

            <hr class="my-4">

            <!-- 圖表展示區 -->
            <div class="row">
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '尖峰時段分析（24小時）',
                        'chartId' => 'peakHoursChart',
                        'icon' => 'fas fa-chart-line',
                        'tooltip' => '顯示一天中各時段的訂單分布',
                        'height' => '400px'
                    ])
                </div>
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '週間分布（週一～週日）',
                        'chartId' => 'weekdayChart',
                        'icon' => 'fas fa-calendar-week',
                        'tooltip' => '顯示一週中各日的訂單分布',
                        'height' => '400px'
                    ])
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '月份趨勢（12個月）',
                        'chartId' => 'monthlyTrendsChart',
                        'icon' => 'fas fa-chart-area',
                        'tooltip' => '顯示各月份的訂單趨勢變化',
                        'height' => '400px'
                    ])
                </div>
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '提前預約天數分布',
                        'chartId' => 'advanceBookingChart',
                        'icon' => 'fas fa-calendar-check',
                        'tooltip' => '顯示客戶提前多少天預約的分布情況',
                        'height' => '400px'
                    ])
                </div>
            </div>

            <!-- 匯出按鈕 -->
            <div class="card card-primary card-outline mt-4">
                <div class="card-footer">
                    @include('statistics.components.export-button', [
                        'text' => '匯出時間分析報表'
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
<!-- 時間分析 JavaScript -->
<script src="{{ asset('js/statistics/time-analysis.js') }}"></script>
@endpush
