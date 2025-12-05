@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="geographyAnalysis()">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-map-marker-alt mr-2"></i>地理與路線分析
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">首頁</a></li>
                        <li class="breadcrumb-item active">地理分析</li>
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
                        'title' => '上車區域統計',
                        'chartId' => 'pickupChart',
                        'icon' => 'fas fa-map-pin',
                        'tooltip' => '顯示前 15 名熱門上車區域（匯出 Excel 包含完整資料）',
                        'height' => '450px'
                    ])
                </div>
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '下車區域統計',
                        'chartId' => 'dropoffChart',
                        'icon' => 'fas fa-map-pin',
                        'tooltip' => '顯示前 15 名熱門下車區域（匯出 Excel 包含完整資料）',
                        'height' => '450px'
                    ])
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '跨縣市訂單統計',
                        'chartId' => 'crossCountyChart',
                        'icon' => 'fas fa-exchange-alt',
                        'tooltip' => '顯示跨縣市訂單與同縣市訂單的比例',
                        'height' => '400px'
                    ])
                </div>
                <div class="col-md-6">
                    @include('statistics.components.chart-card', [
                        'title' => '區域路線統計',
                        'chartId' => 'routesChart',
                        'icon' => 'fas fa-route',
                        'tooltip' => '顯示前 15 名熱門路線（匯出 Excel 包含完整資料）',
                        'height' => '450px'
                    ])
                </div>
            </div>

            <!-- 匯出按鈕 -->
            <div class="card card-primary card-outline mt-4">
                <div class="card-footer">
                    @include('statistics.components.export-button', [
                        'text' => '匯出地理分析報表'
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
<!-- 地理分析 JavaScript -->
<script src="{{ asset('js/statistics/geography.js') }}"></script>
@endpush
