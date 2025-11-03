<?php
/**
 * STATISTICS PAGE - WORKS WITH YOUR EXISTING FUNCTIONS
 * themes/default/settings/statistics.php
 */

// --------- Resolve language (fallbacks, if your theme already sets $userLang, it will stay) ----------
if (!isset($userLang) || !$userLang) {
  $userLang = (isset($_SESSION['lang']) && $_SESSION['lang']) ? $_SESSION['lang'] : ($defaultLanguage ?? 'en');
}

// --------- Small i18n helpers for month/day ribbons ----------
function i18n_month_abbr_map($lang) {
  if ($lang === 'fr') {
    return [
      'Jan'=>'janv.', 'Feb'=>'févr.', 'Mar'=>'mars', 'Apr'=>'avr.',
      'May'=>'mai', 'Jun'=>'juin', 'Jul'=>'juil.', 'Aug'=>'août',
      'Sep'=>'sept.', 'Oct'=>'oct.', 'Nov'=>'nov.', 'Dec'=>'déc.'
    ];
  }
  return []; // English or other langs fall back to PHP date()
}
function i18n_date_Md($ts, $lang) {
  $s = date('M d', $ts);
  $map = i18n_month_abbr_map($lang);
  return strtr($s, $map);
}
function i18n_date_MdY($ts, $lang) {
  $s = date('M d, Y', $ts);
  $map = i18n_month_abbr_map($lang);
  return strtr($s, $map);
}

$period = isset($_GET['period']) ? $_GET['period'] : '7days';
$customStart = isset($_GET['custom_start']) ? $_GET['custom_start'] : '';
$customEnd = isset($_GET['custom_end']) ? $_GET['custom_end'] : '';
$compareWith = isset($_GET['compare_with']) ? $_GET['compare_with'] : 'previous';

$currentTime = time();

if($period == 'custom' && $customStart && $customEnd) {
    $startTime = strtotime($customStart . ' 00:00:00');
    $currentTime = strtotime($customEnd . ' 23:59:59');
    $daysDiff = ceil(($currentTime - $startTime) / 86400);
    
    switch($compareWith) {
        case 'previous':
            $previousStartTime = $startTime - ($daysDiff * 86400);
            $previousEndTime = $startTime - 1;
            $comparisonLabel = $LANG['previous_period'] ?? 'Previous period';
            break;
        case 'lastmonth':
            $previousStartTime = strtotime('first day of last month 00:00:00');
            $previousEndTime = strtotime('last day of last month 23:59:59');
            $comparisonLabel = $LANG['last_month'] ?? 'Last month';
            break;
        case 'lastyear':
            $previousStartTime = strtotime($customStart . ' -1 year');
            $previousEndTime = strtotime($customEnd . ' -1 year');
            $comparisonLabel = $LANG['same_period_last_year'] ?? 'Same period last year';
            break;
        default:
            $previousStartTime = $startTime - ($daysDiff * 86400);
            $previousEndTime = $startTime - 1;
            $comparisonLabel = $LANG['previous_period'] ?? 'Previous period';
    }
    // Localized date ribbon
    $periodLabel = i18n_date_Md($startTime, $userLang) . ' - ' . i18n_date_MdY($currentTime, $userLang);
} else {
    switch($period) {
        case '7days':
            $startTime = strtotime('-7 days', $currentTime);
            $previousStartTime = strtotime('-14 days', $currentTime);
            $previousEndTime = $startTime - 1;
            $periodLabel = $LANG['last_7_days'] ?? 'Last 7 Days';
            $comparisonLabel = $LANG['previous_period'] ?? 'Previous period';
            break;
        case '30days':
            $startTime = strtotime('-30 days', $currentTime);
            $previousStartTime = strtotime('-60 days', $currentTime);
            $previousEndTime = $startTime - 1;
            $periodLabel = $LANG['last_30_days'] ?? 'Last 30 Days';
            $comparisonLabel = $LANG['previous_period'] ?? 'Previous period';
            break;
        case 'thismonth':
            $startTime = strtotime('first day of this month 00:00:00');
            $previousStartTime = strtotime('first day of last month 00:00:00');
            $previousEndTime = strtotime('last day of last month 23:59:59');
            $periodLabel = $LANG['this_month'] ?? 'This Month';
            $comparisonLabel = $LANG['last_month'] ?? 'Last month';
            break;
        case 'thisyear':
            $startTime = strtotime('first day of january this year 00:00:00');
            $previousStartTime = strtotime('first day of january last year 00:00:00');
            $previousEndTime = strtotime('last day of december last year 23:59:59');
            $periodLabel = $LANG['this_year'] ?? 'This Year';
            $comparisonLabel = $LANG['last_year'] ?? 'Last year';
            break;
        default:
            $startTime = strtotime('-7 days', $currentTime);
            $previousStartTime = strtotime('-14 days', $currentTime);
            $previousEndTime = $startTime - 1;
            $periodLabel = $LANG['last_7_days'] ?? 'Last 7 Days';
            $comparisonLabel = $LANG['previous_period'] ?? 'Previous period';
    }
}

