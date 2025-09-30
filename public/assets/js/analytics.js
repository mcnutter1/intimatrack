document.addEventListener('DOMContentLoaded', () => {
  const dataEl = document.getElementById('analytics-data');
  if (!dataEl) return;

  let payload;
  try {
    payload = JSON.parse(dataEl.textContent || '{}');
  } catch (err) {
    console.warn('Unable to parse analytics payload', err);
    return;
  }

  const {
    byPartner = [],
    byLocation = [],
    frequency = [],
    scenarioStats = [],
    partnerSatisfaction = [],
    scenarioLabels = {}
  } = payload;

  const createChart = (ctx, config) => {
    if (!ctx || typeof Chart === 'undefined') return null;
    return new Chart(ctx, config);
  };

  const chartPartnerEl = document.getElementById('chartPartner');
  if (chartPartnerEl && byPartner.length) {
    createChart(chartPartnerEl, {
      type: 'bar',
      data: {
        labels: byPartner.map(r => r.name),
        datasets: [
          {
            label: 'Physical',
            data: byPartner.map(r => Number((r.pavg ?? 0).toFixed(2))),
            backgroundColor: '#64748b'
          },
          {
            label: 'Emotional',
            data: byPartner.map(r => Number((r.eavg ?? 0).toFixed(2))),
            backgroundColor: '#6b74ff'
          },
          {
            label: 'Overall',
            data: byPartner.map(r => Number((r.ravg ?? 0).toFixed(2))),
            backgroundColor: '#22c55e'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, max: 10 }
        }
      }
    });
  }

  const chartLocationEl = document.getElementById('chartLocation');
  if (chartLocationEl && byLocation.length) {
    createChart(chartLocationEl, {
      type: 'bar',
      data: {
        labels: byLocation.map(r => r.label),
        datasets: [
          {
            label: 'Physical avg',
            data: byLocation.map(r => Number((r.pavg ?? 0).toFixed(2))),
            backgroundColor: '#9333ea'
          },
          {
            label: 'Emotional avg',
            data: byLocation.map(r => Number((r.eavg ?? 0).toFixed(2))),
            backgroundColor: '#0ea5e9'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, max: 10 }
        }
      }
    });
  }

  const chartFreqEl = document.getElementById('chartFreq');
  if (chartFreqEl && frequency.length) {
    createChart(chartFreqEl, {
      type: 'line',
      data: {
        labels: frequency.map(r => r.d),
        datasets: [
          {
            label: 'Encounters',
            data: frequency.map(r => Number(r.c || 0)),
            borderColor: '#6b74ff',
            backgroundColor: 'rgba(107, 116, 255, 0.2)',
            tension: 0.3,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  const chartScenarioEl = document.getElementById('chartScenario');
  if (chartScenarioEl && scenarioStats.length) {
    const labels = scenarioStats.map(row => scenarioLabels[row.scenario] || row.scenario.replace(/_/g, ' '));
    const rounds = scenarioStats.map(row => Number(row.rounds || 0));
    const satisfaction = scenarioStats.map(row => row.avg_satisfaction !== null ? Number(row.avg_satisfaction.toFixed(2)) : null);

    createChart(chartScenarioEl, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Rounds',
            data: rounds,
            backgroundColor: '#6b74ff',
            yAxisID: 'y'
          },
          {
            type: 'line',
            label: 'Avg satisfaction',
            data: satisfaction,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.2)',
            yAxisID: 'y1',
            tension: 0.3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true },
          y1: {
            beginAtZero: true,
            position: 'right',
            suggestedMin: 0,
            suggestedMax: 10,
            grid: { drawOnChartArea: false }
          }
        }
      }
    });
  }

  const chartPartnerSatEl = document.getElementById('chartPartnerSatisfaction');
  if (chartPartnerSatEl && partnerSatisfaction.length) {
    const labels = partnerSatisfaction.map(row => row.name);
    const satisfaction = partnerSatisfaction.map(row => row.avg_satisfaction !== null ? Number(row.avg_satisfaction.toFixed(2)) : 0);
    const duration = partnerSatisfaction.map(row => Number(row.total_duration || 0));

    createChart(chartPartnerSatEl, {
      data: {
        labels,
        datasets: [
          {
            type: 'bar',
            label: 'Avg satisfaction',
            data: satisfaction,
            backgroundColor: '#f97316',
            yAxisID: 'y1'
          },
          {
            type: 'line',
            label: 'Total duration (min)',
            data: duration,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            yAxisID: 'y',
            tension: 0.3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Minutes' } },
          y1: {
            beginAtZero: true,
            position: 'right',
            suggestedMin: 0,
            suggestedMax: 10,
            title: { display: true, text: 'Satisfaction' },
            grid: { drawOnChartArea: false }
          }
        }
      }
    });
  }
});
