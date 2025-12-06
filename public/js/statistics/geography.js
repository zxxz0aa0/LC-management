document.addEventListener('alpine:init', () => {
    // 地理統計頁面 Alpine.js 組件
    Alpine.data('geographyAnalysis', () => ({
        charts: {},
        hasData: false,
        exporting: false,

        // 匯出地理統計報表
        async exportReport() {
            this.exporting = true;
            try {
                const store = Alpine.store('dateFilter');
                const startDate = store.startDate;
                const endDate = store.endDate;
                const orderType = store.orderType;
                const statuses = store.statuses || [];

                let exportUrl = `/statistics/export/geography?start_date=${startDate}&end_date=${endDate}`;

                if (orderType) {
                    exportUrl += `&order_type=${encodeURIComponent(orderType)}`;
                }
                if (Array.isArray(statuses) && statuses.length) {
                    statuses.forEach(s => {
                        exportUrl += `&status[]=${encodeURIComponent(s)}`;
                    });
                }

                window.location.href = exportUrl;

                setTimeout(() => {
                    this.exporting = false;
                }, 2000);
            } catch (error) {
                console.error('匯出失敗:', error);
                this.exporting = false;
                alert('匯出失敗，請稍後再試');
            }
        }
    }));

    // 日期/篩選表單 Alpine.js 組件
    Alpine.data('dateRangeFilter', () => ({
        startDate: '',
        endDate: '',
        orderType: '',
        statuses: [],
        statusOptions: [
            { value: 'open', label: '待派遣' },
            { value: 'assigned', label: '已派遣' },
            { value: 'bkorder', label: '候補單' },
        ],
        showStatus: false,
        loading: false,
        error: '',

        init() {
            // 預設查詢最近 30 天
            this.setQuickRange('last30days');

            // 建立共用 store
            if (!Alpine.store('dateFilter')) {
                Alpine.store('dateFilter', {
                    startDate: this.startDate,
                    endDate: this.endDate,
                    orderType: this.orderType,
                    statuses: this.statuses,
                });
            }
        },

        // 快捷設定日期區間並觸發查詢
        setQuickRange(range) {
            const today = new Date();
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            switch (range) {
                case 'today':
                    this.startDate = formatDate(today);
                    this.endDate = formatDate(today);
                    break;
                case 'last7days': {
                    const d = new Date(today);
                    d.setDate(today.getDate() - 7);
                    this.startDate = formatDate(d);
                    this.endDate = formatDate(today);
                    break;
                }
                case 'last30days': {
                    const d = new Date(today);
                    d.setDate(today.getDate() - 30);
                    this.startDate = formatDate(d);
                    this.endDate = formatDate(today);
                    break;
                }
                case 'last90days': {
                    const d = new Date(today);
                    d.setDate(today.getDate() - 90);
                    this.startDate = formatDate(d);
                    this.endDate = formatDate(today);
                    break;
                }
                case 'thisMonth': {
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    this.startDate = formatDate(firstDay);
                    this.endDate = formatDate(today);
                    break;
                }
                case 'lastMonth': {
                    const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const end = new Date(today.getFullYear(), today.getMonth(), 0);
                    this.startDate = formatDate(start);
                    this.endDate = formatDate(end);
                    break;
                }
                case 'thisYear': {
                    const start = new Date(today.getFullYear(), 0, 1);
                    this.startDate = formatDate(start);
                    this.endDate = formatDate(today);
                    break;
                }
            }

            this.applyFilter();
        },

        // 送出查詢，呼叫地理統計 API
        async applyFilter() {
            this.loading = true;
            this.error = '';

            if (Alpine.store('dateFilter')) {
                Alpine.store('dateFilter').startDate = this.startDate;
                Alpine.store('dateFilter').endDate = this.endDate;
                Alpine.store('dateFilter').orderType = this.orderType;
                Alpine.store('dateFilter').statuses = this.statuses;
            }

            try {
                let apiUrl = `/statistics/api/geography?start_date=${this.startDate}&end_date=${this.endDate}`;

                if (this.orderType) {
                    apiUrl += `&order_type=${encodeURIComponent(this.orderType)}`;
                }
                if (Array.isArray(this.statuses) && this.statuses.length) {
                    this.statuses.forEach(s => {
                        apiUrl += `&status[]=${encodeURIComponent(s)}`;
                    });
                }

                const response = await fetch(apiUrl);
                const data = await response.json();

                if (!response.ok) {
                    this.error = data.error || '查詢失敗';
                    this.loading = false;
                    return;
                }

                updateCharts(data);
            } catch (error) {
                console.error('查詢失敗:', error);
                this.error = '查詢失敗，請稍後再試';
            } finally {
                this.loading = false;
            }
        },

        // 顯示目前日期區間摘要
        displayDateRange() {
            if (!this.startDate || !this.endDate) {
                return '未選擇';
            }
            return `${this.startDate} 至 ${this.endDate}`;
        },

        // 顯示目前選取的狀態摘要
        statusSummary() {
            const map = this.statusOptions.reduce((acc, cur) => {
                acc[cur.value] = cur.label;
                return acc;
            }, {});

            if (Array.isArray(this.statuses) && this.statuses.length) {
                const labels = this.statuses.map(s => map[s] || s);
                return labels.join(', ');
            }
            return '已派遣 / 待派遣 / 候補單';
        }
    }));
});

