<?php
/* themes/<theme>/layouts/comments_only.php - FINAL WORKING VERSION */

if (!isset($db) || !($db instanceof mysqli)) { http_response_code(500); echo 'DB not ready'; exit; }

$postID = 0;
if (!empty($_POST['post_id']))          $postID = (int)$_POST['post_id'];
elseif (!empty($GLOBALS['userPostID'])) $postID = (int)$GLOBALS['userPostID'];
if ($postID <= 0) { echo '<div style="padding:14px;color:#f55">Invalid post.</div>'; return; }

function mf_e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function mf_user_url($base_url, $uname){ return rtrim($base_url,'/').'/'.ltrim($uname,'/'); }
function mf_linkify_mentions($text, $base_url){
  return preg_replace_callback('/(^|[\s>])@([A-Za-z0-9_.]{3,32})\b/u', function($m) use ($base_url){
    $u = $m[2];
    $url = mf_user_url($base_url, $u);
    return $m[1].'<a class="mention" href="'.mf_e($url).'" target="_blank">@'.mf_e($u).'</a>';
  }, mf_e($text));
}

$comments = [];
$sql = "
  SELECT
    C.com_id, C.comment, C.comment_time, C.comment_uid_fk,
    U.i_username, U.i_user_fullname, U.user_verified_status
  FROM i_post_comments C
  JOIN i_users U ON U.iuid = C.comment_uid_fk
  WHERE C.comment_post_id_fk = ?
  ORDER BY C.com_id ASC
";
if ($st = $db->prepare($sql)) {
  $st->bind_param('i', $postID);
  $st->execute();
  if ($res = $st->get_result()) while ($r = $res->fetch_assoc()) $comments[] = $r;
  $st->close();
}

$meAva = '';
if (!empty($userID) && isset($iN) && method_exists($iN,'iN_UserAvatar')) {
  $meAva = $iN->iN_UserAvatar((int)$userID, $base_url);
}
?>
<div class="modal-comments-wrapper" data-post="<?php echo (int)$postID; ?>">

  <div class="i_user_comments" id="i_user_comments_<?php echo (int)$postID; ?>">
    <?php if (!$comments): ?>
    <div class="no-comments-placeholder"><?php echo $LANG['no_comments_yet'] ?? 'No comments yet. Be the first to comment!'; ?></div>
    <?php else: ?>
      <?php foreach ($comments as $c):
        $cid   = (int)$c['com_id'];
        $uid   = (int)$c['comment_uid_fk'];
        $uname = $c['i_username'] ?: ('user'.$uid);
        $fname = $c['i_user_fullname'] ?: $uname;
        $when  = (int)$c['comment_time'];
        $ago   = $when ? ($GLOBALS['TimeAgo'] ?? null ? TimeAgo::ago(date('Y-m-d H:i:s',$when), date('Y-m-d H:i:s')) : gmdate('M j', $when)) : '';
        $ava   = (isset($iN) && method_exists($iN,'iN_UserAvatar')) ? $iN->iN_UserAvatar($uid, $base_url) : '';
        $uurl  = mf_user_url($base_url, $uname);
        $text  = mf_linkify_mentions($c['comment'] ?? '', $base_url);

        $likesCount = 0;
        if ($st2 = $db->prepare("SELECT COUNT(*) FROM i_post_comment_likes WHERE c_like_comment_id=?")) {
          $st2->bind_param('i', $cid); $st2->execute(); $st2->bind_result($likesCount); $st2->fetch(); $st2->close();
        }
        $alreadyLiked = false;
        if (!empty($userID) && $st3 = $db->prepare("SELECT 1 FROM i_post_comment_likes WHERE c_like_comment_id=? AND c_like_iuid_fk=? LIMIT 1")) {
          $uidCur = (int)$userID; $st3->bind_param('ii', $cid, $uidCur); $st3->execute(); $st3->store_result(); $alreadyLiked = $st3->num_rows > 0; $st3->close();
        }
        $likeClass = $alreadyLiked ? 'c_in_unlike' : 'c_in_like';
        $isOwner   = !empty($userID) && ($userID == $uid);
      ?>
      <div class="i_u_comment_body dlCm<?php echo $cid; ?>" id="<?php echo $cid; ?>">
        <div class="i_post_user_commented_avatar_out">
          <img class="i_post_user_commented_avatar" src="<?php echo mf_e($ava); ?>" alt="">
        </div>

        <div class="i_user_commented_body">
          <div class="i_user_commented_user_infos">
            <a href="<?php echo mf_e($uurl); ?>" target="_blank"><?php echo mf_e($fname); ?></a>
          </div>

          <div class="i_user_comment_text" id="i_u_c_<?php echo $cid; ?>">
            <?php echo $text; ?>
          </div>

          <div class="i_comment_like_time">
          <a class="i_comment_reply rplyComment" id="<?php echo (int)$postID; ?>" data-who="<?php echo mf_e($uname); ?>"><?php echo $LANG['reply'] ?? 'Reply'; ?></a>
            <div class="i_comment_like_btn">
              <div class="i_comment_item_btn transition <?php echo $likeClass; ?>" id="com_<?php echo $cid; ?>" data-id="<?php echo $cid; ?>" data-p="<?php echo (int)$postID; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
              </div>
              <div class="i_comment_like_sum" id="t_c_<?php echo $cid; ?>"><?php echo (int)$likesCount; ?></div>
            </div>

            <div class="i_comment_time"><?php echo mf_e($ago); ?></div>

            <?php if ($isOwner): ?>
            <div class="i_comment_call_popup openComMenu" id="<?php echo $cid; ?>">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M12 8a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
              <div class="i_comment_menu_container comMBox comMBox<?php echo $cid; ?>">
                <div class="i_comment_menu_wrapper">
                  <div class="i_post_menu_item_out cced transition" id="<?php echo $cid; ?>" data-id="<?php echo (int)$postID; ?>">
                   <svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1.25 1.25 0 000-1.77l-2.98-2.98a1.25 1.25 0 00-1.77 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> <?php echo $LANG['edit'] ?? 'Edit'; ?>
                  </div>
                  <div class="i_post_menu_item_out delCm transition" id="<?php echo $cid; ?>" data-id="<?php echo (int)$postID; ?>">
                  <svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M6 7h12v14H6zM9 3h6v2H9zM4 7h16v2H4z"/></svg> <?php echo $LANG['delete'] ?? 'Delete'; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="i_comment_form">
    <div class="i_post_user_comment_avatar">
      <img src="<?php echo mf_e($meAva); ?>" alt="">
    </div>
    <div class="i_comment_form_textarea">
   <textarea class="comment nwComment" data-id="<?php echo (int)$postID; ?>" id="comment<?php echo (int)$postID; ?>" placeholder="<?php echo $LANG['write_your_comment'] ?? 'Write your comment...'; ?>"></textarea>
    </div>
   <button type="button" class="i_fa_body sndcom mf-send-comment" data-id="<?php echo (int)$postID; ?>"><?php echo $LANG['send'] ?? 'Send'; ?></button>
  </div>

