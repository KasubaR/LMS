import { Chart } from 'chart.js/auto';

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value !== '' ? value : fallback;
}

function palette() {
    return {
        accent: cssVar('--color-accent-500', '#2f9e6f'),
        accent2: cssVar('--color-accent-2-500', '#3aa17a'),
        danger: cssVar('--color-danger', '#e5484d'),
        neutral: cssVar('--color-neutral-500', '#9397ab'),
        grid: cssVar('--color-divider', 'rgba(26,29,39,0.12)'),
        text: cssVar('--color-text', '#1a1d27'),
        muted: cssVar('--color-neutral-500', '#7e8499'),
    };
}

function baseOptions(colors) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: colors.muted },
            },
        },
        scales: {
            x: {
                ticks: { color: colors.muted },
                grid: { color: colors.grid },
            },
            y: {
                ticks: { color: colors.muted },
                grid: { color: colors.grid },
                beginAtZero: true,
            },
        },
    };
}

const chartInstances = new Map();

function destroyCharts() {
    chartInstances.forEach((chart) => chart.destroy());
    chartInstances.clear();
}

function createChart(canvas, config) {
    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    const chart = new Chart(canvas, config);
    chartInstances.set(canvas.id, chart);

    return chart;
}

function buildCharts(data) {
    const colors = palette();
    const canvasIds = [
        'chart-monthly-lending',
        'chart-monthly-collections',
        'chart-loan-status',
        'chart-top-borrowers',
        'chart-loan-growth',
    ];

    if (!canvasIds.some((id) => document.getElementById(id))) {
        return;
    }

    destroyCharts();

    const lendingCanvas = document.getElementById('chart-monthly-lending');
    if (lendingCanvas) {
        createChart(lendingCanvas, {
            type: 'bar',
            data: {
                labels: data.monthly_lending.labels,
                datasets: [{
                    label: 'Principal Disbursed',
                    data: data.monthly_lending.data,
                    backgroundColor: colors.accent,
                    borderRadius: 4,
                }],
            },
            options: { ...baseOptions(colors), plugins: { legend: { display: false } } },
        });
    }

    const collectionsCanvas = document.getElementById('chart-monthly-collections');
    if (collectionsCanvas) {
        createChart(collectionsCanvas, {
            type: 'bar',
            data: {
                labels: data.monthly_collections.labels,
                datasets: [{
                    label: 'Collections',
                    data: data.monthly_collections.data,
                    backgroundColor: colors.accent2,
                    borderRadius: 4,
                }],
            },
            options: { ...baseOptions(colors), plugins: { legend: { display: false } } },
        });
    }

    const statusCanvas = document.getElementById('chart-loan-status');
    if (statusCanvas) {
        createChart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: data.loan_status.labels,
                datasets: [{
                    data: data.loan_status.data,
                    backgroundColor: [
                        colors.neutral,
                        cssVar('--color-accent-300', '#7fd1ab'),
                        cssVar('--color-accent-2-300', '#8fd0b3'),
                        colors.accent,
                        colors.danger,
                        colors.accent2,
                        cssVar('--color-accent-700', '#1f6b48'),
                        cssVar('--color-neutral-700', '#595d6c'),
                    ],
                    borderColor: cssVar('--color-surface', '#f3f5f9'),
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: colors.muted, boxWidth: 12 } } },
            },
        });
    }

    const borrowersCanvas = document.getElementById('chart-top-borrowers');
    if (borrowersCanvas) {
        createChart(borrowersCanvas, {
            type: 'bar',
            data: {
                labels: data.top_borrowers.labels,
                datasets: [{
                    label: 'Total Principal Borrowed',
                    data: data.top_borrowers.data,
                    backgroundColor: colors.accent,
                    borderRadius: 4,
                }],
            },
            options: {
                ...baseOptions(colors),
                indexAxis: 'y',
                plugins: { legend: { display: false } },
            },
        });
    }

    const growthCanvas = document.getElementById('chart-loan-growth');
    if (growthCanvas) {
        createChart(growthCanvas, {
            type: 'line',
            data: {
                labels: data.loan_growth.labels,
                datasets: [{
                    label: 'Total Loans',
                    data: data.loan_growth.data,
                    borderColor: colors.accent,
                    backgroundColor: colors.accent,
                    tension: 0.3,
                    fill: false,
                }],
            },
            options: { ...baseOptions(colors), plugins: { legend: { display: false } } },
        });
    }
}

export function initDashboardCharts(data) {
    window.__lmsDashboardChartData = data;
    buildCharts(data);

    if (window.__lmsDashboardThemeBound) {
        return;
    }

    window.__lmsDashboardThemeBound = true;

    window.addEventListener('themechange', () => {
        if (window.__lmsDashboardChartData) {
            buildCharts(window.__lmsDashboardChartData);
        }
    });
}
