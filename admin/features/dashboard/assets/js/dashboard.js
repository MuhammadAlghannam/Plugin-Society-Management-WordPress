/**
 * Dashboard JavaScript
 * Main dashboard functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Auto-refresh stats every 5 minutes
        var refreshInterval = 300000; // 5 minutes
        
        setInterval(function() {
            refreshDashboardStats();
        }, refreshInterval);
        
        // Manual refresh button (if exists)
        $(document).on('click', '#refresh-dashboard', function() {
            refreshDashboardStats();
        });
    });
    
    /**
     * Refresh dashboard statistics
     */
    function refreshDashboardStats() {
        $.ajax({
            url: csiDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'csi_refresh_dashboard_stats',
                nonce: csiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update stats and redraw charts
                    if (typeof csiDashboardData !== 'undefined') {
                        csiDashboardData = response.data.stats;
                        location.reload(); // Simple refresh for now
                    }
                }
            }
        });
    }
    
})(jQuery);
