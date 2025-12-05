document.addEventListener('alpine:init', () => {
    // 客服統計頁面 Alpine.js 組件
    Alpine.data('customerServiceAnalysis', () => ({
        charts: {},
        hasData: false,
        exporting: false,

        async exportReport() {
            this.exporting = true;
            try {
                const store = Alpine.store('customerServiceFilter');
                const startDate = store.startDate;
                const endDate = store.endDate;
                const orderType = store.orderType;
                const createdBy = store.createdBy;
                const orderStatus = store.orderStatus;

                let exportUrl = `/statistics/export/customer-service?start_date=${startDate}&end_date=${endDate}`;

                if (orderType) {
                    exportUrl += `&order_type=${encodeURIComponent(orderType)}`;
                }
                if (createdBy) {
                    exportUrl += `&created_by=${encodeURIComponent(createdBy)}`;
                }
                if (orderStatus) {
                    exportUrl += `&status=${encodeURIComponent(orderStatus)}`;
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

    // 客服統計篩選表單 Alpine.js 組件
    Alpine.data('customerServiceFilter', () => ({
        startDate: '',
        endDate: '',
        orderType: '',
        createdBy: '',
        orderStatus: '',
        availableUsers: [],
        loading: false,
        error: '',

        init() {
            // 預設查詢最近 30 天
            this.setQuickRange('last30days');

            // 載入建單人員清單
            this.fetchAvailableUsers();

            // 建立共用 store 供其他組件取用
            if (!Alpine.store('customerServiceFilter')) {
                Alpine.store('customerServiceFilter', {
                    startDate: this.startDate,
                    endDate: this.endDate,
                    orderType: this.orderType,
                    createdBy: this.createdBy,
                    orderStatus: this.orderStatus
                });
            }
        },

        async fetchAvailableUsers() {
            try {
                const response = await fetch('/statistics/api/customer-service/users');
                const data = await response.json();
                this.availableUsers = data.users;
            } catch (error) {
                console.error('載入建單人員清單失敗:', error);
            }
        },

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
                    const last7 = new Date(today);
                    last7.setDate(today.getDate() - 7);
                    this.startDate = formatDate(last7);
                    this.endDate = formatDate(today);
                    break;
                }
                case 'last30days': {
                    const last30 = new Date(today);
                    last30.setDate(today.getDate() - 30);
                    this.startDate = formatDate(last30);
                    this.endDate = formatDate(today);
                    break;
                }
                case 'last90days': {
                    const last90 = new Date(today);
                    last90.setDate(today.getDate() - 90);
                    this.startDate = formatDate(last90);
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
                    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                    this.startDate = formatDate(lastMonthStart);
                    this.endDate = formatDate(lastMonthEnd);
                    break;
                }
                case 'thisYear': {
                    const yearStart = new Date(today.getFullYear(), 0, 1);
                    this.startDate = formatDate(yearStart);
                    this.endDate = formatDate(today);
                    break;
                }
            }

            this.applyFilter();
        },

        async applyFilter() {
            this.loading = true;
            this.error = '';

            if (Alpine.store('customerServiceFilter')) {
                Alpine.store('customerServiceFilter').startDate = this.startDate;
                Alpine.store('customerServiceFilter').endDate = this.endDate;
                Alpine.store('customerServiceFilter').orderType = this.orderType;
                Alpine.store('customerServiceFilter').createdBy = this.createdBy;
                Alpine.store('customerServiceFilter').orderStatus = this.orderStatus;
            }

            try {
                let apiUrl = `/statistics/api/customer-service?start_date=${this.startDate}&end_date=${this.endDate}`;

                if (this.orderType) {
                    apiUrl += `&order_type=${encodeURIComponent(this.orderType)}`;
                }
                if (this.createdBy) {
                    apiUrl += `&created_by=${encodeURIComponent(this.createdBy)}`;
                }
                if (this.orderStatus) {
                    apiUrl += `&status=${encodeURIComponent(this.orderStatus)}`;
                }

                const response = await fetch(apiUrl);
                const data = await response.json();

                if (!response.ok) {
                    this.error = data.error || '查詢失敗';
                    this.loading = false;
                    return;
                }

                updateAllCharts(data);
            } catch (error) {
                console.error('查詢失敗:', error);
                this.error = '查詢失敗，請稍後再試';
            } finally {
                this.loading = false;
            }
        },

        displayDateRange() {
            if (!this.startDate || !this.endDate) {
                return '未選擇';
            }
            return `${this.startDate} 至 ${this.endDate}`;
        }
    }));
});

// Chart instances
let charts = {};

function updateAllCharts(data) {
    updateUserOrderCountChart(data.order_count_by_user);
    updateUserOrderTypesChart(data.order_types_by_user);
    updateHourlyChart(data.orders_by_hour);
    updateOrderTypeSummaryChart(data.order_type_summary);
    updateStatusDistributionChart(data.status_distribution);
}

function updateUserOrderCountChart(data) {
    const ctx = document.getElementById('userOrderCountChart');
    if (!ctx) return;

    if (charts.userOrderCount) {
        charts.userOrderCount.destroy();
    }

    const labels = data.map(item => item.user_name);
    const values = data.map(item => item.total_orders);

    charts.userOrderCount = new Chart(ctx, {
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
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `訂單數 ${item.total_orders}`,
                                `獨立客戶數 ${item.unique_customers}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function updateUserOrderTypesChart(data) {
    const ctx = document.getElementById('userOrderTypesChart');
    if (!ctx) return;

    if (charts.userOrderTypes) {
        charts.userOrderTypes.destroy();
    }

    const labels = data.users_data.map(item => item.user_name);
    const sameDayData = data.users_data.map(item => item.same_day_orders);
    const advanceData = data.users_data.map(item => item.advance_orders);

    charts.userOrderTypes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '當天訂單',
                    data: sameDayData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: '預約訂單',
                    data: advanceData,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    stacked: true
                }
            }
        }
    });
}

function updateHourlyChart(data) {
    const ctx = document.getElementById('hourlyChart');
    if (!ctx) return;

    if (charts.hourly) {
        charts.hourly.destroy();
    }

    const labels = data.hourly_data.map(item => `${item.hour}時`);
    const values = data.hourly_data.map(item => item.order_count);

    charts.hourly = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '訂單數',
                data: values,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function updateOrderTypeSummaryChart(data) {
    const ctx = document.getElementById('orderTypeSummaryChart');
    if (!ctx) return;

    if (charts.orderTypeSummary) {
        charts.orderTypeSummary.destroy();
    }

    charts.orderTypeSummary = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['當天訂單', '預約訂單'],
            datasets: [{
                data: [data.same_day_count, data.advance_count],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const percentage = context.dataIndex === 0 ?
                                data.same_day_percentage :
                                data.advance_percentage;
                            return `${label}: ${value} 筆(${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function updateStatusDistributionChart(data) {
    const ctx = document.getElementById('statusDistributionChart');
    if (!ctx) return;

    if (charts.statusDistribution) {
        charts.statusDistribution.destroy();
    }

    const statusLabelMap = {
        open: '待派遣',
        assigned: '已派遣',
        bkorder: '預約單',
        blocked: '黑名單',
        cancelled: '已取消',
        cancelledOOC: '已取消(9999)',
        cancelledNOC: '已取消(通知)',
        cancelledCOTD: '已取消(當天)',
        blacklist: '黑名單',
        no_send: '不派車',
        regular_sedans: '一般車',
        no_car: '無車可派',
    };

    const labels = data.status_data.map(item => statusLabelMap[item.status] || item.status);
    const values = data.status_data.map(item => item.order_count);

    charts.statusDistribution = new Chart(ctx, {
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
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data.status_data[context.dataIndex];
                            return [
                                `訂單數 ${item.order_count}`,
                                `百分比: ${item.percentage}%`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}