// Get current period stats
$totalViews = $iN->iN_GetTotalProfileViewsByDateRange($userID, $startTime, $currentTime);
$totalFollowers = $iN->iN_GetFollowersByDateRange($userID, $startTime, $currentTime);
$totalLikes = $iN->iN_GetTotalLikesByDateRange($userID, $startTime, $currentTime);
$totalComments = $iN->iN_GetTotalCommentsByDateRange($userID, $startTime, $currentTime);
$totalPosts = $iN->iN_GetPostsByDateRange($userID, $startTime, $currentTime);
$totalSubscribers = $iN->iN_GetSubscribersByDateRange($userID, $startTime, $currentTime);

// Get previous period stats
$prevViews = $iN->iN_GetTotalProfileViewsByDateRange($userID, $previousStartTime, $previousEndTime);
$prevFollowers = $iN->iN_GetFollowersByDateRange($userID, $previousStartTime, $previousEndTime);
$prevLikes = $iN->iN_GetTotalLikesByDateRange($userID, $previousStartTime, $previousEndTime);
$prevComments = $iN->iN_GetTotalCommentsByDateRange($userID, $previousStartTime, $previousEndTime);
$prevPosts = $iN->iN_GetPostsByDateRange($userID, $previousStartTime, $previousEndTime);

// Calculate growth
$viewsGrowth = $iN->iN_CalculateGrowthPercentage($totalViews, $prevViews);
$followersGrowth = $iN->iN_CalculateGrowthPercentage($totalFollowers, $prevFollowers);
$likesGrowth = $iN->iN_CalculateGrowthPercentage($totalLikes, $prevLikes);
$commentsGrowth = $iN->iN_CalculateGrowthPercentage($totalComments, $prevComments);

// Engagement
$engagementRate = $iN->iN_CalculateEngagementRate($totalViews, $totalLikes, $totalComments);
$prevEngagementRate = $iN->iN_CalculateEngagementRate($prevViews, $prevLikes, $prevComments);
$engagementGrowth = $iN->iN_CalculateGrowthPercentage($engagementRate, $prevEngagementRate);

// Daily data
$dailyViews = $iN->iN_GetDailyProfileViews($userID, $startTime, $currentTime);
$dailyFollowers = $iN->iN_GetDailyFollowers($userID, $startTime, $currentTime);
$dailyLikes = $iN->iN_GetDailyLikes($userID, $startTime, $currentTime);
$dailyComments = $iN->iN_GetDailyComments($userID, $startTime, $currentTime);

// Totals
$allTimeFollowers = $iN->iN_UserTotalFollowerUsers($userID);
$allTimeSubscribers = $iN->iN_UserTotalSubscribers($userID);
$allTimePosts = $iN->iN_TotalPosts($userID);

