/**
 * Dashboard Charts
 * Chart.js initialization for analytics dashboard
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Wait for Chart.js to load
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded');
            return;
        }

        // Wait for data
        if (typeof csiDashboardData === 'undefined') {
            console.error('Dashboard data not available');
            return;
        }

        // Membership Type Distribution - Pie Chart
        var membershipCtx = document.getElementById('membershipChart');
        if (membershipCtx) {
            var membershipData = csiDashboardData.membership_types;
            new Chart(membershipCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        'Student',
                        'Early Investigator',
                        'Postdoctoral',
                        'Scientist',
                        'Industry',
                        'Honorary'
                    ],
                    datasets: [{
                        data: [
                            membershipData.student || 0,
                            membershipData.early_investigator || 0,
                            membershipData.postdoctoral || 0,
                            membershipData.scientist || 0,
                            membershipData.industry || 0,
                            membershipData.honorary || 0
                        ],
                        backgroundColor: [
                            '#2271b1',
                            '#00a32a',
                            '#d63638',
                            '#dba617',
                            '#8c8f94',
                            '#135e96'
                        ],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 6
                        }
                    }
                }
            });
        }

        // Payment Status - Bar Chart
        var paymentCtx = document.getElementById('paymentChart');
        if (paymentCtx) {
            var paymentData = csiDashboardData.payments;
            new Chart(paymentCtx, {
                type: 'bar',
                data: {
                    labels: ['Paid', 'Pending', 'In Review', 'Declined'],
                    datasets: [{
                        label: 'Users',
                        data: [
                            paymentData.paid || 0,
                            paymentData.pending || 0,
                            paymentData.inreview || 0,
                            paymentData.declined || 0
                        ],
                        backgroundColor: [
                            '#00a32a',
                            '#dba617',
                            '#2271b1',
                            '#d63638'
                        ],
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 6
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e1e5e9',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                color: '#646970'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                color: '#646970'
                            }
                        }
                    }
                }
            });
        }

        // Registration Trends - Line Chart
        var trendsCtx = document.getElementById('trendsChart');
        if (trendsCtx) {
            var trendsData = csiDashboardData.time_based.monthly_data;
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: trendsData.map(function (item) { return item.month; }),
                    datasets: [{
                        label: 'Registrations',
                        data: trendsData.map(function (item) { return item.count; }),
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#2271b1',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 6,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e1e5e9',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                color: '#646970'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                color: '#646970'
                            }
                        }
                    }
                }
            });
        }

        // Initialize DataTable for recent activity
        if (typeof CSI !== 'undefined' && CSI.DataTables) {
            CSI.DataTables.init('#recent-activity-table', {
                pageLength: 10,
                order: [[1, 'desc']]
            });
        }
    });

})(jQuery);
