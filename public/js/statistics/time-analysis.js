// 等待 Alpine.js 載入後註冊組件
document.addEventListener('alpine:init', () => {
    // 時間分析頁面 Alpine.js 組件
    Alpine.data('timeAnalysis', () => ({
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
                let exportUrl = `/statistics/export/time-analysis?start_date=${startDate}&end_date=${endDate}`;

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

    // 日期範圍篩選器 Alpine.js 組件（時間分析版本）
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
                let apiUrl = `/statistics/api/time-analysis?start_date=${this.startDate}&end_date=${this.endDate}`;

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
    updatePeakHoursChart(data.peak_hours);
    updateWeekdayChart(data.weekday_distribution);
    updateMonthlyTrendsChart(data.monthly_trends);
    updateAdvanceBookingChart(data.advance_booking);
}

// 更新尖峰時段圖表
function updatePeakHoursChart(data) {
    const ctx = document.getElementById('peakHoursChart');
    if (!ctx) return;

    if (charts.peakHours) {
        charts.peakHours.destroy();
    }

    const labels = data.hourly_data.map(item => item.hour);
    const values = data.hourly_data.map(item => item.order_count);

    charts.peakHours = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '訂單數',
                data: values,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
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
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data.hourly_data[context.dataIndex];
                            return [
                                `訂單數: ${item.order_count}`,
                                `獨特客戶數: ${item.unique_customers}`
                            ];
                        },
                        title: function (context) {
                            return `時段: ${context[0].label}`;
                        }
                    }
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

// 更新週間分布圖表
function updateWeekdayChart(data) {
    const ctx = document.getElementById('weekdayChart');
    if (!ctx) return;

    if (charts.weekday) {
        charts.weekday.destroy();
    }

    const labels = data.weekday_data.map(item => item.weekday);
    const values = data.weekday_data.map(item => item.order_count);

    charts.weekday = new Chart(ctx, {
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
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = data.weekday_data[context.dataIndex];
                            return [
                                `訂單數: ${item.order_count}`,
                                `獨特客戶數: ${item.unique_customers}`
                            ];
                        }
                    }
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

// 更新月份趨勢圖表
function updateMonthlyTrendsChart(data) {
    const ctx = document.getElementById('monthlyTrendsChart');
    if (!ctx) return;

    if (charts.monthlyTrends) {
        charts.monthlyTrends.destroy();
    }

    const labels = data.monthly_data.map(item => item.month_name);
    const totalValues = data.monthly_data.map(item => item.order_count);
    const assignedValues = data.monthly_data.map(item => item.assigned_count);
    const openValues = data.monthly_data.map(item => item.open_count);

    charts.monthlyTrends = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '總訂單數',
                    data: totalValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: '已指派',
                    data: assignedValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: '待派遣',
                    data: openValues,
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
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

// 更新提前預約圖表
function updateAdvanceBookingChart(data) {
    const ctx = document.getElementById('advanceBookingChart');
    if (!ctx) return;

    if (charts.advanceBooking) {
        charts.advanceBooking.destroy();
    }

    // 只顯示前 30 天的分布
    const labels = data.advance_booking_data.map(item => `${item.advance_days} 天`);
    const values = data.advance_booking_data.map(item => item.order_count);

    // 建立分類數據用於餅圖或長條圖
    const categoryLabels = ['當天預約', '3天內', '7天內', '7天以上'];
    const categoryValues = [
        data.categories.same_day,
        data.categories.within_3_days,
        data.categories.within_7_days,
        data.categories.more_than_7_days
    ];
    const categoryPercentages = [
        data.percentages.same_day_pct,
        data.percentages.within_3_days_pct,
        data.percentages.within_7_days_pct,
        data.percentages.more_than_7_days_pct
    ];

    charts.advanceBooking = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: '訂單數',
                data: categoryValues,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed.y;
                            const percentage = categoryPercentages[context.dataIndex];
                            return [
                                `訂單數: ${value}`,
                                `占比: ${percentage}%`,
                                `平均提前天數: ${data.avg_advance_days} 天`
                            ];
                        }
                    }
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