// Averages
$dayCount = max(1, ceil(($currentTime - $startTime) / 86400));
$avgViewsPerDay = round($totalViews / $dayCount, 1);
$avgLikesPerDay = round($totalLikes / $dayCount, 1);
$avgFollowersPerDay = round($totalFollowers / $dayCount, 1);
$avgEngagement = $totalLikes + $totalComments;

// Insights
$metrics = [
    'Profile Views' => ['current' => $totalViews, 'growth' => $viewsGrowth['percentage']],
    'New Followers' => ['current' => $totalFollowers, 'growth' => $followersGrowth['percentage']],
    'Post Likes' => ['current' => $totalLikes, 'growth' => $likesGrowth['percentage']],
    'Comments' => ['current' => $totalComments, 'growth' => $commentsGrowth['percentage']]
];
$bestMetric = '';
$bestGrowth = 0;
foreach($metrics as $name => $data) {
    if($data['growth'] > $bestGrowth) {
        $bestGrowth = $data['growth'];
        $bestMetric = $name;
    }
}

// Localized dynamic insight texts
if($engagementRate > 10) {
    $engagementInsight = sprintf($LANG['outstanding_engagement'] ?? 'Outstanding! %s%% engagement rate is exceptional.', $engagementRate);
    $engagementClass = 'excellent';
} elseif($engagementRate > 5) {
    $engagementInsight = sprintf($LANG['great_engagement'] ?? 'Great! %s%% engagement rate is above average.', $engagementRate);
    $engagementClass = 'good';
} elseif($engagementRate > 2) {
    $engagementInsight = sprintf($LANG['solid_engagement'] ?? 'Solid %s%% engagement. Keep it up!', $engagementRate);
    $engagementClass = 'ok';
} else {
    $engagementInsight = $LANG['focus_engagement'] ?? 'Focus on interactive content to boost engagement.';
    $engagementClass = 'improve';
}

$totalGrowthMetrics = ($viewsGrowth['direction'] == 'up' ? 1 : 0) + 
                      ($followersGrowth['direction'] == 'up' ? 1 : 0) + 
                      ($likesGrowth['direction'] == 'up' ? 1 : 0) + 
                      ($commentsGrowth['direction'] == 'up' ? 1 : 0);

if($totalGrowthMetrics >= 3) {
    $growthInsight = sprintf($LANG['excellent_growth'] ?? 'Excellent! %s/4 metrics growing.', $totalGrowthMetrics);
    $growthClass = 'excellent';
} elseif($totalGrowthMetrics >= 2) {
    $growthInsight = sprintf($LANG['steady_progress'] ?? 'Steady progress. %s/4 metrics up.', $totalGrowthMetrics);
    $growthClass = 'good';
} elseif($totalGrowthMetrics >= 1) {
    $growthInsight = sprintf($LANG['mixed_results'] ?? 'Mixed results. Only %s/4 growing.', $totalGrowthMetrics);
    $growthClass = 'ok';
} else {
    $growthInsight = $LANG['all_declining'] ?? 'All metrics declining. Review your strategy.';
    $growthClass = 'improve';
}

$postsPerDay = $totalPosts / max(1, $dayCount);
if($postsPerDay < 0.5) {
    $contentInsight = sprintf($LANG['post_more'] ?? 'Post more! You are at %s posts/day. Aim for 1–2 daily.', round($postsPerDay, 1));
} elseif($postsPerDay > 3) {
    $contentInsight = sprintf($LANG['great_frequency'] ?? 'Great frequency (%s posts/day).', round($postsPerDay, 1));
} else {
    $contentInsight = sprintf($LANG['good_posting'] ?? 'Good posting (%s posts/day).', round($postsPerDay, 1));
}

$audienceInsight = $LANG['keep_engaging'] ?? 'Keep engaging your audience at peak times for better reach.';
?>