// Chart instances
let charts = {};

// 更新所有圖表
function updateCharts(data) {
    updatePickupChart(data.pickup_locations);
    updateDropoffChart(data.dropoff_locations);
    updateCrossCountyChart(data.cross_county);
    updateRoutesChart(data.popular_routes);
}

// 更新上車熱點圖表
function updatePickupChart(data) {
    const ctx = document.getElementById('pickupChart');
    if (!ctx) return;

    if (charts.pickup) {
        charts.pickup.destroy();
    }

    const fixedHeight = 450;
    ctx.style.height = fixedHeight + 'px';

    const labels = data.map(item => item.area);
    const values = data.map(item => item.order_count);

    charts.pickup = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '訂單數',
                data: values,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: '顯示前 15 筆熱門上車地點',
                    font: { size: 12, weight: 'normal' },
                    color: '#6c757d',
                    padding: { bottom: 10 }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `訂單數 ${item.order_count}`,
                                `獨立客戶數 ${item.unique_customers}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

// 更新下車熱點圖表
function updateDropoffChart(data) {
    const ctx = document.getElementById('dropoffChart');
    if (!ctx) return;

    if (charts.dropoff) {
        charts.dropoff.destroy();
    }

    const fixedHeight = 450;
    ctx.style.height = fixedHeight + 'px';

    const labels = data.map(item => item.area);
    const values = data.map(item => item.order_count);

    charts.dropoff = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '訂單數',
                data: values,
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: '顯示前 15 筆熱門下車地點',
                    font: { size: 12, weight: 'normal' },
                    color: '#6c757d',
                    padding: { bottom: 10 }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `訂單數 ${item.order_count}`,
                                `獨立客戶數 ${item.unique_customers}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

// 更新跨縣市占比圖表
function updateCrossCountyChart(data) {
    const ctx = document.getElementById('crossCountyChart');
    if (!ctx) return;

    if (charts.crossCounty) {
        charts.crossCounty.destroy();
    }

    charts.crossCounty = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['同縣市訂單', '跨縣市訂單'],
            datasets: [{
                data: [data.same_county_orders, data.cross_county_orders],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 206, 86, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const percentage = context.label === '同縣市訂單'
                                ? data.same_county_percentage
                                : data.cross_county_percentage;
                            return `${label}: ${value} 筆(${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// 更新熱門路線圖表
function updateRoutesChart(data) {
    const ctx = document.getElementById('routesChart');
    if (!ctx) return;

    if (charts.routes) {
        charts.routes.destroy();
    }

    const fixedHeight = 450;
    ctx.style.height = fixedHeight + 'px';

    const labels = data.map(item => item.route);
    const values = data.map(item => item.order_count);

    charts.routes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '訂單數',
                data: values,
                backgroundColor: 'rgba(153, 102, 255, 0.6)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: '顯示前 15 筆熱門路線',
                    font: { size: 12, weight: 'normal' },
                    color: '#6c757d',
                    padding: { bottom: 10 }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `路線: ${item.route}`,
                                `訂單數 ${item.order_count}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}
