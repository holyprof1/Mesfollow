<?php
/**
 * Grid Renderer - TikTok Style (with blur on locked media)
 * Shows Premium or Followers badge on top-left
 * and blurs the media for locked posts
 */

if (defined('MEDIA_GRID_RENDERED')) return;
define('MEDIA_GRID_RENDERED', true);

// allow grid for profile media tabs and for saved posts
$page = $page ?? ($_POST['type'] ?? '');
$isGridMode = in_array($pCat, ['photos','videos','audios'], true) || $page === 'savedpost';


if ($isGridMode && !empty($postsFromData)) {
    echo '<div class="media-grid-container" style="padding: 15px; padding-bottom: 70px;">';
    
    $processedPostIds = [];
    $hasValidMedia = false;
    
    foreach ($postsFromData as $postFromData) {
        $userPostID = $postFromData['post_id'] ?? null;
        
        if (in_array($userPostID, $processedPostIds)) continue;
        $processedPostIds[] = $userPostID;
        
        $userPostFile = $postFromData['post_file'] ?? null;
        $userPostText = $postFromData['post_text'] ?? null;
        $userPostWhoCanSee = $postFromData['who_can_see'] ?? '1';
        $userPostOwnerID = $postFromData['post_owner_id'] ?? null;
        
        if (empty($userPostFile)) continue;
        
        $fileIds = array_filter(array_map('trim', explode(',', $userPostFile)));
        $firstFileId = $fileIds[0] ?? null;
        $fileCount = count($fileIds);
        
        if (!$firstFileId) continue;
        
        // Permissions check
        $canView = true;
        $isPremium = false;
        $isSubscriberOnly = false;
        
        if ($logedIn == 1 && $userID == $userPostOwnerID) {
            $canView = true;
        } else {
            if ($logedIn == 0) {
                if ($userPostWhoCanSee == '2' || $userPostWhoCanSee == '3' || $userPostWhoCanSee == '4') {
                    $canView = false;
                    $isPremium = ($userPostWhoCanSee == '3' || $userPostWhoCanSee == '4');
                    $isSubscriberOnly = !$isPremium;
                }
            } else {
                $getFriendStatus = $iN->iN_GetRelationsipBetweenTwoUsers($userID, $userPostOwnerID);
                if ($userPostWhoCanSee == '2' && $getFriendStatus != '1') {
                    $canView = false;
                    $isSubscriberOnly = true;
                } else if ($userPostWhoCanSee == '3' || $userPostWhoCanSee == '4') {
                    if ($iN->iN_CheckUserPurchasedThisPost($userID, $userPostID) != '1') {
                        $canView = false;
                        $isPremium = true;
                    }
                }
            }
        }
        
        // Get file info
        $stmt = $db->prepare("SELECT * FROM i_user_uploads WHERE upload_id = ? AND upload_status = '1' LIMIT 1");
        if (!$stmt) continue;
        
        $stmt->bind_param("i", $firstFileId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fileInfo = $result->fetch_assoc();
        $stmt->close();
        
        if (!$fileInfo) continue;
        
        $fileExt = strtolower($fileInfo['uploaded_file_ext'] ?? '');
        $uploadedFilePath = $fileInfo['uploaded_file_path'] ?? '';
        
        // Build file path
        if ($s3Status == 1) {
            $filePath = 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . $uploadedFilePath;
        } else if ($WasStatus == '1') {
            $filePath = 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . $uploadedFilePath;
        } else if ($digitalOceanStatus == '1') {
            $filePath = 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . $uploadedFilePath;
        } else {
            $filePath = $base_url . $uploadedFilePath;
        }
        
        // Determine type
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $videoExts = ['mp4', 'mov', 'm4v', 'webm', 'mkv', 'avi'];
        $audioExts = ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'flac'];
        
        if (in_array($fileExt, $imageExts)) {
            $fileType = 'image';
        } elseif (in_array($fileExt, $videoExts)) {
            $fileType = 'video';
        } elseif (in_array($fileExt, $audioExts)) {
            $fileType = 'audio';
        } else {
            continue;
        }
        
        $hasValidMedia = true;
        
        // Get stats including views
        $likeSum = 0;
        $commentCount = 0;
        $viewCount = 0;
        if ($canView) {
            $likeSum = $iN->iN_TotalPostLiked($userPostID);
            $allComments = $iN->iN_GetPostComments($userPostID, 0);
            $commentCount = $allComments ? count($allComments) : 0;
            
            if (function_exists('mf_post_views')) {
                $viewCount = mf_post_views($userPostID, $iN, $db);
            }
        }
        
        $slugUrl = $base_url . 'post/' . ($postFromData['url_slug'] ?? '') . '_' . $userPostID;
        ?>
        
        <div class="media-grid-item <?php if($fileType=='audio') echo 'audio-item'; ?>" 
             data-post-id="<?php echo $userPostID; ?>" 
             onclick="window.location.href='<?php echo iN_HelpSecure($slugUrl); ?>'">
             
            <div class="media-grid-content" <?php if (!$canView) echo 'style="filter: blur(20px);"'; ?>>
                <?php if ($fileType == 'image'): ?>
                    <img src="<?php echo iN_HelpSecure($filePath); ?>" 
                         alt="<?php echo iN_HelpSecure(substr($userPostText, 0, 100)); ?>" 
                         loading="lazy">
                    
                <?php elseif ($fileType == 'video'): ?>
                    <div class="grid-video-wrapper" style="width:100%;height:100%;background:#000;position:relative;">
                        <video 
                            src="<?php echo iN_HelpSecure($filePath); ?>#t=0.5"
                            poster="<?php echo iN_HelpSecure($filePath); ?>#t=0.5"
                            muted 
                            playsinline
                            preload="none"
                            style="width:100%;height:100%;object-fit:cover;"></video>
                    </div>

                    <?php if ($viewCount > 0): ?>
                        <div class="video-plays-badge" style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,0.7);color:#fff;padding:4px 8px;border-radius:12px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;z-index:5;pointer-events:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="12" height="12">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <?php echo function_exists('mf_k') ? mf_k($viewCount) : number_format($viewCount); ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="audio-icon" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea,#764ba2);">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="48" height="48">
                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($fileCount > 1): ?>
                <div class="media-count-badge" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.7);color:#fff;padding:4px 8px;border-radius:12px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;z-index:5;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" width="12" height="12">
                        <path d="M3 6h18v12H3V6zm2 2v8h14V8H5zm14-6v2H3V2h16zM3 20h16v2H3v-2z"/>
                    </svg>
                    <?php echo $fileCount; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$canView): ?>
                <div class="media-premium-badge" style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.8);color:#fff;padding:6px 10px;border-radius:16px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;z-index:6;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    <?php echo $isPremium ? ($LANG['l_premium'] ?? 'Premium') : ($LANG['followers_only'] ?? 'Followers'); ?>
                </div>

                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.8);border-radius:50%;padding:20px;z-index:7;pointer-events:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="48" height="48">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                </div>
            <?php endif; ?>

            <?php if ($canView && ($likeSum > 0 || $commentCount > 0)): ?>
                <div class="media-grid-overlay">
                    <div class="media-grid-stats">
                        <?php if ($likeSum > 0): ?>
                            <div class="media-grid-stat">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3c3.08 0 5.5 2.42 5.5 5.5 0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                <?php echo number_format($likeSum); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($commentCount > 0): ?>
                            <div class="media-grid-stat">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                                </svg>
                                <?php echo number_format($commentCount); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    echo '</div>';
    
    if (!$hasValidMedia) {
        ?>
        <div class="media-grid-empty" style="margin-bottom:60px;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
            </svg>
            <h3><?php echo $LANG['no_media_found'] ?? 'No media found'; ?></h3>
            <p><?php echo $LANG['no_media_description'] ?? 'No content available yet.'; ?></p>
        </div>
        <?php
    }

} elseif ($isGridMode && empty($postsFromData)) {
    ?>
    <div class="media-grid-empty" style="margin-bottom:60px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
        </svg>
        <h3><?php echo $LANG['no_media_found'] ?? 'No media found'; ?></h3>
        <p><?php echo $LANG['no_media_description'] ?? 'No content available yet.'; ?></p>
    </div>
    <?php
}
?>

<script>
(function() {
    if (window.__gridVideoLoader) return;
    window.__gridVideoLoader = true;
    document.querySelectorAll('.grid-video-wrapper video').forEach(v => {
        setTimeout(() => {
            v.preload = 'metadata';
            v.load();
        }, 100);
    });
})();
</script>