<style>
/* --- Page-local styles, including iOS anti-zoom guard --- */
.settings_main_wrapper { -webkit-text-size-adjust: 100%; }
.stat_custom_picker input[type="date"],
.stat_custom_picker select {
  font-size:16px; /* iOS: prevent auto-zoom on focus */
}
.stat_breakdown_toggle {
  touch-action: manipulation; /* prevent double-tap zoom */
}
</style>

<div class="settings_main_wrapper">
  <div class="i_settings_wrapper_in i_inline_table">
     <div class="i_settings_wrapper_title">
       <div class="i_settings_wrapper_title_txt flex_">
         <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('154'));?>
         <?php echo $LANG['statistics_analytics'] ?? 'Statistics & Analytics'; ?>
       </div>
       <div class="i_moda_header_nt"><?php echo $LANG['professional_insights'] ?? 'Professional insights into your profile'; ?></div>
    </div>

    <div class="i_settings_wrapper_items">
        <div class="stat_controls">
            <div class="stat_period_filter flex_ tabing">
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=7days" 
                   class="stat_filter_btn <?php echo $period == '7days' ? 'active' : ''; ?>">
                    <?php echo $LANG['last_7_days'] ?? 'Last 7 Days'; ?>
                </a>
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=30days" 
                   class="stat_filter_btn <?php echo $period == '30days' ? 'active' : ''; ?>">
                    <?php echo $LANG['last_30_days'] ?? 'Last 30 Days'; ?>
                </a>
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=thismonth" 
                   class="stat_filter_btn <?php echo $period == 'thismonth' ? 'active' : ''; ?>">
                    <?php echo $LANG['this_month'] ?? 'This Month'; ?>
                </a>
                <a href="<?php echo iN_HelpSecure($base_url);?>settings?tab=statistics&period=thisyear" 
                   class="stat_filter_btn <?php echo $period == 'thisyear' ? 'active' : ''; ?>">
                    <?php echo $LANG['this_year'] ?? 'This Year'; ?>
                </a>
                <button type="button" onclick="toggleCustomPicker()" class="stat_filter_btn <?php echo $period == 'custom' ? 'active' : ''; ?>">
                    <span><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('115'));?></span> <?php echo $LANG['custom'] ?? 'Custom'; ?>
                </button>
            </div>
            
            <div id="customDatePicker" class="stat_custom_picker" style="display: <?php echo $period == 'custom' ? 'flex' : 'none'; ?>;">
                <form method="GET" action="<?php echo iN_HelpSecure($base_url);?>settings" class="stat_date_form" onsubmit="return true;">
                    <input type="hidden" name="tab" value="statistics">
                    <input type="hidden" name="period" value="custom">
                    
                    <div class="stat_date_group">
                        <label><?php echo $LANG['from'] ?? 'From'; ?></label>
                        <input type="date" name="custom_start" value="<?php echo iN_HelpSecure($customStart); ?>" required>
                    </div>
                    
                    <div class="stat_date_group">
                        <label><?php echo $LANG['to'] ?? 'To'; ?></label>
                        <input type="date" name="custom_end" value="<?php echo iN_HelpSecure($customEnd); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="stat_date_group">
                        <label><?php echo $LANG['compare'] ?? 'Compare'; ?></label>
                        <select name="compare_with">
                            <option value="previous" <?php echo $compareWith == 'previous' ? 'selected' : ''; ?>><?php echo $LANG['previous_period'] ?? 'Previous Period'; ?></option>
                            <option value="lastmonth" <?php echo $compareWith == 'lastmonth' ? 'selected' : ''; ?>><?php echo $LANG['last_month'] ?? 'Last Month'; ?></option>
                            <option value="lastyear" <?php echo $compareWith == 'lastyear' ? 'selected' : ''; ?>><?php echo $LANG['same_period_last_year'] ?? 'Same Period Last Year'; ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="stat_apply_btn"><?php echo $LANG['apply'] ?? 'Apply'; ?></button>
                </form>
            </div>

            <div class="stat_actions flex_ tabing">
                <button type="button" onclick="exportStats()" class="stat_action_btn">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('157'));?> <?php echo $LANG['export'] ?? 'Export'; ?>
                </button>
                <button type="button" onclick="window.print()" class="stat_action_btn">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('137'));?> <?php echo $LANG['print'] ?? 'Print'; ?>
                </button>
            </div>
        </div>

        <div class="payouts_form_container">
            <div class="stat_period_header">
                <h3><?php echo iN_HelpSecure($periodLabel); ?></h3>
                <span class="stat_comparison_label"><?php echo $LANG['vs'] ?? 'vs'; ?> <?php echo iN_HelpSecure($comparisonLabel); ?></span>
            </div>

            <div class="stat_stats_grid">
                <div class="stat_stat_card views">
                    <div class="stat_stat_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('47'));?>
                    </div>
                    <div class="stat_stat_content">
                        <div class="stat_stat_label"><?php echo $LANG['post_views'] ?? 'Post Views'; ?></div>
                        <div class="stat_stat_value"><?php echo number_format($totalViews); ?></div>
                        <div class="stat_stat_meta">
                            <span class="stat_growth <?php echo $viewsGrowth['direction']; ?>">
                                <?php 
                                if($viewsGrowth['direction'] == 'up') echo '↑ ' . $viewsGrowth['percentage'] . '%';
                                elseif($viewsGrowth['direction'] == 'down') echo '↓ ' . $viewsGrowth['percentage'] . '%';
                                else echo '→ 0%';
                                ?>
                            </span>
                            <span class="stat_comparison"><?php echo $LANG['vs'] ?? 'vs'; ?> <?php echo number_format($prevViews); ?></span>
                        </div>
                        <div class="stat_stat_avg"><?php echo number_format($avgViewsPerDay, 1); ?> <?php echo $LANG['avg_day'] ?? 'avg/day'; ?></div>
                    </div>
                </div>

                <div class="stat_stat_card followers">
                    <div class="stat_stat_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('140'));?>
                    </div>
                    <div class="stat_stat_content">
                        <div class="stat_stat_label"><?php echo $LANG['new_followers'] ?? 'New Followers'; ?></div>
                        <div class="stat_stat_value"><?php echo number_format($totalFollowers); ?></div>
                        <div class="stat_stat_meta">
                            <span class="stat_growth <?php echo $followersGrowth['direction']; ?>">
                                <?php 
                                if($followersGrowth['direction'] == 'up') echo '↑ ' . $followersGrowth['percentage'] . '%';
                                elseif($followersGrowth['direction'] == 'down') echo '↓ ' . $followersGrowth['percentage'] . '%';
                                else echo '→ 0%';
                                ?>
                            </span>
                            <span class="stat_comparison"><?php echo $LANG['vs'] ?? 'vs'; ?> <?php echo number_format($prevFollowers); ?></span>
                        </div>
                        <div class="stat_stat_avg"><?php echo ($LANG['total'] ?? 'Total'); ?>: <?php echo number_format($allTimeFollowers); ?></div>
                    </div>
                </div>

                <div class="stat_stat_card likes">
                    <div class="stat_stat_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('24'));?>
                    </div>
                    <div class="stat_stat_content">
                        <div class="stat_stat_label"><?php echo $LANG['total_likes'] ?? 'Total Likes'; ?></div>
                        <div class="stat_stat_value"><?php echo number_format($totalLikes); ?></div>
                        <div class="stat_stat_meta">
                            <span class="stat_growth <?php echo $likesGrowth['direction']; ?>">
                                <?php 
                                if($likesGrowth['direction'] == 'up') echo '↑ ' . $likesGrowth['percentage'] . '%';
                                elseif($likesGrowth['direction'] == 'down') echo '↓ ' . $likesGrowth['percentage'] . '%';
                                else echo '→ 0%';
                                ?>
                            </span>
                            <span class="stat_comparison"><?php echo $LANG['vs'] ?? 'vs'; ?> <?php echo number_format($prevLikes); ?></span>
                        </div>
                        <div class="stat_stat_avg"><?php echo number_format($avgLikesPerDay, 1); ?> <?php echo $LANG['avg_day'] ?? 'avg/day'; ?></div>
                    </div>
                </div>

                <div class="stat_stat_card comments">
                    <div class="stat_stat_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('114'));?>
                    </div>
                    <div class="stat_stat_content">
                        <div class="stat_stat_label"><?php echo $LANG['comments'] ?? 'Comments'; ?></div>
                        <div class="stat_stat_value"><?php echo number_format($totalComments); ?></div>
                        <div class="stat_stat_meta">
                            <span class="stat_growth <?php echo $commentsGrowth['direction']; ?>">
                                <?php 
                                if($commentsGrowth['direction'] == 'up') echo '↑ ' . $commentsGrowth['percentage'] . '%';
                                elseif($commentsGrowth['direction'] == 'down') echo '↓ ' . $commentsGrowth['percentage'] . '%';
                                else echo '→ 0%';
                                ?>
                            </span>
                            <span class="stat_comparison"><?php echo $LANG['vs'] ?? 'vs'; ?> <?php echo number_format($prevComments); ?></span>
                        </div>
                        <div class="stat_stat_avg"><?php echo number_format($avgEngagement); ?> <?php echo $LANG['interactions'] ?? 'interactions'; ?></div>
                    </div>
                </div>

                <div class="stat_stat_card engagement">
                    <div class="stat_stat_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('46'));?>
                    </div>
                    <div class="stat_stat_content">
                        <div class="stat_stat_label"><?php echo $LANG['engagement_rate'] ?? 'Engagement Rate'; ?></div>
                        <div class="stat_stat_value"><?php echo $engagementRate; ?>%</div>
                        <div class="stat_stat_meta">
                            <span class="stat_growth <?php echo $engagementGrowth['direction']; ?>">
                                <?php 
                                if($engagementGrowth['direction'] == 'up') echo '↑ ' . $engagementGrowth['percentage'] . '%';
                                elseif($engagementGrowth['direction'] == 'down') echo '↓ ' . $engagementGrowth['percentage'] . '%';
                                else echo '→ 0%';
                                ?>
                            </span>
                            <span class="stat_comparison"><?php echo $LANG['vs'] ?? 'vs'; ?> <?php echo $prevEngagementRate; ?>%</span>
                        </div>
                        <div class="stat_stat_avg"><?php echo $LANG['quality_score'] ?? 'Quality score'; ?></div>
                    </div>
                </div>

                <div class="stat_stat_card shares">
                    <div class="stat_stat_icon">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('27'));?>
                    </div>
                    <div class="stat_stat_content">
                        <div class="stat_stat_label"><?php echo $LANG['posts_created'] ?? 'Posts Created'; ?></div>
                        <div class="stat_stat_value"><?php echo number_format($totalPosts); ?></div>
                        <div class="stat_stat_meta">
                            <span class="stat_comparison"><?php echo ($LANG['total'] ?? 'Total'); ?>: <?php echo number_format($allTimePosts); ?></span>
                        </div>
                        <div class="stat_stat_avg"><?php echo round($postsPerDay, 1); ?> <?php echo $LANG['posts_day'] ?? 'posts/day'; ?></div>
                    </div>
                </div>
            </div>

            <div class="stat_chart_section">
                <h3 class="stat_section_title"><?php echo $LANG['performance_comparison'] ?? 'Performance Comparison'; ?></h3>
                <div class="stat_chart_wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <div class="stat_chart_section">
                <h3 class="stat_section_title"><?php echo $LANG['daily_activity_trend'] ?? 'Daily Activity Trend'; ?></h3>
                <div class="stat_chart_wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="stat_insights_section">
                <h3 class="stat_section_title"><?php echo $LANG['key_insights'] ?? 'Key Insights'; ?></h3>
                <div class="stat_insights_grid">
                    <div class="stat_insight_card <?php echo $engagementClass; ?>">
                        <div class="stat_insight_header">
                            <span class="stat_insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('114'));?></span>
                            <h4><?php echo $LANG['engagement_quality'] ?? 'Engagement Quality'; ?></h4>
                        </div>
                        <p><?php echo $engagementInsight; ?></p>
                    </div>

                    <div class="stat_insight_card <?php echo $growthClass; ?>">
                        <div class="stat_insight_header">
                            <span class="stat_insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('147'));?></span>
                            <h4><?php echo $LANG['growth_momentum'] ?? 'Growth Momentum'; ?></h4>
                        </div>
                        <p><?php echo $growthInsight; ?></p>
                    </div>

                    <div class="stat_insight_card">
                        <div class="stat_insight_header">
                            <span class="stat_insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('46'));?></span>
                            <h4><?php echo $LANG['content_strategy'] ?? 'Content Strategy'; ?></h4>
                        </div>
                        <p><?php echo $contentInsight; ?></p>
                        <?php if($bestMetric): ?>
                            <p class="stat_insight_highlight"><?php echo $LANG['best'] ?? 'Best'; ?>: <strong><?php echo iN_HelpSecure($bestMetric); ?></strong> (+<?php echo number_format($bestGrowth, 1); ?>%)</p>
                        <?php endif; ?>
                    </div>

                    <div class="stat_insight_card">
                        <div class="stat_insight_header">
                            <span class="stat_insight_icon"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('115'));?></span>
                            <h4><?php echo $LANG['audience_engagement'] ?? 'Audience Engagement'; ?></h4>
                        </div>
                        <p><?php echo $audienceInsight; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat_breakdown_section">
                <button type="button" onclick="toggleBreakdown()" class="stat_breakdown_toggle">
                    <span><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('125'));?></span>
                    <span><?php echo $LANG['daily_breakdown_table'] ?? 'Daily Breakdown Table'; ?></span>
                    <span class="stat_toggle_icon">▼</span>
                </button>
                
                <div id="detailedBreakdown" class="stat_breakdown_content" style="display: none;">
                    <div class="stat_table_wrapper">
                        <table class="stat_data_table">
                            <thead>
                                <tr>
                                    <th><?php echo $LANG['date'] ?? 'Date'; ?></th>
                                    <th><?php echo $LANG['views'] ?? 'Views'; ?></th>
                                    <th><?php echo $LANG['followers'] ?? 'Followers'; ?></th>
                                    <th><?php echo $LANG['likes'] ?? 'Likes'; ?></th>
                                    <th><?php echo $LANG['comments'] ?? 'Comments'; ?></th>
                                    <th><?php echo $LANG['total'] ?? 'Total'; ?></th>
                                </tr>
                            </thead>
                            <tbody id="breakdownTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
         </div>
    </div>
  </div>
