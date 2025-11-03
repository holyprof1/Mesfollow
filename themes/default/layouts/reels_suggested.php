<?php
// themes/default/layouts/reels_suggested.php
if (!isset($db) || !($db instanceof mysqli)) { 
    echo "<!-- reels: no db connection -->";
    return; 
}
if (!isset($base_url)) {
    echo "<!-- reels: no base_url -->";
    return;
}

$limit = 6;
$base  = rtrim($base_url, '/');

// Build absolute URL
$__mf_file_url = function($path) use ($base, $s3Status, $s3Bucket, $s3Region, $WasStatus, $WasBucket, $WasRegion, $digitalOceanStatus, $oceanspace_name, $oceanregion) {
  if (!$path) { return ''; }
  if (preg_match('~^https?://~i', $path)) { return $path; }
  
  if (!empty($s3Status) && $s3Status == '1' && !empty($s3Bucket) && !empty($s3Region)) {
    return 'https://' . $s3Bucket . '.s3.' . $s3Region . '.amazonaws.com/' . ltrim($path, '/');
  }
  if (!empty($WasStatus) && $WasStatus == '1' && !empty($WasBucket) && !empty($WasRegion)) {
    return 'https://' . $WasBucket . '.s3.' . $WasRegion . '.wasabisys.com/' . ltrim($path, '/');
  }
  if (!empty($digitalOceanStatus) && $digitalOceanStatus == '1' && !empty($oceanspace_name) && !empty($oceanregion)) {
    return 'https://' . $oceanspace_name . '.' . $oceanregion . '.digitaloceanspaces.com/' . ltrim($path, '/');
  }
  
  return $base . '/' . ltrim($path, '/');
};

// Fetch random recent public video posts with ACTUAL video files
$sql = "
  SELECT p.post_id,
         (SELECT up.uploaded_file_path
            FROM i_user_uploads up
           WHERE FIND_IN_SET(up.upload_id, p.post_file)
             AND LOWER(up.uploaded_file_ext) IN ('mp4','mov','webm','m4v')
           ORDER BY up.upload_id DESC
           LIMIT 1) AS video_path
  FROM i_posts p
  WHERE p.post_status='1'
    AND p.who_can_see='1'
    AND p.post_file IS NOT NULL
    AND p.post_file != ''
    AND EXISTS (
      SELECT 1
      FROM i_user_uploads f
      WHERE f.upload_status='1'
        AND LOWER(f.uploaded_file_ext) IN ('mp4','mov','webm','m4v')
        AND FIND_IN_SET(
              f.upload_id,
              REPLACE(TRIM(BOTH ',' FROM p.post_file), ' ', '')
            ) > 0
    )
  ORDER BY RAND()
  LIMIT {$limit}
";

$items = [];
if ($res = mysqli_query($db, $sql)) {
  while ($r = mysqli_fetch_assoc($res)) {
    $pid = (int)$r['post_id'];
    $videoPath = $r['video_path'] ?? '';
    
    if (!$videoPath) continue; // Skip if no video
    
    $videoUrl = $__mf_file_url($videoPath);
    
    // Try to find thumbnail (image or video poster)
    $thumb = '';
    $sqlImg = "
      SELECT uploaded_file_path AS pth
      FROM i_user_uploads f
      WHERE f.upload_status='1'
        AND LOWER(f.uploaded_file_ext) IN ('jpg','jpeg','png','gif','webp','bmp')
        AND FIND_IN_SET(
              f.upload_id,
              REPLACE(TRIM(BOTH ',' FROM (SELECT post_file FROM i_posts WHERE post_id={$pid})), ' ', '')
            ) > 0
      ORDER BY f.upload_id DESC
      LIMIT 1
    ";
    if ($ri = mysqli_query($db, $sqlImg)) {
      if ($riRow = mysqli_fetch_assoc($ri)) { 
        $thumb = $__mf_file_url($riRow['pth']); 
      }
      mysqli_free_result($ri);
    }

    // Fallback: try .jpg version of video
    if (!$thumb && $videoUrl) {
      $guess = preg_replace('~\.(mp4|mov|webm|m4v)(\?.*)?$~i', '.jpg', $videoUrl);
      $thumb = ($guess !== $videoUrl) ? $guess : '';
    }

    if (!$thumb) { 
      $thumb = $base . '/img/video_poster.jpg'; 
    }

    $items[] = [
      'id' => $pid, 
      'thumb' => $thumb,
      'video' => $videoUrl
    ];
  }
  mysqli_free_result($res);
}

if (!$items) { 
    echo "<!-- reels: no videos found -->";
    return; 
}

// Language label (use your language system)
$reelsLabel = $LANG['reels_suggested'] ?? 'Reels Suggested';
$viewMoreLabel = $LANG['view_more'] ?? 'View more';
?>
<!-- Reels Carousel -->
<section class="mf-reels-card" data-mf-reels>
  <div class="mf-reels-head">
    <span><?= htmlspecialchars($reelsLabel, ENT_QUOTES, 'UTF-8') ?></span>
    <a class="mf-reels-cta" href="<?= $base ?>/videos"><?= htmlspecialchars($viewMoreLabel, ENT_QUOTES, 'UTF-8') ?></a>
  </div>
  <div class="mf-reels-row">
    <?php foreach ($items as $it): ?>
    <a class="mf-reels-item" 
   href="<?= $base ?>/videos?start=<?= (int)$it['id'] ?>#reel_<?= (int)$it['id'] ?>"
         data-reel-id="<?= (int)$it['id'] ?>"
         data-video-src="<?= htmlspecialchars($it['video'], ENT_QUOTES, 'UTF-8') ?>">
        <video 
          class="mf-reel-video" 
          src="<?= htmlspecialchars($it['video'], ENT_QUOTES, 'UTF-8') ?>"
          poster="<?= htmlspecialchars($it['thumb'], ENT_QUOTES, 'UTF-8') ?>"
          muted
          playsinline
          preload="metadata"
        ></video>
        <div class="mf-play">▶</div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<script>
// Just trigger video load when visible - inora.js handles the 3-second loop
(function() {
  if (!window.IntersectionObserver) return;
  
  const reelVideos = document.querySelectorAll('.mf-reel-video');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      const video = entry.target;
      
      if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
        // Load and play - inora.js will handle the 3-second loop
        if (video.readyState === 0) {
          video.load();
        }
        video.play().catch(() => {});
      } else {
        // Pause when out of view
        video.pause();
      }
    });
  }, {
    threshold: 0.5,
    rootMargin: '50px'
  });
  
  reelVideos.forEach(video => observer.observe(video));
})();
</script>