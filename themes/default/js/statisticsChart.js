/**
 * Enhanced Statistics Chart Script (stable)
 * File: themes/default/js/statisticsChartEnhanced.js
 * Improvements:
 * - Waits for window.Chart and payload before drawing.
 * - Handles empty datasets (shows empty state, avoids runtime errors).
 * - Sane defaults for ticks/legend across themes.
 */

(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function hasAnyValues(arr) {
    return Array.isArray(arr) && arr.some(v => Number(v || 0) > 0);
  }

  function show(elId, visible) {
    var el = document.getElementById(elId);
    if (el) el.style.display = visible ? 'block' : 'none';
  }

  function parsePayload() {
    var el = document.getElementById('statisticsChartData');
    if (!el) return null;
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      console.error('Failed to parse chart data:', e);
      return null;
    }
  }

  function waitForChart(cb, tries) {
    tries = tries || 0;
    if (window.Chart) return cb();
    if (tries > 50) return; // give up ~5s max
    setTimeout(function () { waitForChart(cb, tries + 1); }, 100);
  }

  function initMainChart(chartData) {
    var canvas = document.getElementById('statisticsChart');
    if (!canvas) return;

    var anySeriesHasData =
      hasAnyValues(chartData.views) ||
      hasAnyValues(chartData.followers) ||
      hasAnyValues(chartData.likes) ||
      hasAnyValues(chartData.comments);

    show('trendEmpty', !anySeriesHasData);
    if (!anySeriesHasData) return;

    new Chart(canvas, {
      type: 'line',
      data: {
        labels: Array.isArray(chartData.labels) ? chartData.labels : [],
        datasets: [
          {
            label: 'Profile Views',
            data: chartData.views || [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.10)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          },
          {
            label: 'New Followers',
            data: chartData.followers || [],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.10)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          },
          {
            label: 'Likes',
            data: chartData.likes || [],
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.10)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#f59e0b',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          },
          {
            label: 'Comments',
            data: chartData.comments || [],
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139,92,246,0.10)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#8b5cf6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: { padding: 12, usePointStyle: true, pointStyle: 'circle' }
          },
          tooltip: {
            enabled: true,
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1,
            padding: 10,
            callbacks: {
              label: function (ctx) {
                var label = ctx.dataset.label ? ctx.dataset.label + ': ' : '';
                return label + new Intl.NumberFormat().format(ctx.parsed.y || 0);
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false, drawBorder: false },
            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.06)', drawBorder: false },
            ticks: {
              callback: function (v) { return new Intl.NumberFormat().format(v); }
            }
          }
        }
      }
    });
  }

  function initComparisonChart(chartData) {
    var canvas = document.getElementById('comparisonChart');
    if (!canvas) return;

    var c = chartData.comparison || {};
    var cur = (c.current || {});
    var prv = (c.previous || {});

    var hasCur = (cur.views|0) + (cur.followers|0) + (cur.likes|0) + (cur.comments|0) > 0;
    var hasPrv = (prv.views|0) + (prv.followers|0) + (prv.likes|0) + (prv.comments|0) > 0;

    var hasAny = hasCur || hasPrv;
    show('comparisonEmpty', !hasAny);
    if (!hasAny) return;

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: ['Views', 'Followers', 'Likes', 'Comments'],
        datasets: [
          {
            label: 'Current Period',
            data: [cur.views|0, cur.followers|0, cur.likes|0, cur.comments|0],
            backgroundColor: 'rgba(59,130,246,0.8)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            borderRadius: 6
          },
          {
            label: 'Previous Period',
            data: [prv.views|0, prv.followers|0, prv.likes|0, prv.comments|0],
            backgroundColor: 'rgba(156,163,175,0.5)',
            borderColor: '#9ca3af',
            borderWidth: 2,
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: { padding: 12, usePointStyle: true, pointStyle: 'rect' }
          },
          tooltip: {
            enabled: true,
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 10,
            callbacks: {
              label: function(ctx){
                var label = (ctx.dataset.label || '') + ': ';
                var val = ctx.parsed.y || 0;
                var other = ctx.chart.data.datasets[ctx.datasetIndex === 0 ? 1 : 0].data[ctx.dataIndex] || 0;
                var pct = (other > 0 && ctx.datasetIndex === 0)
                  ? ' (' + (( (val - other) / other ) * 100).toFixed(1) + '%)'
                  : '';
                return label + new Intl.NumberFormat().format(val) + pct;
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false, drawBorder: false } },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.06)', drawBorder: false },
            ticks: { callback: v => new Intl.NumberFormat().format(v) }
          }
        }
      }
    });
  }

  function populateDataTable(rows) {
    var tbody = document.getElementById('statsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#6b7280;">No data in this period</td></tr>';
      return;
    }

    var reversed = rows.slice().reverse();
    reversed.forEach(function (r) {
      var totalEng = (r.likes|0) + (r.comments|0);
      var tr = document.createElement('tr');
      tr.innerHTML = [
        '<td><strong>' + (r.date || '') + '</strong></td>',
        '<td>' + new Intl.NumberFormat().format(r.views|0) + '</td>',
        '<td>' + new Intl.NumberFormat().format(r.followers|0) + '</td>',
        '<td>' + new Intl.NumberFormat().format(r.likes|0) + '</td>',
        '<td>' + new Intl.NumberFormat().format(r.comments|0) + '</td>',
        '<td><strong>' + new Intl.NumberFormat().format(totalEng) + '</strong></td>'
      ].join('');
      tbody.appendChild(tr);
    });
  }

  function exportToCSV() {
    var el = document.getElementById('statisticsChartData');
    if (!el) return;
    var data;
    try { data = JSON.parse(el.textContent); } catch (e) { return; }

    var csv = 'Date,Views,Followers,Likes,Comments,Total Engagement\n';
    (data.tableData || []).forEach(function (r) {
      var total = (r.likes|0) + (r.comments|0);
      csv += [r.date, r.views|0, r.followers|0, r.likes|0, r.comments|0, total].join(',') + '\n';
    });

    csv += '\nSummary\nMetric,Current Period,Previous Period,Change %\n';
    function pct(cur, prv){ if((prv|0)===0) return (cur|0)>0 ? 100 : 0; return (((cur-prv)/prv)*100).toFixed(1); }
    var c = data.comparison || {}, cur=c.current||{}, prv=c.previous||{};
    csv += 'Views,'     + (cur.views|0)     + ',' + (prv.views|0)     + ',' + pct(cur.views|0,     prv.views|0)     + '%\n';
    csv += 'Followers,' + (cur.followers|0) + ',' + (prv.followers|0) + ',' + pct(cur.followers|0, prv.followers|0) + '%\n';
    csv += 'Likes,'     + (cur.likes|0)     + ',' + (prv.likes|0)     + ',' + pct(cur.likes|0,     prv.likes|0)     + '%\n';
    csv += 'Comments,'  + (cur.comments|0)  + ',' + (prv.comments|0)  + ',' + pct(cur.comments|0,  prv.comments|0)  + '%\n';

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href = url;
    a.download = 'statistics_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  // expose small helpers globally (your PHP calls these)
  window.toggleCustomDatePicker = function () {
    var picker = document.getElementById('customDatePicker');
    if (picker) picker.style.display = (picker.style.display === 'none') ? 'flex' : 'none';
  };
  window.exportToCSV = exportToCSV;

  ready(function () {
    waitForChart(function () {
      var data = parsePayload();
      if (!data) return;

      populateDataTable(data.tableData);
      initMainChart(data);
      initComparisonChart(data);
    });
  });
})();
