<div x-data="customerServiceFilter()" x-init="init()" class="card card-primary card-outline">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-filter mr-2"></i>篩選條件
        </h5>

        <!-- 快捷選項 -->
        <div class="btn-group mb-3 d-flex flex-wrap" role="group">
            <button type="button" @click="setQuickRange('today')"
                    class="btn btn-sm btn-outline-primary">今天</button>
            <button type="button" @click="setQuickRange('last7days')"
                    class="btn btn-sm btn-outline-primary">最近7天</button>
            <button type="button" @click="setQuickRange('last30days')"
                    class="btn btn-sm btn-outline-primary active">最近30天</button>
            <button type="button" @click="setQuickRange('last90days')"
                    class="btn btn-sm btn-outline-primary">最近90天</button>
            <button type="button" @click="setQuickRange('thisMonth')"
                    class="btn btn-sm btn-outline-primary">本月</button>
            <button type="button" @click="setQuickRange('lastMonth')"
                    class="btn btn-sm btn-outline-primary">上月</button>
            <button type="button" @click="setQuickRange('thisYear')"
                    class="btn btn-sm btn-outline-primary">今年</button>
        </div>

        <!-- 第一行：日期 + 訂單來源 + 查詢按鈕 -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="start-date">開始日期</label>
                <input type="date" id="start-date" x-model="startDate"
                       class="form-control" placeholder="選擇開始日期">
            </div>
            <div class="col-md-3">
                <label for="end-date">結束日期</label>
                <input type="date" id="end-date" x-model="endDate"
                       class="form-control" placeholder="選擇結束日期">
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
                <label>&nbsp;</label>
                <button @click="applyFilter()"
                        class="btn btn-primary w-100"
                        :disabled="loading">
                    <i class="fas fa-search mr-1"></i>
                    <span x-show="!loading">查詢</span>
                    <span x-show="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </div>

        <!-- 第二行：建單人員 + 訂單狀態 -->
        <div class="row">
            <div class="col-md-6">
                <label for="created-by">建單人員</label>
                <select id="created-by" x-model="createdBy" class="form-control">
                    <option value="">全部</option>
                    <template x-for="user in availableUsers" :key="user">
                        <option :value="user" x-text="user"></option>
                    </template>
                </select>
            </div>
            <div class="col-md-6">
                <label for="order-status">訂單狀態</label>
                <select id="order-status" x-model="orderStatus" class="form-control">
                    <option value="">全部</option>
                    <option value="assigned">已指派</option>
                    <option value="open">待派遣</option>
                    <option value="bkorder">預約單</option>
                    <option value="blocked">已封鎖</option>
                    <option value="cancelled">已取消</option>
                    <option value="cancelledOOC">已取消(非公司因素)</option>
                    <option value="cancelledNOC">已取消(非客戶因素)</option>
                    <option value="cancelledCOTD">已取消(當日取消)</option>
                    <option value="blacklist">黑名單</option>
                    <option value="no_send">未派送</option>
                    <option value="regular_sedans">一般轎車</option>
                    <option value="no_car">無車可派</option>
                </select>
            </div>
        </div>

        <!-- 日期範圍顯示 -->
        <div class="mt-3 text-muted small">
            <i class="fas fa-info-circle mr-1"></i>
            當前查詢範圍：<span x-text="displayDateRange()"></span>
            <span x-show="orderType" class="ml-2">
                | 訂單來源：<span x-text="orderType"></span>
            </span>
            <span x-show="createdBy" class="ml-2">
                | 建單人員：<span x-text="createdBy"></span>
            </span>
            <span x-show="orderStatus" class="ml-2">
                | 訂單狀態：<span x-text="orderStatus"></span>
            </span>
        </div>

        <!-- 錯誤訊息 -->
        <div x-show="error" class="alert alert-danger mt-3 mb-0" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <span x-text="error"></span>
        </div>
    </div>
</div>
