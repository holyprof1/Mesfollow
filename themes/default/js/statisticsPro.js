/**
 * Statistics Pro JS - FINAL VERSION
 * themes/default/js/statisticsPro.js
 */

document.addEventListener('DOMContentLoaded', function() {
    const dataEl = document.getElementById('statsData');
    if (!dataEl) return;
    
    const data = JSON.parse(dataEl.textContent);
    initComparisonChart(data);
    initTrendChart(data);
    populateTable(data.table);
});

function initComparisonChart(data) {
    const ctx = document.getElementById('comparisonChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Views', 'Followers', 'Likes', 'Comments', 'Shares', 'Engagement'],
            datasets: [{
                label: 'Current Period',
                data: data.comparison.current,
                backgroundColor: [
                    'rgba(203, 12, 159, 0.9)',
                    'rgba(121, 40, 202, 0.9)',
                    'rgba(250, 180, 41, 0.9)',
                    'rgba(255, 0, 128, 0.9)',
                    'rgba(16, 185, 129, 0.9)',
                    'rgba(82, 92, 122, 0.9)'
                ],
                borderRadius: 8,
                barThickness: 40
            }, {
                label: 'Previous Period',
                data: data.comparison.previous,
                backgroundColor: 'rgba(156, 163, 175, 0.5)',
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 13, weight: '600' },
                        padding: 14,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    padding: 12,
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y;
                            const formatted = new Intl.NumberFormat().format(value);
                            let label = context.dataset.label + ': ' + formatted;
                            
                            if (context.datasetIndex === 0) {
                                const prev = data.comparison.previous[context.dataIndex];
                                if (prev > 0) {
                                    const change = ((value - prev) / prev * 100).toFixed(1);
                                    label += ` (${change > 0 ? '+' : ''}${change}%)`;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        font: { size: 11 },
                        color: '#6b7280',
                        callback: v => {
                            if(v >= 1000000) return (v/1000000).toFixed(1) + 'M';
                            if(v >= 1000) return (v/1000).toFixed(1) + 'K';
                            return v;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 12, weight: '600' },
                        color: '#374151'
                    }
                }
            }
        }
    });
}

function initTrendChart(data) {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Views',
                data: data.trend.views,
                borderColor: '#CB0C9F',
                backgroundColor: 'rgba(203, 12, 159, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#CB0C9F',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'Followers',
                data: data.trend.followers,
                borderColor: '#7928CA',
                backgroundColor: 'rgba(121, 40, 202, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#7928CA',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'Likes',
                data: data.trend.likes,
                borderColor: '#fab429',
                backgroundColor: 'rgba(250, 180, 41, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fab429',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'Comments',
                data: data.trend.comments,
                borderColor: '#FF0080',
                backgroundColor: 'rgba(255, 0, 128, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#FF0080',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 13, weight: '600' },
                        padding: 14,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    padding: 12,
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + new Intl.NumberFormat().format(ctx.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        font: { size: 11 },
                        color: '#6b7280',
                        callback: v => {
                            if(v >= 1000000) return (v/1000000).toFixed(1) + 'M';
                            if(v >= 1000) return (v/1000).toFixed(1) + 'K';
                            return v;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#6b7280',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 12
                    }
                }
            }
        }
    });
}

function populateTable(tableData) {
    const tbody = document.getElementById('breakdownTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    [...tableData].reverse().forEach(row => {
        const total = row.views + row.followers + row.likes + row.comments;
        tbody.innerHTML += `
            <tr>
                <td><strong>${row.date}</strong></td>
                <td>${new Intl.NumberFormat().format(row.views)}</td>
                <td>${new Intl.NumberFormat().format(row.followers)}</td>
                <td>${new Intl.NumberFormat().format(row.likes)}</td>
                <td>${new Intl.NumberFormat().format(row.comments)}</td>
                <td><strong>${new Intl.NumberFormat().format(total)}</strong></td>
            </tr>
        `;
    });
}

function toggleCustomPicker() {
    const picker = document.getElementById('customDatePicker');
    if (picker) {
        picker.style.display = picker.style.display === 'none' ? 'flex' : 'none';
    }
}

function toggleBreakdown() {
    const content = document.getElementById('detailedBreakdown');
    const icon = document.querySelector('.stat_toggle_icon');
    if (content && icon) {
        const isHidden = content.style.display === 'none';
        content.style.display = isHidden ? 'block' : 'none';
        icon.textContent = isHidden ? '▲' : '▼';
    }
}

function exportStats() {
    const dataEl = document.getElementById('statsData');
    if (!dataEl) return;
    
    const data = JSON.parse(dataEl.textContent);
    let csv = 'Date,Views,Followers,Likes,Comments,Total\n';
    
    data.table.forEach(row => {
        const total = row.views + row.followers + row.likes + row.comments;
        csv += `${row.date},${row.views},${row.followers},${row.likes},${row.comments},${total}\n`;
    });
    
    csv += '\nComparison Summary\n';
    csv += 'Metric,Current,Previous,Change %\n';
    const metrics = ['Views', 'Followers', 'Likes', 'Comments', 'Shares', 'Engagement Rate'];
    metrics.forEach((m, i) => {
        const curr = data.comparison.current[i];
        const prev = data.comparison.previous[i];
        const change = prev > 0 ? ((curr - prev) / prev * 100).toFixed(1) + '%' : 'N/A';
        csv += `${m},${curr},${prev},${change}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `statistics_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

window.toggleCustomPicker = toggleCustomPicker;
window.toggleBreakdown = toggleBreakdown;
window.exportStats = exportStats;