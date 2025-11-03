<?php
/**
 * ENHANCED STATISTICS PAGE - PROFESSIONAL VERSION
 * Place this file in: themes/default/settings/statistics.php
 * REPLACES the previous statistics.php
 */

// Get filter period from URL (default: 7 days)
$period = isset($_GET['period']) ? $_GET['period'] : '7days';
$customStart = isset($_GET['custom_start']) ? $_GET['custom_start'] : '';
$customEnd = isset($_GET['custom_end']) ? $_GET['custom_end'] : '';
$compareMode = isset($_GET['compare']) ? $_GET['compare'] : 'previous';

// Calculate date ranges based on selected period
$currentTime = time();

if($period == 'custom' && $customStart && $customEnd) {
    // Custom date range
    $startTime = strtotime($customStart . ' 00:00:00');
    $currentTime = strtotime($customEnd . ' 23:59:59');
    $daysDiff = ceil(($currentTime - $startTime) / 86400);
    $previousStartTime = $startTime - ($daysDiff * 86400);
    $previousEndTime = $startTime - 1;
    $periodLabel = date('M d', $startTime) . ' - ' . date('M d, Y', $currentTime);
} else {
    switch($period) {
        case '7days':
            $startTime = strtotime('-7 days', $currentTime);
            $previousStartTime = strtotime('-14 days', $currentTime);
            $previousEndTime = $startTime - 1;
            $periodLabel = 'Last 7 Days';
            break;
        case '30days':
            $startTime = strtotime('-30 days', $currentTime);
            $previousStartTime = strtotime('-60 days', $currentTime);
            $previousEndTime = $startTime - 1;
            $periodLabel = 'Last 30 Days';
            break;
        case 'thismonth':
            $startTime = strtotime('first day of this month 00:00:00');
            $previousStartTime = strtotime('first day of last month 00:00:00');
            $previousEndTime = strtotime('last day of last month 23:59:59');
            $periodLabel = 'This Month';
            break;
        case 'lastmonth':
            $startTime = strtotime('first day of last month 00:00:00');
            $currentTime = strtotime('last day of last month 23:59:59');
            $previousStartTime = strtotime('first day of -2 month 00:00:00');
            $previousEndTime = strtotime('last day of -2 month 23:59:59');
            $periodLabel = 'Last Month';
            break;
        default:
            $startTime = strtotime('-7 days', $currentTime);
            $previousStartTime = strtotime('-14 days', $currentTime);
            $previousEndTime = $startTime - 1;
            $periodLabel = 'Last 7 Days';
    }
}

// Get current period statistics
$totalViews = $iN->iN_GetTotalProfileViewsByDateRange($userID, $startTime, $currentTime);
$totalFollowers = $iN->iN_GetFollowersByDateRange($userID, $startTime, $currentTime);
$totalLikes = $iN->iN_GetTotalLikesByDateRange($userID, $startTime, $currentTime);
$totalComments = $iN->iN_GetTotalCommentsByDateRange($userID, $startTime, $currentTime);
$totalPosts = $iN->iN_GetPostsByDateRange($userID, $startTime, $currentTime);
$totalSubscribers = $iN->iN_GetSubscribersByDateRange($userID, $startTime, $currentTime);

// Get previous period statistics for comparison
$prevViews = $iN->iN_GetTotalProfileViewsByDateRange($userID, $previousStartTime, $previousEndTime);
$prevFollowers = $iN->iN_GetFollowersByDateRange($userID, $previousStartTime, $previousEndTime);
$prevLikes = $iN->iN_GetTotalLikesByDateRange($userID, $previousStartTime, $previousEndTime);
$prevComments = $iN->iN_GetTotalCommentsByDateRange($userID, $previousStartTime, $previousEndTime);
$prevPosts = $iN->iN_GetPostsByDateRange($userID, $previousStartTime, $previousEndTime);
$prevSubscribers = $iN->iN_GetSubscribersByDateRange($userID, $previousStartTime, $previousEndTime);

// Calculate growth percentages
$viewsGrowth = $iN->iN_CalculateGrowthPercentage($totalViews, $prevViews);
$followersGrowth = $iN->iN_CalculateGrowthPercentage($totalFollowers, $prevFollowers);
$likesGrowth = $iN->iN_CalculateGrowthPercentage($totalLikes, $prevLikes);
$commentsGrowth = $iN->iN_CalculateGrowthPercentage($totalComments, $prevComments);
$postsGrowth = $iN->iN_CalculateGrowthPercentage($totalPosts, $prevPosts);

