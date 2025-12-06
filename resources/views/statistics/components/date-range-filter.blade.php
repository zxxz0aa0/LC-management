<div x-data="dateRangeFilter()" x-init="init()" class="card card-primary card-outline">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-calendar-alt mr-2"></i>查詢範圍篩選
        </h5>

        <div class="btn-group mb-3 d-flex flex-wrap" role="group">
            <button type="button" @click="setQuickRange('today')" class="btn btn-sm btn-outline-primary">今天</button>
            <button type="button" @click="setQuickRange('last7days')" class="btn btn-sm btn-outline-primary">最近7天</button>
            <button type="button" @click="setQuickRange('last30days')" class="btn btn-sm btn-outline-primary active">最近30天</button>
            <button type="button" @click="setQuickRange('last90days')" class="btn btn-sm btn-outline-primary">最近90天</button>
            <button type="button" @click="setQuickRange('thisMonth')" class="btn btn-sm btn-outline-primary">本月</button>
            <button type="button" @click="setQuickRange('lastMonth')" class="btn btn-sm btn-outline-primary">上月</button>
            <button type="button" @click="setQuickRange('thisYear')" class="btn btn-sm btn-outline-primary">今年</button>
        </div>

        <div class="row">
            <div class="col-md-3">
                <label for="start-date">開始日期</label>
                <input type="date" id="start-date" x-model="startDate" class="form-control" placeholder="請選擇開始日期">
            </div>
            <div class="col-md-3">
                <label for="end-date">結束日期</label>
                <input type="date" id="end-date" x-model="endDate" class="form-control" placeholder="請選擇結束日期">
            </div>
            <div class="col-md-3">
                <label for="order-type">訂單來源</label>
                <select id="order-type" x-model="orderType" class="form-control">
                    <option value="">全部</option>
                    @foreach(\App\Models\Order::ORDER_TYPES as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="d-block">訂單狀態</label>
                <div class="position-relative" @click.outside="showStatus = false">
                    <div class="form-control d-flex justify-content-between align-items-center"
                         role="button"
                         @click="showStatus = !showStatus">
                        <span x-text="statusSummary()"></span>
                        <i class="fas fa-chevron-down ml-2"></i>
                    </div>
                    <div class="border rounded bg-white position-absolute w-100 mt-1 shadow"
                         x-show="showStatus"
                         x-transition>
                        <div class="p-2">
                            <template x-for="opt in statusOptions" :key="opt.value">
                                <label class="mb-1 d-flex align-items-center">
                                    <input type="checkbox" class="mr-2" :value="opt.value" x-model="statuses">
                                    <span x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                        <div class="px-2 pb-2 text-muted small">
                            未選擇時預設統計：已派遣 / 待派遣 / 候補單
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3 col-sm-6">
                <button @click="applyFilter()" class="btn btn-primary w-100" :disabled="loading">
                    <i class="fas fa-search mr-1"></i>
                    <span x-show="!loading">查詢</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>

        <div class="mt-3 text-muted small">
            <i class="fas fa-info-circle mr-1"></i>
            當前查詢範圍：<span x-text="displayDateRange()"></span>
            <span x-show="orderType" class="ml-2">| 訂單來源：<span x-text="orderType"></span></span>
            <span x-show="statuses.length" class="ml-2">| 訂單狀態：<span x-text="statusSummary()"></span></span>
        </div>

        <div x-show="error" class="alert alert-danger mt-3 mb-0" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <span x-text="error"></span>
        </div>
    </div>
</div>
