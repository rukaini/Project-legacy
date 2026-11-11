import { Chart, DoughnutController, ArcElement, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { initAnalyticsTablePagination } from './analytics-table-pagination.js';

Chart.register(DoughnutController, ArcElement, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);
let charts = [];
const colors = ['#16A34A', '#94A3B8'];
const analyticsBlue = '#60A5FA';
const analyticsBlueHover = '#3B82F6';
const chartText = '#475569';
const chartGrid = 'rgba(148, 163, 184, 0.22)';
const read = (el, key) => JSON.parse(el.dataset[key] || '[]');
const destroy = () => { charts.forEach((chart) => chart.destroy()); charts = []; };
const pct = (value, total) => total ? `${Math.round(value / total * 100)}%` : '0%';
const chartPixelRatio = () => Math.max(1, Number(globalThis.devicePixelRatio) || 1);
const isTrainerAnalytics = (root) => Boolean(root?.closest?.('.ana-shell') || root?.querySelector?.('.ana-shell'));

export function wrapAnalyticsChartLabel(value, lineLength = 24) {
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

const trainerChartOptions = () => ({
    devicePixelRatio: chartPixelRatio(),
    animation: { duration: 350, easing: 'easeOutQuart' },
    layout: { padding: { top: 4, right: 12, bottom: 4, left: 4 } },
});

const trainerTooltipStyle = {
    backgroundColor: '#0F172A',
    titleColor: '#FFFFFF',
    bodyColor: '#E2E8F0',
    titleFont: { size: 12, weight: '700' },
    bodyFont: { size: 11.5, weight: '500' },
    padding: 11,
    cornerRadius: 9,
};

const trainerVerticalScales = () => ({
    y: {
        beginAtZero: true,
        border: { display: false },
        ticks: { color: chartText, font: { size: 11, weight: '600' }, precision: 0, padding: 8 },
        grid: { color: chartGrid, drawTicks: false },
    },
    x: {
        border: { display: false },
        ticks: { color: chartText, font: { size: 11.5, weight: '600' }, padding: 8 },
        grid: { display: false },
    },
});

const trainerHorizontalScales = () => ({
    x: {
        min: 0,
        max: 100,
        border: { display: false },
        ticks: { color: chartText, font: { size: 11, weight: '600' }, maxTicksLimit: 6, padding: 8, callback: (value) => `${value}%` },
        grid: { color: chartGrid, drawTicks: false },
    },
    y: {
        border: { display: false },
        ticks: { autoSkip: false, color: chartText, font: { size: 11.5, weight: '600' }, padding: 10 },
        grid: { display: false },
    },
});

function applyTrainerChartTheme(root) {
    if (!isTrainerAnalytics(root)) return;

    const themeChart = (selector, applyTheme) => {
        const canvas = root.querySelector(selector);
        const chart = canvas ? Chart.getChart(canvas) : null;
        if (!chart) return;

        Object.assign(chart.options, trainerChartOptions());
        Object.assign(chart.options.plugins.tooltip, trainerTooltipStyle);
        applyTheme(chart, canvas);
        chart.update('none');
    };

    themeChart('#attendanceDonutChart', (chart) => {
        const dataset = chart.data.datasets[0];
        dataset.borderWidth = 4;
        dataset.hoverOffset = 6;
        chart.options.cutout = '69%';
    });

    themeChart('#attendanceBarChart', (chart) => {
        const dataset = chart.data.datasets[0];
        dataset.borderRadius = 6;
        dataset.maxBarThickness = 38;
        dataset.categoryPercentage = 0.7;
        dataset.barPercentage = 0.82;
        chart.options.scales = trainerVerticalScales();
    });

    themeChart('#quizComparisonChart', (chart, canvas) => {
        const specificQuiz = canvas.dataset.specific === '1';
        if (specificQuiz) {
            chart.data.datasets[0].backgroundColor = ['#DBEAFE', '#BFDBFE', '#93C5FD', analyticsBlue];
            chart.data.datasets[0].hoverBackgroundColor = ['#BFDBFE', '#93C5FD', analyticsBlue, analyticsBlueHover];
            chart.data.datasets[0].borderRadius = 6;
            chart.data.datasets[0].maxBarThickness = 38;
            chart.options.scales = trainerVerticalScales();
            return;
        }

        chart.data.labels = chart.data.labels.map((label) => wrapAnalyticsChartLabel(
            Array.isArray(label) ? label.join(' ') : label
        ));
        chart.data.datasets.forEach((dataset) => {
            if (String(dataset.backgroundColor).toUpperCase() === '#2563EB') {
                dataset.backgroundColor = analyticsBlue;
                dataset.hoverBackgroundColor = analyticsBlueHover;
            }
            dataset.borderRadius = 6;
            dataset.maxBarThickness = 24;
            dataset.categoryPercentage = 0.72;
            dataset.barPercentage = 0.82;
        });
        chart.options.interaction = { mode: 'nearest', axis: 'y', intersect: false };
        chart.options.scales = trainerHorizontalScales();
    });
}

export function scoreBandCounts(rows) {
    const values = [0, 0, 0, 0];
    rows.forEach((row) => {
        const score = Number(row.percentage);
        if (Number.isFinite(score)) values[score < 40 ? 0 : (score < 60 ? 1 : (score < 80 ? 2 : 3))]++;
    });

    return values;
}

export function initAnalyticsCharts(root = document) {
    destroy();
    const donut = root.querySelector('#attendanceDonutChart');
    if (donut) {
        const values = read(donut, 'values'); const labels = read(donut, 'labels'); const total = values.reduce((a, b) => a + Number(b), 0);
        charts.push(new Chart(donut, { type: 'doughnut', data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 7 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => [`${ctx.label}`, `${ctx.raw} participant-session slots`, `${pct(ctx.raw, total)} of expected records`] } } } } }));
    }
    const attendanceBar = root.querySelector('#attendanceBarChart');
    if (attendanceBar) {
        const values = read(attendanceBar, 'values'); const labels = read(attendanceBar, 'labels'); const total = values.reduce((a, b) => a + Number(b), 0);
        charts.push(new Chart(attendanceBar, { type: 'bar', data: { labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 7, borderSkipped: false, maxBarThickness: 48 }] }, options: { responsive: true, maintainAspectRatio: false, animation: { duration: 350 }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => [`${ctx.raw} participant-session slots`, `${pct(ctx.raw, total)} of expected records`] } } } } }));
    }
    const quiz = root.querySelector('#quizComparisonChart');
    if (quiz) {
        const rows = read(quiz, 'results'); const specific = quiz.dataset.specific === '1'; const sorted = rows.filter((row) => row.average !== null && row.average !== undefined).sort((a, b) => Number(b.average) - Number(a.average));
        if (specific) {
            const values = scoreBandCounts(rows);
            const total = values.reduce((sum, value) => sum + value, 0);
            charts.push(new Chart(quiz, { type: 'bar', data: { labels: ['0–39%', '40–59%', '60–79%', '80–100%'], datasets: [{ label: 'Graded attempts', data: values, backgroundColor: ['#93C5FD', '#60A5FA', '#3B82F6', '#2563EB'], borderRadius: 7, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => [`${ctx.raw} graded attempts`, `${pct(ctx.raw, total)} share of graded attempts`] } } } } }));
        } else if (sorted.length === 1) {
            const row = sorted[0]; charts.push(new Chart(quiz, { type: 'bar', data: { labels: [row.title], datasets: [{ label: 'Average', data: [row.average], backgroundColor: '#2563EB', borderRadius: 8, borderSkipped: false }, { label: 'Lowest', data: [row.lowest], backgroundColor: '#F59E0B', borderRadius: 8, borderSkipped: false }, { label: 'Highest', data: [row.highest], backgroundColor: '#10B981', borderRadius: 8, borderSkipped: false }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { min: 0, max: 100, ticks: { callback: (v) => `${v}%` }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } }, plugins: { tooltip: { callbacks: { afterBody: () => [`Attempts: ${row.attempts}`, `Average: ${row.average ?? '—'}`, `Highest: ${row.highest ?? '—'}`, `Lowest: ${row.lowest ?? '—'}`] } } } } }));
        } else {
            const display = sorted.slice(0, 8); charts.push(new Chart(quiz, { type: 'bar', data: { labels: display.map((row) => row.title), datasets: [{ label: 'Average mark', data: display.map((row) => row.average), backgroundColor: '#2563EB', borderRadius: 7, borderSkipped: false, maxBarThickness: 30 }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: { duration: 350 }, scales: { x: { min: 0, max: 100, ticks: { callback: (v) => `${v}%` }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { afterBody: (items) => { const row = display[items[0].dataIndex]; return row ? [`Attempts: ${row.attempts}`, `Average: ${row.average ?? '—'}`, `Highest: ${row.highest ?? '—'}`, `Lowest: ${row.lowest ?? '—'}`] : []; } } } } } }));
        }
    }
    applyTrainerChartTheme(root);
}

export function initAnalyticsTables(root = document) {
    initAnalyticsTablePagination(root, '.ana-attendance-panel .ana-table, .ana-quiz-section .ana-table');
}
if (typeof window !== 'undefined') {
    window.initAnalyticsCharts = initAnalyticsCharts;
    window.destroyAnalyticsCharts = destroy;
    window.initAnalyticsTables = initAnalyticsTables;
}