</div>

<script id="statsData" type="application/json">
<?php
$chartLabels = [];
$chartViews = [];
$chartFollowers = [];
$chartLikes = [];
$chartComments = [];
$tableData = [];

$dateRange = [];
$currentDate = $startTime;
while($currentDate <= $currentTime) {
    $dateKey = date('Y-m-d', $currentDate);
    $dateRange[$dateKey] = [
        'views' => 0, 'followers' => 0, 'likes' => 0, 'comments' => 0,
        'display' => i18n_date_Md($currentDate, $userLang)
    ];
    $currentDate = strtotime('+1 day', $currentDate);
}

foreach($dailyViews as $d) if(isset($dateRange[$d['view_date']])) $dateRange[$d['view_date']]['views'] = (int)$d['daily_views'];
foreach($dailyFollowers as $d) if(isset($dateRange[$d['follow_date']])) $dateRange[$d['follow_date']]['followers'] = (int)$d['daily_followers'];
foreach($dailyLikes as $d) if(isset($dateRange[$d['like_date']])) $dateRange[$d['like_date']]['likes'] = (int)$d['daily_likes'];
foreach($dailyComments as $d) if(isset($dateRange[$d['comment_date']])) $dateRange[$d['comment_date']]['comments'] = (int)$d['daily_comments'];

