import Chart from 'chart.js/auto';

function baseChartOptions() {
    const isDark = document.documentElement.classList.contains('dark');
    const labelColor = isDark ? '#9ca3af' : '#4b5563';
    const gridColor = isDark ? 'rgba(75, 85, 99, 0.12)' : 'rgba(229, 231, 235, 0.5)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    color: labelColor,
                    boxWidth: 8,
                    boxHeight: 8,
                    borderRadius: 4,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        family: 'Figtree, ui-sans-serif, system-ui',
                        size: 11,
                        weight: '600',
                    },
                    padding: 16,
                },
            },
            tooltip: {
                backgroundColor: isDark ? '#1f2937' : '#ffffff',
                titleColor: isDark ? '#ffffff' : '#111827',
                bodyColor: isDark ? '#d1d5db' : '#4b5563',
                borderColor: isDark ? '#374151' : '#e5e7eb',
                borderWidth: 1,
                padding: 10,
                boxPadding: 6,
                usePointStyle: true,
                borderRadius: 10,
                titleFont: {
                    family: 'Figtree, ui-sans-serif, system-ui',
                    size: 12,
                    weight: 'bold',
                },
                bodyFont: {
                    family: 'Figtree, ui-sans-serif, system-ui',
                    size: 11,
                },
            },
        },
        scales: {
            x: {
                ticks: { 
                    color: labelColor,
                    font: {
                        family: 'Figtree, ui-sans-serif, system-ui',
                        size: 11,
                        weight: '500',
                    }
                },
                grid: { 
                    display: false,
                },
                border: {
                    display: false,
                }
            },
            y: {
                beginAtZero: true,
                ticks: { 
                    color: labelColor, 
                    precision: 0,
                    font: {
                        family: 'Figtree, ui-sans-serif, system-ui',
                        size: 11,
                    }
                },
                grid: { 
                    color: gridColor,
                },
                border: {
                    display: false,
                }
            },
        },
    };
}

function initProjectComparisonChart(canvas, data) {
    if (! canvas || ! data.labels.length) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Received / Assigned to Site',
                    data: data.received,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.9)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.15)');
                        return gradient;
                    },
                    borderColor: 'rgba(99, 102, 241, 0.85)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Assigned to Employees',
                    data: data.distributed,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
                        gradient.addColorStop(0, 'rgba(245, 158, 11, 0.9)');
                        gradient.addColorStop(1, 'rgba(245, 158, 11, 0.15)');
                        return gradient;
                    },
                    borderColor: 'rgba(245, 158, 11, 0.85)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Remaining Balance',
                    data: data.available,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
                        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.9)');
                        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.15)');
                        return gradient;
                    },
                    borderColor: 'rgba(16, 185, 129, 0.85)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                },
            ],
        },
        options: baseChartOptions(),
    });
}

function initAssignmentFlowChart(canvas, data) {
    if (! canvas || ! data.values.some((value) => value > 0)) {
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const labelColor = isDark ? '#9ca3af' : '#4b5563';

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [
                {
                    data: data.values,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(100, 116, 139, 0.85)',
                    ].slice(0, data.labels.length),
                    hoverBackgroundColor: [
                        'rgba(99, 102, 241, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(100, 116, 139, 1)',
                    ].slice(0, data.labels.length),
                    borderWidth: isDark ? 3 : 2,
                    borderColor: isDark ? '#1f2937' : '#ffffff',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: labelColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            family: 'Figtree, ui-sans-serif, system-ui',
                            size: 11,
                            weight: '600',
                        },
                        padding: 16,
                    },
                },
                tooltip: {
                    backgroundColor: isDark ? '#1f2937' : '#ffffff',
                    titleColor: isDark ? '#ffffff' : '#111827',
                    bodyColor: isDark ? '#d1d5db' : '#4b5563',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                    borderRadius: 10,
                }
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('material-dashboard-charts');

    if (! root) {
        return;
    }

    const config = JSON.parse(root.dataset.charts);

    initProjectComparisonChart(
        document.getElementById('projectComparisonChart'),
        config.project_comparison,
    );

    initAssignmentFlowChart(
        document.getElementById('assignmentFlowChart'),
        config.assignment_flow,
    );
});

