// 等待 Alpine.js 載入後註冊組件
document.addEventListener('alpine:init', () => {
    // 地理分析頁面 Alpine.js 組件
    Alpine.data('geographyAnalysis', () => ({
        charts: {},
        hasData: false,
        exporting: false,

        async exportReport() {
            this.exporting = true;
            try {
                const startDate = Alpine.store('dateFilter').startDate;
                const endDate = Alpine.store('dateFilter').endDate;
                const orderType = Alpine.store('dateFilter').orderType;

                // 建立匯出 URL
                let exportUrl = `/statistics/export/geography?start_date=${startDate}&end_date=${endDate}`;

                // 如果有選擇 order_type，加到 URL 參數中
                if (orderType) {
                    exportUrl += `&order_type=${encodeURIComponent(orderType)}`;
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

    // 日期範圍篩選器 Alpine.js 組件
    Alpine.data('dateRangeFilter', () => ({
        startDate: '',
        endDate: '',
        orderType: '',
        loading: false,
        error: '',

        init() {
            // 預設為最近 30 天
            this.setQuickRange('last30days');

            // 建立全域 store 供其他組件使用
            if (!Alpine.store('dateFilter')) {
                Alpine.store('dateFilter', {
                    startDate: this.startDate,
                    endDate: this.endDate,
                    orderType: this.orderType
                });
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
                case 'last7days':
                    const last7 = new Date(today);
                    last7.setDate(today.getDate() - 7);
                    this.startDate = formatDate(last7);
                    this.endDate = formatDate(today);
                    break;
                case 'last30days':
                    const last30 = new Date(today);
                    last30.setDate(today.getDate() - 30);
                    this.startDate = formatDate(last30);
                    this.endDate = formatDate(today);
                    break;
                case 'last90days':
                    const last90 = new Date(today);
                    last90.setDate(today.getDate() - 90);
                    this.startDate = formatDate(last90);
                    this.endDate = formatDate(today);
                    break;
                case 'thisMonth':
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    this.startDate = formatDate(firstDay);
                    this.endDate = formatDate(today);
                    break;
                case 'lastMonth':
                    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                    this.startDate = formatDate(lastMonthStart);
                    this.endDate = formatDate(lastMonthEnd);
                    break;
                case 'thisYear':
                    const yearStart = new Date(today.getFullYear(), 0, 1);
                    this.startDate = formatDate(yearStart);
                    this.endDate = formatDate(today);
                    break;
            }

            this.applyFilter();
        },

        async applyFilter() {
            this.loading = true;
            this.error = '';

            // 更新全域 store
            if (Alpine.store('dateFilter')) {
                Alpine.store('dateFilter').startDate = this.startDate;
                Alpine.store('dateFilter').endDate = this.endDate;
                Alpine.store('dateFilter').orderType = this.orderType;
            }

            try {
                // 建立 API URL
                let apiUrl = `/statistics/api/geography?start_date=${this.startDate}&end_date=${this.endDate}`;

                // 如果有選擇 order_type，加到 URL 參數中
                if (this.orderType) {
                    apiUrl += `&order_type=${encodeURIComponent(this.orderType)}`;
                }

                const response = await fetch(apiUrl);
                const data = await response.json();

                if (!response.ok) {
                    this.error = data.error || '查詢失敗';
                    this.loading = false;
                    return;
                }

                // 更新圖表
                updateCharts(data);

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

// 圖表物件
let charts = {};

// 更新所有圖表
function updateCharts(data) {
    updatePickupChart(data.pickup_locations);
    updateDropoffChart(data.dropoff_locations);
    updateCrossCountyChart(data.cross_county);
    updateRoutesChart(data.popular_routes);
}

// 更新熱門上車地點圖表
function updatePickupChart(data) {
    const ctx = document.getElementById('pickupChart');
    if (!ctx) return;

    // 銷毀舊圖表
    if (charts.pickup) {
        charts.pickup.destroy();
    }

    // 固定高度（圖表現在限制 Top 15）
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
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: '僅顯示前 15 名熱門上車區域',
                    font: {
                        size: 12,
                        weight: 'normal'
                    },
                    color: '#6c757d',
                    padding: {
                        bottom: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `訂單數: ${item.order_count}`,
                                `獨特客戶數: ${item.unique_customers}`
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

// 更新熱門下車地點圖表
function updateDropoffChart(data) {
    const ctx = document.getElementById('dropoffChart');
    if (!ctx) return;

    if (charts.dropoff) {
        charts.dropoff.destroy();
    }

    // 固定高度（圖表現在限制 Top 15）
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
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: '僅顯示前 15 名熱門下車區域',
                    font: {
                        size: 12,
                        weight: 'normal'
                    },
                    color: '#6c757d',
                    padding: {
                        bottom: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `訂單數: ${item.order_count}`,
                                `獨特客戶數: ${item.unique_customers}`
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

// 更新跨縣市訂單圖表
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
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const percentage = context.label === '同縣市訂單' ?
                                data.same_county_percentage :
                                data.cross_county_percentage;
                            return `${label}: ${value} 筆 (${percentage}%)`;
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

    // 固定高度（圖表現在限制 Top 15）
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
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: '僅顯示前 15 名熱門路線',
                    font: {
                        size: 12,
                        weight: 'normal'
                    },
                    color: '#6c757d',
                    padding: {
                        bottom: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data[context.dataIndex];
                            return [
                                `路線: ${item.route}`,
                                `訂單數: ${item.order_count}`
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