foreach($dateRange as $date => $data) {
    $chartLabels[] = $data['display'];
    $chartViews[] = $data['views'];
    $chartFollowers[] = $data['followers'];
    $chartLikes[] = $data['likes'];
    $chartComments[] = $data['comments'];
    $tableData[] = [
        'date' => i18n_date_MdY(strtotime($date), $userLang),
        'views' => $data['views'],
        'followers' => $data['followers'],
        'likes' => $data['likes'],
        'comments' => $data['comments']
    ];
}

echo json_encode([
    'labels' => $chartLabels,
    'trend' => [
        'views' => $chartViews,
        'followers' => $chartFollowers,
        'likes' => $chartLikes,
        'comments' => $chartComments
    ],
    'comparison' => [
        'current' => [$totalViews, $totalFollowers, $totalLikes, $totalComments, $totalPosts, $engagementRate],
        'previous' => [$prevViews, $prevFollowers, $prevLikes, $prevComments, $prevPosts, $prevEngagementRate]
    ],
    'table' => $tableData
], JSON_UNESCAPED_UNICODE);
?>
</script>

<script>
/* Expose localized labels to your chart JS */
window.STATS_L10N = {
  performance_comparison: <?php echo json_encode($LANG['performance_comparison'] ?? 'Performance Comparison'); ?>,
  daily_activity_trend: <?php echo json_encode($LANG['daily_activity_trend'] ?? 'Daily Activity Trend'); ?>,
  post_views: <?php echo json_encode($LANG['post_views'] ?? 'Post Views'); ?>,
  new_followers: <?php echo json_encode($LANG['new_followers'] ?? 'New Followers'); ?>,
  total_likes: <?php echo json_encode($LANG['total_likes'] ?? 'Total Likes'); ?>,
  comments: <?php echo json_encode($LANG['comments'] ?? 'Comments'); ?>,
  posts_created: <?php echo json_encode($LANG['posts_created'] ?? 'Posts Created'); ?>,
  engagement_rate: <?php echo json_encode($LANG['engagement_rate'] ?? 'Engagement Rate'); ?>,
  current: <?php echo json_encode($LANG['select_period'] ?? 'Current'); ?>,
  previous: <?php echo json_encode($LANG['previous_period'] ?? 'Previous Period'); ?>
};
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?php echo $base_url; ?>themes/<?php echo $currentTheme; ?>/js/statisticsPro.js?v=<?php echo time(); ?>"></script>

