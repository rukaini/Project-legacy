import { Chart, DoughnutController, ArcElement, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { initAnalyticsTablePagination } from './analytics-table-pagination.js';

Chart.register(DoughnutController, ArcElement, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

let charts = [];
const attendanceColors = ['#16A34A', '#94A3B8'];
const analyticsBlue = '#60A5FA';
const analyticsBlueHover = '#3B82F6';
const chartText = '#475569';
const chartGrid = 'rgba(148, 163, 184, 0.22)';
const read = (element, key) => JSON.parse(element.dataset[key] || '[]');
const percentage = (value, total) => total ? `${Math.round(Number(value) / total * 100)}%` : '0%';
const chartPixelRatio = () => Math.max(1, Number(globalThis.devicePixelRatio) || 1);

export function wrapChartLabel(value, lineLength = 24) {
    const words = String(value ?? '').trim().split(/\s+/).filter(Boolean);
    const lines = [];
    let currentLine = '';

    words.forEach((word) => {
        if (word.length > lineLength) {
            if (currentLine) {
                lines.push(currentLine);
                currentLine = '';
            }

            for (let index = 0; index < word.length; index += lineLength) {
                const chunk = word.slice(index, index + lineLength);
                if (chunk.length === lineLength) lines.push(chunk);
                else currentLine = chunk;
            }

            return;
        }

        const nextLine = currentLine ? `${currentLine} ${word}` : word;
        if (nextLine.length <= lineLength) {
            currentLine = nextLine;
            return;
        }

        if (currentLine) lines.push(currentLine);
        currentLine = word;
    });

    if (currentLine) lines.push(currentLine);

    return lines.length ? lines : [''];
}

const horizontalScales = () => ({
    x: {
        min: 0,
        max: 100,
        border: { display: false },
        ticks: {
            color: chartText,
            font: { size: 11, weight: '600' },
            maxTicksLimit: 6,
            padding: 8,
            callback: (value) => `${value}%`,
        },
        grid: { color: chartGrid, drawTicks: false },
    },
    y: {
        border: { display: false },
        ticks: { autoSkip: false, color: chartText, font: { size: 11.5, weight: '600' }, padding: 10 },
        grid: { display: false },
    },
});

const tooltipStyle = {
    backgroundColor: '#0F172A',
    titleColor: '#FFFFFF',
    bodyColor: '#E2E8F0',
    titleFont: { size: 12, weight: '700' },
    bodyFont: { size: 11.5, weight: '500' },
    padding: 11,
    cornerRadius: 9,
};

function applyAdminDetailBlueTheme(root) {
    root.querySelectorAll('#quizComparisonChart').forEach((canvas) => {
        const chart = Chart.getChart(canvas);
        if (!chart) return;

        chart.data.datasets.forEach((dataset) => {
            if (Array.isArray(dataset.backgroundColor)) {
                dataset.backgroundColor = dataset.backgroundColor.map((color) => (
                    String(color).toUpperCase() === '#2563EB' ? analyticsBlueHover : color
                ));
                return;
            }

            if (String(dataset.backgroundColor).toUpperCase() === '#2563EB') {
                dataset.backgroundColor = analyticsBlue;
                dataset.hoverBackgroundColor = analyticsBlueHover;
            }
        });
        chart.update('none');
    });
}

export function topEightRows(rows, valueKey) {
    return [...rows]
        .filter((row) => row[valueKey] !== null && row[valueKey] !== undefined)
        .sort((left, right) => Number(right[valueKey]) - Number(left[valueKey]))
        .slice(0, 8);
}

export function destroyAdminAnalyticsCharts() {
    charts.forEach((chart) => chart.destroy());
    charts = [];
}

export function initAdminAnalyticsCharts(root = document) {
    destroyAdminAnalyticsCharts();

    const donut = root.querySelector('#adminAttendanceDonutChart');
    if (donut) {
        const values = read(donut, 'values');
        const labels = read(donut, 'labels');
        const total = values.reduce((sum, value) => sum + Number(value), 0);
        charts.push(new Chart(donut, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: attendanceColors, borderWidth: 3, borderColor: '#fff', hoverOffset: 7 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                devicePixelRatio: chartPixelRatio(),
                cutout: '69%',
                animation: { duration: 350, easing: 'easeOutQuart' },
                layout: { padding: 5 },
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipStyle, callbacks: { label: (context) => [context.label, `${context.raw} participant-session slots`, `${percentage(context.raw, total)} of expected records`] } },
                },
            },
        }));
    }

    const attendance = root.querySelector('#adminAttendanceTrainingChart');
    if (attendance) {
        const rows = topEightRows(read(attendance, 'results'), 'rate');
        charts.push(new Chart(attendance, {
            type: 'bar',
            data: { labels: rows.map((row) => wrapChartLabel(row.title)), datasets: [{ label: 'Record coverage', data: rows.map((row) => row.rate), backgroundColor: analyticsBlue, hoverBackgroundColor: analyticsBlueHover, borderRadius: 6, borderSkipped: false, maxBarThickness: 24, categoryPercentage: 0.72, barPercentage: 0.82 }] },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                devicePixelRatio: chartPixelRatio(),
                animation: { duration: 350, easing: 'easeOutQuart' },
                interaction: { mode: 'nearest', axis: 'y', intersect: false },
                layout: { padding: { top: 4, right: 12, bottom: 4, left: 4 } },
                scales: horizontalScales(),
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipStyle, displayColors: false, callbacks: { afterBody: (items) => { const row = rows[items[0].dataIndex]; return row ? [`Record coverage: ${row.rate}%`, `Participants: ${row.participants}`, `Sessions: ${row.sessions}`] : []; } } },
                },
            },
        }));
    }

    const quiz = root.querySelector('#adminQuizTrainingChart');
    if (quiz) {
        const rows = topEightRows(read(quiz, 'results'), 'average');
        charts.push(new Chart(quiz, {
            type: 'bar',
            data: { labels: rows.map((row) => wrapChartLabel(row.title)), datasets: [{ label: 'Average quiz mark', data: rows.map((row) => row.average), backgroundColor: analyticsBlue, hoverBackgroundColor: analyticsBlueHover, borderRadius: 6, borderSkipped: false, maxBarThickness: 24, categoryPercentage: 0.72, barPercentage: 0.82 }] },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                devicePixelRatio: chartPixelRatio(),
                animation: { duration: 350, easing: 'easeOutQuart' },
                interaction: { mode: 'nearest', axis: 'y', intersect: false },
                layout: { padding: { top: 4, right: 12, bottom: 4, left: 4 } },
                scales: horizontalScales(),
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipStyle, displayColors: false, callbacks: { afterBody: (items) => { const row = rows[items[0].dataIndex]; return row ? [`Quizzes: ${row.quizzes}`, `Graded attempts: ${row.attempts}`, `Average quiz mark: ${row.average}%`] : []; } } },
                },
            },
        }));
    }

    applyAdminDetailBlueTheme(root);
}

export function initAdminAnalyticsTables(root = document) {
    initAnalyticsTablePagination(root, '.admin-analytics-table');
}

if (typeof window !== 'undefined') {
    window.initAdminAnalyticsCharts = initAdminAnalyticsCharts;
    window.destroyAdminAnalyticsCharts = destroyAdminAnalyticsCharts;
    window.initAdminAnalyticsTables = initAdminAnalyticsTables;
}