// Calculate engagement rate
$engagementRate = $iN->iN_CalculateEngagementRate($totalViews, $totalLikes, $totalComments);
$prevEngagementRate = $iN->iN_CalculateEngagementRate($prevViews, $prevLikes, $prevComments);
$engagementGrowth = $iN->iN_CalculateGrowthPercentage($engagementRate, $prevEngagementRate);

// Get daily data for charts
$dailyViews = $iN->iN_GetDailyProfileViews($userID, $startTime, $currentTime);
$dailyFollowers = $iN->iN_GetDailyFollowers($userID, $startTime, $currentTime);
$dailyLikes = $iN->iN_GetDailyLikes($userID, $startTime, $currentTime);
$dailyComments = $iN->iN_GetDailyComments($userID, $startTime, $currentTime);

// Get previous period daily data for comparison
$prevDailyViews = $iN->iN_GetDailyProfileViews($userID, $previousStartTime, $previousEndTime);
$prevDailyFollowers = $iN->iN_GetDailyFollowers($userID, $previousStartTime, $previousEndTime);
$prevDailyLikes = $iN->iN_GetDailyLikes($userID, $previousStartTime, $previousEndTime);
$prevDailyComments = $iN->iN_GetDailyComments($userID, $previousStartTime, $previousEndTime);

// Get total followers (all time) for display
$allTimeFollowers = $iN->iN_UserTotalFollowerUsers($userID);
$allTimeSubscribers = $iN->iN_UserTotalSubscribers($userID);
$allTimePosts = $iN->iN_TotalPosts($userID);

// Calculate averages
$dayCount = max(1, ceil(($currentTime - $startTime) / 86400));
$avgViewsPerDay = round($totalViews / $dayCount, 1);
$avgLikesPerDay = round($totalLikes / $dayCount, 1);
$avgFollowersPerDay = round($totalFollowers / $dayCount, 1);
?>