<script>
/* Minimal helpers if your statisticsPro.js doesn't define them */
window.toggleCustomPicker = window.toggleCustomPicker || function(){
  var el = document.getElementById('customDatePicker');
  if(!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'flex' : 'none';
};

window.toggleBreakdown = window.toggleBreakdown || function(){
  var el = document.getElementById('detailedBreakdown');
  if(!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
  // Ensure no accidental zoom on mobile when toggling
  document.activeElement && document.activeElement.blur();
};

window.exportStats = window.exportStats || function(){
  // Simple CSV export using the embedded #statsData
  try {
    var data = JSON.parse(document.getElementById('statsData').textContent);
    var rows = [['<?php echo $LANG['date'] ?? 'Date'; ?>','<?php echo $LANG['views'] ?? 'Views'; ?>','<?php echo $LANG['followers'] ?? 'Followers'; ?>','<?php echo $LANG['likes'] ?? 'Likes'; ?>','<?php echo $LANG['comments'] ?? 'Comments'; ?>','<?php echo $LANG['total'] ?? 'Total'; ?>']];
    (data.table || []).forEach(function(r){
      var total = (r.views|0)+(r.followers|0)+(r.likes|0)+(r.comments|0);
      rows.push([r.date, r.views, r.followers, r.likes, r.comments, total]);
    });
    var csv = rows.map(r => r.join(',')).join('\n');
    var blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'stats.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
  } catch(e) {
    console.error(e);
  }
};
</script>
