<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="{{ $icon ?? 'fas fa-chart-bar' }} mr-2"></i>
            {{ $title }}
        </h3>
        <div class="card-tools">
            @if(isset($tooltip))
            <button type="button" class="btn btn-tool" data-bs-toggle="tooltip" title="{{ $tooltip }}">
                <i class="fas fa-question-circle"></i>
            </button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="chart-container" style="position: relative; height: {{ $height ?? '400px' }}; overflow-y: auto;">
            <canvas id="{{ $chartId }}"></canvas>
        </div>

        <!-- 載入中動畫 -->
        <div id="{{ $chartId }}-loading" class="text-center py-5" style="display: none;">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3 text-muted">載入中...</p>
        </div>

        <!-- 無數據提示 -->
        <div id="{{ $chartId }}-no-data" class="text-center py-5" style="display: none;">
            <i class="fas fa-chart-line fa-3x text-muted"></i>
            <p class="mt-3 text-muted">目前沒有數據</p>
        </div>
    </div>
</div>