</div>

<style>
/* ===================================================================== */
/* === FINAL, CLEAN STYLES FOR comments_only.php === */
/* ===================================================================== */

/* --- Core Layout & Scrolling --- */
.modal-comments-wrapper { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
.modal-comments-wrapper .i_user_comments { flex: 1 1 auto; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 0 16px; }
.no-comments-placeholder { padding: 24px 16px; color: #666; text-align: center; }

/* --- Comment Item Layout --- */
.i_u_comment_body { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
.i_post_user_commented_avatar_out { width: 36px; flex: 0 0 36px; }
.i_post_user_commented_avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; display: block; }
.i_user_commented_body { flex: 1; min-width: 0; }

/* --- Username and Comment Text --- */
.i_user_commented_user_infos { margin-bottom: 2px; }
.i_user_commented_user_infos a { font-weight: 600; color: #111; text-decoration: none; font-size: 14px; }
.i_user_comment_text { color: #333; line-height: 1.45; word-wrap: break-word; font-size: 14px; }
.modal-comments-wrapper a.mention { color: #00376b; text-decoration: none; }

/* --- Comment Actions (THIS FIXES THE ALIGNMENT) --- */
.i_comment_like_time { display: flex; align-items: center; gap: 16px; margin-top: 8px; font-size: 12px; color: #8e8e8e; }
.i_comment_reply { color: inherit; cursor: pointer; text-decoration: none; font-weight: 500; }
.i_comment_like_btn { display: flex; align-items: center; gap: 5px; }
.i_comment_like_sum { font-weight: 600; }
.i_comment_time { color: #aaa; }

/* --- THE FINAL, GUARANTEED HEART FIX --- */
/* By default, the heart is filled grey */
.i_comment_item_btn svg path {
    fill: #a8a8a8; /* Neutral grey fill */
}
/* When the '.c_in_unlike' (liked) class is present, the heart is filled red */
.i_comment_item_btn.c_in_unlike svg path {
    fill: #ff3b5c !important; /* Force red fill */
}

/* --- Composer & Menu --- */
.i_comment_form { position: sticky; bottom: 0; z-index: 10; display: flex; align-items: center; gap: 12px; background: #fff; border-top: 1px solid #e6e6e6; padding: 12px 16px; }
.i_post_user_comment_avatar img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; display: block; }
.i_comment_form_textarea { flex: 1 1 auto; }
textarea.comment { width: 100%; min-height: 44px; max-height: 140px; resize: vertical; padding: 10px 15px; border: 1px solid #ddd; border-radius: 22px; font: inherit; font-size: 14px; }
.i_comment_call_popup { position: relative; margin-left: auto; cursor: pointer; }
.i_comment_menu_container { display: none; position: absolute; right: 0; top: 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,.1); z-index: 20; min-width: 160px; padding: 6px; }
.i_post_menu_item_out { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; cursor: pointer; color: #262626; font-size: 14px; }
.i_post_menu_item_out svg { flex-shrink: 0; }
.i_post_menu_item_out:hover { background: #f3f4f6; }
</style>