<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

$stats = csi_get_dashboard_stats();
?>

<div class="wrap csi-admin-wrapper">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Main Statistics Overview -->
    <div class="csi-dashboard-section">
        <div class="csi-dashboard-grid">
            <div class="csi-stat-card">
                <h3><?php _e('Total Users', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['users']['total']); ?></p>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('Active Users', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['users']['active']); ?></p>
                <p class="stat-change"><?php echo $stats['users']['active_percentage']; ?>% <?php _e('of total', 'custom-signup-plugin'); ?></p>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('Paid Users', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['payments']['paid']); ?></p>
                <p class="stat-change"><?php echo $stats['payments']['success_rate']; ?>% <?php _e('success rate', 'custom-signup-plugin'); ?></p>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('Pending Payments', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['payments']['pending']); ?></p>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('In Review', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['payments']['inreview']); ?></p>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('Declined Payments', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['payments']['declined']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="csi-dashboard-section">
        <h2 class="csi-dashboard-section-title"><?php _e('Analytics Overview', 'custom-signup-plugin'); ?></h2>
        <div class="csi-charts-grid">
            <div class="csi-chart-card">
                <div class="card-header">
                    <h3><?php _e('Membership Type Distribution', 'custom-signup-plugin'); ?></h3>
                </div>
                <div class="card-body">
                    <canvas id="membershipChart"></canvas>
                </div>
            </div>
            <div class="csi-chart-card">
                <div class="card-header">
                    <h3><?php _e('Payment Status', 'custom-signup-plugin'); ?></h3>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registration Trends - Full Width -->
    <div class="csi-dashboard-section">
        <div class="csi-chart-full">
            <div class="csi-chart-card">
                <div class="card-header">
                    <h3><?php _e('Registration Trends (Last 12 Months)', 'custom-signup-plugin'); ?></h3>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Time-based Statistics -->
    <div class="csi-dashboard-section">
        <h2 class="csi-dashboard-section-title"><?php _e('Time-based Statistics', 'custom-signup-plugin'); ?></h2>
        <div class="csi-time-stats-grid">
            <div class="csi-stat-card">
                <h3><?php _e('This Month', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['time_based']['this_month']); ?></p>
                <?php if ($stats['time_based']['growth_rate'] > 0): ?>
                    <p class="stat-change positive">+<?php echo $stats['time_based']['growth_rate']; ?>% <?php _e('growth', 'custom-signup-plugin'); ?></p>
                <?php elseif ($stats['time_based']['growth_rate'] < 0): ?>
                    <p class="stat-change negative"><?php echo $stats['time_based']['growth_rate']; ?>% <?php _e('change', 'custom-signup-plugin'); ?></p>
                <?php endif; ?>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('Last Month', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['time_based']['last_month']); ?></p>
            </div>
            <div class="csi-stat-card">
                <h3><?php _e('This Year', 'custom-signup-plugin'); ?></h3>
                <p class="stat-value"><?php echo number_format($stats['time_based']['this_year']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="csi-dashboard-section">
        <h2 class="csi-dashboard-section-title"><?php _e('Recent Registrations', 'custom-signup-plugin'); ?></h2>
        <div class="csi-chart-card">
            <div class="card-body">
                <table class="table" id="recent-activity-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'custom-signup-plugin'); ?></th>
                            <th><?php _e('Registration Date', 'custom-signup-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_activity']['registrations'] as $registration): ?>
                            <tr>
                                <td><?php echo esc_html($registration['fullname'] ?: 'User #' . $registration['ID']); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registration['user_registered']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Chart data will be initialized in charts.js
var csiDashboardData = <?php echo json_encode($stats); ?>;
</script>