<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <!-- Header -->
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_">
         <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('154'));?>
         Statistics
       </div>
       <div class="i_moda_header_nt">
         Track your profile performance and growth trends
       </div>
    </div>

    <div class="i_settings_wrapper_items">
        <!-- Period Filter + Custom Date Picker -->
        <div class="stats_filter_container">
            <div class="stats_period_filter tabing flex_">
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=7days" 
                   class="stats_filter_btn transition <?php echo $period == '7days' ? 'active' : ''; ?>">
                    Last 7 Days
                </a>
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=30days" 
                   class="stats_filter_btn transition <?php echo $period == '30days' ? 'active' : ''; ?>">
                    Last 30 Days
                </a>
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=thismonth" 
                   class="stats_filter_btn transition <?php echo $period == 'thismonth' ? 'active' : ''; ?>">
                    This Month
                </a>
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=lastmonth" 
                   class="stats_filter_btn transition <?php echo $period == 'lastmonth' ? 'active' : ''; ?>">
                    Last Month
                </a>
                <button class="stats_filter_btn transition <?php echo $period == 'custom' ? 'active' : ''; ?>" 
                        onclick="toggleCustomDatePicker()">
                    Custom Range
                </button>
            </div>
            
            <!-- Custom Date Picker -->
            <div class="custom_date_picker" id="customDatePicker" style="display: <?php echo $period == 'custom' ? 'flex' : 'none'; ?>;">
                <form method="GET" action="<?php echo iN_HelpSecure($base_url);?>settings" class="flex_ tabing">
                    <input type="hidden" name="tab" value="statistics">
                    <input type="hidden" name="period" value="custom">
                    <div class="date_input_group">
                        <label>From:</label>
                        <input type="date" name="custom_start" value="<?php echo $customStart; ?>" required>
                    </div>
                    <div class="date_input_group">
                        <label>To:</label>
                        <input type="date" name="custom_end" value="<?php echo $customEnd; ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="stats_filter_btn active">Apply</button>
                </form>
            </div>

            <!-- Export Buttons -->
            <div class="stats_export_buttons flex_ tabing">
                <button onclick="exportToCSV()" class="export_btn">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('157'));?> Export CSV
                </button>
                <button onclick="window.print()" class="export_btn">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('137'));?> Print Report
                </button>
            </div>
        </div>

        <div class="payouts_form_container">
            <!-- Overview Stats Cards with Comparison -->
            <div class="i_sub_not">Overview - <?php echo $periodLabel; ?></div>
            
            <div class="chart_row tabing flex_">
                <!-- Profile Views Card -->
                <div class="chart_row_box">
                   <div class="chart_row_box_item c1">
                        <div class="chart_row_box_title tabing_non_justify flex_">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('47'));?> Profile Views
                        </div>
                        <div class="chart_row_box_sum">
                            <?php echo number_format($totalViews); ?>
                        </div>
                        <div class="stats_growth_indicator <?php echo $viewsGrowth['direction']; ?>">
                            <?php 
                            if($viewsGrowth['direction'] == 'up') {
                                echo '↑ ' . $viewsGrowth['percentage'] . '%';
                            } elseif($viewsGrowth['direction'] == 'down') {
                                echo '↓ ' . $viewsGrowth['percentage'] . '%';
                            } else {
                                echo '→ 0%';
                            }
                            ?>
                        </div>
                        <div class="stats_comparison_text">
                            vs <?php echo number_format($prevViews); ?> previous period
                        </div>
                        <div class="stats_subtext">
                            Avg: <?php echo number_format($avgViewsPerDay, 1); ?> views/day
                        </div>
                   </div>
                </div>

                <!-- New Followers Card -->
                <div class="chart_row_box">
                   <div class="chart_row_box_item c2">
                        <div class="chart_row_box_title tabing_non_justify flex_">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('140'));?> New Followers
                        </div>
                        <div class="chart_row_box_sum">
                            <?php echo number_format($totalFollowers); ?>
                        </div>
                        <div class="stats_growth_indicator <?php echo $followersGrowth['direction']; ?>">
                            <?php 
                            if($followersGrowth['direction'] == 'up') {
                                echo '↑ ' . $followersGrowth['percentage'] . '%';
                            } elseif($followersGrowth['direction'] == 'down') {
                                echo '↓ ' . $followersGrowth['percentage'] . '%';
                            } else {
                                echo '→ 0%';
                            }
                            ?>
                        </div>
                        <div class="stats_comparison_text">
                            vs <?php echo number_format($prevFollowers); ?> previous period
                        </div>
                        <div class="stats_subtext">
                            Total: <?php echo number_format($allTimeFollowers); ?> followers
                        </div>
                   </div>
                </div>

                <!-- Total Likes Card -->
                <div class="chart_row_box">
                   <div class="chart_row_box_item c3">
                        <div class="chart_row_box_title tabing_non_justify flex_">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('24'));?> Total Likes
                        </div>
                        <div class="chart_row_box_sum">
                            <?php echo number_format($totalLikes); ?>
                        </div>
                        <div class="stats_growth_indicator <?php echo $likesGrowth['direction']; ?>">
                            <?php 
                            if($likesGrowth['direction'] == 'up') {
                                echo '↑ ' . $likesGrowth['percentage'] . '%';
                            } elseif($likesGrowth['direction'] == 'down') {
                                echo '↓ ' . $likesGrowth['percentage'] . '%';
                            } else {
                                echo '→ 0%';
                            }
                            ?>
                        </div>
                        <div class="stats_comparison_text">
                            vs <?php echo number_format($prevLikes); ?> previous period
                        </div>
                        <div class="stats_subtext">
                            Avg: <?php echo number_format($avgLikesPerDay, 1); ?> likes/day
                        </div>
                   </div>
                </div>

                <!-- Engagement Rate Card -->
                <div class="chart_row_box">
                   <div class="chart_row_box_item c4">
                        <div class="chart_row_box_title tabing_non_justify flex_">
                            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('114'));?> Engagement Rate
                        </div>
                        <div class="chart_row_box_sum">
                            <?php echo $engagementRate; ?>%
                        </div>
                        <div class="stats_growth_indicator <?php echo $engagementGrowth['direction']; ?>">
                            <?php 
                            if($engagementGrowth['direction'] == 'up') {
                                echo '↑ ' . $engagementGrowth['percentage'] . '%';
                            } elseif($engagementGrowth['direction'] == 'down') {
                                echo '↓ ' . $engagementGrowth['percentage'] . '%';
                            } else {
                                echo '→ 0%';
                            }
                            ?>
                        </div>
                        <div class="stats_comparison_text">
                            vs <?php echo $prevEngagementRate; ?>% previous
                        </div>
                        <div class="stats_subtext">
                            (Likes + Comments) / Views
                        </div>
                   </div>
                </div>
            </div>

            <!-- Comparison Chart Section -->
            <div class="i_sub_not" style="margin-top: 30px;">Performance Comparison</div>
            
            <div class="comparison_chart_wrapper">
                <canvas id="comparisonChart"></canvas>
            </div>

            <!-- Main Trend Chart -->
            <div class="i_sub_not" style="margin-top: 30px;">Daily Trends (<?php echo $periodLabel; ?>)</div>
            
            <div class="chart_wrapper">
                <canvas id="statisticsChart"></canvas>
            </div>

            <!-- Detailed Data Table -->
            <div class="i_sub_not" style="margin-top: 30px;">Detailed Daily Breakdown</div>
            
            <div class="stats_data_table_wrapper">
                <table class="stats_data_table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Views</th>
                            <th>Followers</th>
                            <th>Likes</th>
                            <th>Comments</th>
                            <th>Total Engagement</th>
                        </tr>
                    </thead>
                    <tbody id="statsTableBody">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Summary Stats Row -->
            <div class="chart_row tabing flex_" style="margin-top: 30px;">
                <div class="chart_row_box flex_ tabing column">
                    <div class="revenue_sum_u"><?php echo number_format($totalComments); ?></div>
                    <div class="revenue_title_u">Total Comments</div>
                    <div class="stats_mini_growth <?php echo $commentsGrowth['direction']; ?>">
                        <?php echo $commentsGrowth['direction'] == 'up' ? '↑' : ($commentsGrowth['direction'] == 'down' ? '↓' : '→'); ?>
                        <?php echo $commentsGrowth['percentage']; ?>%
                    </div>
                </div>
                
                <div class="chart_row_box flex_ tabing column">
                    <div class="revenue_sum_u"><?php echo number_format($totalPosts); ?></div>
                    <div class="revenue_title_u">Posts Created</div>
                    <div class="stats_mini_growth <?php echo $postsGrowth['direction']; ?>">
                        <?php echo $postsGrowth['direction'] == 'up' ? '↑' : ($postsGrowth['direction'] == 'down' ? '↓' : '→'); ?>
                        <?php echo $postsGrowth['percentage']; ?>%
                    </div>
                </div>
                
                <div class="chart_row_box flex_ tabing column">
                    <div class="revenue_sum_u"><?php echo number_format($allTimePosts); ?></div>
                    <div class="revenue_title_u">Total Posts (All Time)</div>
                </div>
                
                <div class="chart_row_box flex_ tabing column">
                    <div class="revenue_sum_u"><?php echo number_format($avgFollowersPerDay, 1); ?></div>
                    <div class="revenue_title_u">Avg Followers/Day</div>
                </div>
            </div>

            <!-- Key Insights Section -->
            <div class="i_sub_not" style="margin-top: 30px;">Key Insights</div>
            
            <div class="stats_insights_grid">
                <div class="insight_card">
                    <div class="insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('153'));?></div>
                    <div class="insight_content">
                        <h4>Best Metric</h4>
                        <p>
                            <?php
                            $metrics = [
                                'Views' => $viewsGrowth['percentage'],
                                'Followers' => $followersGrowth['percentage'],
                                'Likes' => $likesGrowth['percentage'],
                                'Comments' => $commentsGrowth['percentage']
                            ];
                            arsort($metrics);
                            $bestMetric = array_key_first($metrics);
                            echo "<strong>$bestMetric</strong> performing best with <strong>" . number_format($metrics[$bestMetric], 1) . "%</strong> growth";
                            ?>
                        </p>
                    </div>
                </div>

                <div class="insight_card">
                    <div class="insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('114'));?></div>
                    <div class="insight_content">
                        <h4>Engagement Quality</h4>
                        <p>
                            <?php
                            if($engagementRate > 5) {
                                echo "<strong class='good'>Excellent!</strong> Your " . $engagementRate . "% engagement rate is above average.";
                            } elseif($engagementRate > 2) {
                                echo "<strong class='ok'>Good!</strong> Your " . $engagementRate . "% engagement rate is solid.";
                            } else {
                                echo "<strong class='improve'>Room to grow.</strong> Focus on content that drives more interaction.";
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="insight_card">
                    <div class="insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('147'));?></div>
                    <div class="insight_content">
                        <h4>Growth Trend</h4>
                        <p>
                            <?php
                            $totalGrowth = $viewsGrowth['percentage'] + $followersGrowth['percentage'] + $likesGrowth['percentage'];
                            $avgGrowth = round($totalGrowth / 3, 1);
                            if($avgGrowth > 20) {
                                echo "<strong class='good'>Strong growth!</strong> Your profile is expanding rapidly at " . $avgGrowth . "% average growth.";
                            } elseif($avgGrowth > 0) {
                                echo "<strong class='ok'>Steady progress!</strong> You're growing at " . $avgGrowth . "% average rate.";
                            } else {
                                echo "<strong class='improve'>Focus needed.</strong> Try posting more engaging content.";
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
         </div>
    </div>
  </div>
</div>

<!-- Inject chart data as JSON -->
<script id="statisticsChartData" type="application/json">
<?php
// Prepare data for main trend chart
$chartLabels = [];
$chartViews = [];
$chartFollowers = [];
$chartLikes = [];
$chartComments = [];

// Create array of all dates in range
$dateRange = [];
$currentDate = $startTime;
while($currentDate <= $currentTime) {
    $dateKey = date('Y-m-d', $currentDate);
    $dateRange[$dateKey] = [
        'views' => 0,
        'followers' => 0,
        'likes' => 0,
        'comments' => 0,
        'display' => date('M d', $currentDate)
    ];
    $currentDate = strtotime('+1 day', $currentDate);
}

// Fill in actual data
foreach($dailyViews as $data) {
    if(isset($dateRange[$data['view_date']])) {
        $dateRange[$data['view_date']]['views'] = (int)$data['daily_views'];
    }
}
foreach($dailyFollowers as $data) {
    if(isset($dateRange[$data['follow_date']])) {
        $dateRange[$data['follow_date']]['followers'] = (int)$data['daily_followers'];
    }
}
foreach($dailyLikes as $data) {
    if(isset($dateRange[$data['like_date']])) {
        $dateRange[$data['like_date']]['likes'] = (int)$data['daily_likes'];
    }
}
foreach($dailyComments as $data) {
    if(isset($dateRange[$data['comment_date']])) {
        $dateRange[$data['comment_date']]['comments'] = (int)$data['daily_comments'];
    }
}

// Convert to arrays for chart and table
$tableData = [];
foreach($dateRange as $date => $data) {
    $chartLabels[] = $data['display'];
    $chartViews[] = $data['views'];
    $chartFollowers[] = $data['followers'];
    $chartLikes[] = $data['likes'];
    $chartComments[] = $data['comments'];
    
    $tableData[] = [
        'date' => date('M d, Y', strtotime($date)),
        'views' => $data['views'],
        'followers' => $data['followers'],
        'likes' => $data['likes'],
        'comments' => $data['comments']
    ];
}

echo json_encode([
    'labels' => $chartLabels,
    'views' => $chartViews,
    'followers' => $chartFollowers,
    'likes' => $chartLikes,
    'comments' => $chartComments,
    'tableData' => $tableData,
    'comparison' => [
        'current' => [
            'views' => $totalViews,
            'followers' => $totalFollowers,
            'likes' => $totalLikes,
            'comments' => $totalComments
        ],
        'previous' => [
            'views' => $prevViews,
            'followers' => $prevFollowers,
            'likes' => $prevLikes,
            'comments' => $prevComments
        ]
    ]
]);
?>
</script>

<!-- Load Chart.js and enhanced statistics script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?php echo $base_url; ?>themes/<?php echo $currentTheme; ?>/js/statisticsChartEnhanced.js?v=<?php echo time(); ?>" defer></script>

<style>
@media print {
    .stats_period_filter, .stats_export_buttons, .settings_left_menu, .i_top_nav { display: none !important; }
    .chart_wrapper, .comparison_chart_wrapper { page-break-inside: avoid; }
}
</style>