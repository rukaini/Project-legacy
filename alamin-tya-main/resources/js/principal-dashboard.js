// resources/js/principal-dashboard.js
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Get data from data attributes
        var maleCount = parseInt(document.getElementById('genderChart')?.dataset?.male || 0);
        var femaleCount = parseInt(document.getElementById('genderChart')?.dataset?.female || 0);
        var activeCount = parseInt(document.getElementById('statusChart')?.dataset?.active || 0);
        var resignedCount = parseInt(document.getElementById('statusChart')?.dataset?.resigned || 0);
        var activePercent = parseInt(document.getElementById('statusChart')?.dataset?.activePercent || 0);
        var resignedPercent = parseInt(document.getElementById('statusChart')?.dataset?.resignedPercent || 0);
        
        // Monthly data
        var monthlyLabels = JSON.parse(document.getElementById('trendChart')?.dataset?.labels || '[]');
        var monthlyData = JSON.parse(document.getElementById('trendChart')?.dataset?.data || '[]');
        
        // Gender Distribution Chart
        var genderCtx = document.getElementById('genderChart');
        if (genderCtx && (maleCount > 0 || femaleCount > 0)) {
            new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{
                        data: [maleCount, femaleCount],
                        backgroundColor: ['#3b82f6', '#ec4899'],
                        borderWidth: 0,
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Teacher Status Chart
        var statusCtx = document.getElementById('statusChart');
        if (statusCtx && (activeCount > 0 || resignedCount > 0)) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Resigned'],
                    datasets: [{
                        data: [activeCount, resignedCount],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 0,
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.raw || 0;
                                    var percentage = label === 'Active' ? activePercent : resignedPercent;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Monthly Resignation Trends Chart
        var trendCtx = document.getElementById('trendChart');
        if (trendCtx && monthlyLabels.length > 0) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Resignations',
                        data: monthlyData,
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Resignations: ' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Resignations',
                                color: '#6b7280'
                            },
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month',
                                color: '#6b7280'
                            }
                        }
                    }
                }
            });
        }
    });
})();