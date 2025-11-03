<?php
// header.php
?>
<div class="header">
  <div class="i_header_in">
    <div class="i_logo tabing flex_">
      <a href="<?php echo iN_HelpSecure($base_url); ?>">
        <img src="<?php echo iN_HelpSecure($siteLogoUrl); ?>">
      </a>
      <?php if ($page === 'moreposts' && $logedIn == 1) { ?>
        <div class="mobile_hamburger tabing flex_">
          <div class="i_header_btn_item transition">
            <div class="i_h_in is_mobile">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('100')); ?>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>

    <!-- ===== Search (scoped; only active instance opens) ===== -->
    <div class="i_search relativePosition">
      <div class="mobile_back tabing flex_ mobile_srcbtn">
        <div class="i_header_btn_item transition">
          <div class="i_h_in is_mobile">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('102')); ?>
          </div>
        </div>
      </div>

      <input
        type="text"
        class="i_s_input"
        id="search_creator"
        placeholder="<?php echo iN_HelpSecure($LANG['search_creators']); ?>"
      >

      <div class="i_general_box_search_container generalBox search_cont_style">
        <div class="btest">
          <div class="i_user_details">
            <div class="i_box_messages_header">
              <?php echo iN_HelpSecure($LANG['search']); ?>
            </div>
            <!-- “Recent” shell is injected ABOVE this by JS -->
            <div class="i_header_others_box sb_items"></div>
          </div>
        </div>
      </div>
    </div>
    <!-- ===== /Search ===== -->

    <div class="i_header_right">
      <div class="i_one">
        <div class="i_header_btn_item transition search_mobile mobile_srcbtn">
          <div class="i_h_in">
            <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('101')); ?>
          </div>
        </div>

        <?php if ($logedIn == 1) { ?>
          <div class="i_header_btn_item topPoints transition ownTooltip"
               data-label="<?php echo iN_HelpSecure($LANG['get_point_and_point_balance']); ?>"
               id="topPoints" data-type="topPoints">
            <div class="i_h_in">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('40')); ?>
            </div>
          </div>

          <?php if ($iN->iN_ShopStatus(1) === 'yes') { ?>
            <div class="i_header_btn_item transition shopi ownTooltip"
                 data-label="<?php echo iN_HelpSecure($LANG['marketplace']); ?>">
              <a href="<?php echo iN_HelpSecure($base_url) . 'marketplace?cat=all'; ?>">
                <div class="i_h_in">
                  <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('158')); ?>
                </div>
              </a>
            </div>
          <?php } ?>

          <div class="i_header_btn_item topMessages transition ownTooltip"
               data-label="<?php echo iN_HelpSecure($LANG['messenger']); ?>"
               id="topMessages" data-type="topMessages">
            <div class="i_h_in">
              <div class="i_notifications_count msg_not nonePoint">
                <div class="isum sum_m" data-id=""><?php echo iN_HelpSecure($totalMessageNotifications); ?></div>
              </div>
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('38')); ?>
            </div>
          </div>

          <div class="i_header_btn_item topNotifications transition ownTooltip"
               data-label="<?php echo iN_HelpSecure($LANG['notifications']); ?>"
               id="topNotifications" data-type="topNotifications">
            <div class="i_h_in">
              <div class="i_notifications_count not_not nonePoint">
                <div class="isum sum_not" data-id=""><?php echo iN_HelpSecure($totalNotifications); ?></div>
              </div>
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('37')); ?>
            </div>
          </div>

          <div class="i_header_btn_item getMenu transition" id="topMenu" data-type="topMenu">
            <div class="i_h_in">
              <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('36')); ?>
            </div>
          </div>

        <?php } else { ?>
          <!-- Before Login -->
          <div class="i_login loginForm"><?php echo iN_HelpSecure($LANG['login']); ?></div>
          <a href="<?php echo iN_HelpSecure($base_url); ?>register"><div class="i_singup"><?php echo iN_HelpSecure($LANG['sign_up']); ?></div></a>
          <a href="<?php echo iN_HelpSecure($base_url); ?>creators"><div class="i_language"><?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95')); ?></div></a>
          <!-- /Before Login -->
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php if ($logedIn == 1) { ?>
  <audio id="notification-sound-mes" class="sound-controls" preload="auto">
    <source src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/mp3/message.mp3" type="audio/mpeg">
  </audio>
  <audio id="notification-sound-not" class="sound-controls aw" preload="auto">
    <source src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/mp3/not.mp3" type="audio/mpeg">
  </audio>
  <audio id="notification-sound-coin" class="sound-controls" preload="auto">
    <source src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/mp3/coin.mp3" type="audio/mpeg">
  </audio>
  <audio id="notification-sound-call" class="sound-controls" preload="auto">
    <source src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/mp3/call.mp3" type="audio/mpeg">
  </audio>
<?php } ?>

<!-- Vendor / utilities -->
<script src="<?php echo iN_HelpSecure($base_url); ?>src/gdpr-cookie.js?v=<?php echo iN_HelpSecure($version); ?>" defer></script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo $currentTheme; ?>/js/gdpr-handler.js?v=<?php echo time(); ?>" defer></script>
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo $currentTheme; ?>/js/fbvideo.v1.js?v=<?php echo time(); ?>" defer></script>

<!-- ALWAYS load the subscription click handler -->
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/subscribeHandler.js?v=<?php echo time(); ?>" defer></script>

<?php
$__paystack_pk = null;
if (isset($paystackPublicKey) && $paystackPublicKey) {
  $__paystack_pk = $paystackPublicKey;
} elseif (isset($PAYSTACK_PUBLIC_KEY) && $PAYSTACK_PUBLIC_KEY) {
  $__paystack_pk = $PAYSTACK_PUBLIC_KEY;
} elseif (isset($paymentKeys['paystack_public']) && $paymentKeys['paystack_public']) {
  $__paystack_pk = $paymentKeys['paystack_public'];
}
?>
<?php if ($__paystack_pk) { ?>
  <script src="https://js.paystack.co/v1/inline.js" defer></script>
  <script>
    window.paystackPK  = "<?php echo iN_HelpSecure($__paystack_pk); ?>";
    window.viewerId    = <?php echo (int)($userID ?? 0); ?>;
    window.viewerEmail = "<?php echo iN_HelpSecure($userData['email'] ?? ''); ?>";
  </script>
  <script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/subscriptionCheckout.js?v=<?php echo time(); ?>" defer></script>
<?php } ?>

<script>
  window.cookie_title  = "<?php echo iN_HelpSecure($LANG['cookie_title']); ?>";
  window.cookie_desc   = "<?php echo iN_HelpSecure($LANG['cookie_desc']); ?>";
  window.cookie_accept = "<?php echo iN_HelpSecure($LANG['accept']); ?>";
</script>
<script>try{ window.mfCallAudio = new Audio('<?php echo iN_HelpSecure($base_url); ?>themes/default/mp3/call.mp3'); }catch(e){}</script>

<!-- Global URLs used by many scripts -->
<script>
  window.mfBaseUrl = "<?php echo iN_HelpSecure($base_url); ?>";
  window.siteurl   = window.mfBaseUrl; // legacy var some scripts expect
</script>

<!-- Core one-time includes -->
<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/mentions-followers.js?v=<?php echo time(); ?>"></script>

<script src="<?php echo iN_HelpSecure($base_url); ?>themes/<?php echo iN_HelpSecure($currentTheme); ?>/js/custom-header.js?v=<?php echo time(); ?>" defer></script>

<!-- ================== SEARCH DROPDOWN STYLES ================== -->
<style>
  /* only the active search shows its popover */
  .i_general_box_search_container{ display:none; }
  .i_search{ position:relative; }
  .i_search.is-open{ z-index:9999; }
  .i_search.is-open > .i_general_box_search_container{ display:block; position:absolute; left:0; right:0; max-height:520px; overflow:visible; }

  /* inner scroll area */
  .i_search.is-open .i_user_details{ max-height:520px; overflow:auto; padding-bottom:12px; background:#fff; }

  /* sticky Recent shell */
  .i_search.is-open .mf_shell{ position:sticky; top:0; z-index:2; background:#fff; padding:8px 10px 6px; border-bottom:1px solid rgba(0,0,0,.07); }

  /* chips + rows */
  .mf_section_hd{display:flex;align-items:center;margin:0 0 6px}
  .mf_section_hd .title{font-weight:700;opacity:.85}
  .mf_section_hd .spacer{flex:1}
  .mf_clear_btn{background:none;border:0;padding:0;font-size:12px;opacity:.7;text-decoration:underline;cursor:pointer}
  .mf_chip_row{display:flex;flex-wrap:wrap;gap:8px;margin:4px 0 6px}
  .mf_chip{padding:6px 10px;border:1px solid rgba(0,0,0,.09);border-radius:999px;background:#fff;cursor:pointer}

  /* tidy list rows (recent profiles + live results) */
  .i_search.is-open .mf_profiles .i_message_wrpper,
  .i_search.is-open .sb_items     .i_message_wrpper{ display:block; padding:8px 0; border-bottom:1px solid rgba(0,0,0,.06); }
  .i_search.is-open .i_message_wrpper:last-child{ margin-bottom:8px; border-bottom:0; }
  .i_search.is-open .i_message_wrpper a{ display:block; }
  .i_search.is-open .sb_items{ display:block; }
	
	/* When typing, hide the Recent (chips + recent profiles) section */
.i_search.is-open.querying .mf_shell{ display:none; }

</style>

<!-- ================== SEARCH DROPDOWN SCRIPT ================== -->
<script>
(function(){
  "use strict";

  /* prevent duplicate init if header is included twice */
  if (window.__mfScopedSearchInit) return;
  window.__mfScopedSearchInit = true;

  const INPUT_SEL  = "#search_creator";
  const BOX_SEL    = ".i_general_box_search_container.generalBox.search_cont_style";
  const LIST_SEL   = ".i_header_others_box.sb_items";

  // mark only the focused header as open
  function openFor(el){
    const root = el.closest('.i_search');
    document.querySelectorAll('.i_search').forEach(r => {
      if (r === root) r.classList.add('is-open');
      else r.classList.remove('is-open');
    });
  }
  ['focus','input','click'].forEach(evt=>{
    document.addEventListener(evt, e=>{
      if (e.target && e.target.matches(INPUT_SEL)) openFor(e.target);
    }, true);
  });

  // close when clicking outside
  document.addEventListener('mousedown', e=>{
    const open = document.querySelector('.i_search.is-open');
    if (!open) return;
    if (e.target.closest('.i_search.is-open')) return;
    open.classList.remove('is-open');
  }, true);

  // Build "Recent" block once per visible header
  function ensureRecentShell(root){
    const box   = root.querySelector(BOX_SEL);
    const items = root.querySelector(LIST_SEL);
    if (!box || !items) return;

    // dedupe if multiple shells exist
    const shells = box.querySelectorAll('.mf_shell');
    if (shells.length > 1) shells.forEach((el,i)=>{ if(i>0) el.remove(); });

    let shell = box.querySelector('.mf_shell');
    if (!shell) {
      shell = document.createElement('div');
      shell.className = 'mf_shell';
      shell.innerHTML =
        '<div class="mf_recent">'+
          '<div class="mf_section_hd">'+
            '<div class="title">Recent</div><div class="spacer"></div>'+
            '<button class="mf_clear_btn" type="button">Clear</button>'+
          '</div>'+
          '<div class="mf_chip_row" id="mf_qchips"></div>'+
          '<div class="mf_profiles" id="mf_profiles"></div>'+
        '</div>';
      items.parentNode.insertBefore(shell, items);

      // minimal history (per browser)
      const j=JSON, QK="mf:search_history", UK="mf:profile_history", MAX=8;
      const get=(k,d)=>{try{const v=localStorage.getItem(k);return v?j.parse(v):d}catch(e){return d}};
      const set=(k,v)=>{try{localStorage.setItem(k,j.stringify(v))}catch(e){}};
      const esc=s=>String(s||"").replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
      const qchips = shell.querySelector('#mf_qchips');
      const plist  = shell.querySelector('#mf_profiles');

      function render(){
        const qs=get(QK,[]), us=get(UK,[]);
        qchips.innerHTML = qs.length ? qs.map(q=>'<button class="mf_chip" data-q="'+esc(q)+'">'+esc(q)+'</button>').join('') : '<div style="opacity:.6">Your recent searches will appear here</div>';
        plist.innerHTML  = us.length ? us.map(p=>(
          '<div class="i_message_wrpper"><a href="'+esc(p.u)+'">'+
            '<div class="i_message_wrapper transition">'+
              '<div class="i_message_owner_avatar"><div class="i_message_avatar"><img src="'+esc(p.a)+'" alt="'+esc(p.n)+'"></div></div>'+
              '<div class="i_message_info_container"><div class="i_message_owner_name">'+esc(p.n)+'</div></div>'+
            '</div>'+
          '</a></div>'
        )).join('') : '';
      }
      render();

      // chip fill + clear
      shell.addEventListener('click',e=>{
        if (e.target.matches('.mf_clear_btn')){ set(QK,[]); set(UK,[]); render(); return; }
        const chip = e.target.closest('.mf_chip');
        if (chip){
          const input = root.querySelector(INPUT_SEL);
          input.value = chip.getAttribute('data-q') || '';
          input.dispatchEvent(new Event('input',{bubbles:true}));
          input.focus();
        }
      });

      // mirror clicked profile to history (works when live results are clicked)
      box.addEventListener('click', e=>{
        const link = e.target.closest('.i_message_wrpper a'); if(!link) return;
        const nmEl = link.querySelector('.i_message_owner_name');
        const imEl = link.querySelector('img');
        const rec  = { n: nmEl ? nmEl.textContent.trim() : link.href, u: link.href, a: imEl ? imEl.src : '' };
        const arr  = get(UK,[]).filter(x=>x.u!==rec.u); arr.unshift(rec); if(arr.length>MAX) arr.length=MAX; set(UK,arr); render();
      });

      // remember query on Enter/blur/idle
      const input = root.querySelector(INPUT_SEL);
      function remember(q){
        q=(q!=null?q:input.value).trim(); if(!q) return;
        let arr=get(QK,[]).filter(x=>String(x).toLowerCase()!==q.toLowerCase()); arr.unshift(q); if(arr.length>MAX) arr.length=MAX; set(QK,arr); render();
      }
      input.addEventListener('keydown',e=>{ if(e.key==='Enter') remember(); });
      input.addEventListener('blur', ()=>remember());
      let idle; input.addEventListener('input', ()=>{ clearTimeout(idle); idle=setTimeout(()=>remember(input.value),1000); });
    }
  }

  // ensure a shell exists for each header instance
  document.querySelectorAll('.i_search').forEach(ensureRecentShell);

  // ---- Sync any results into the ACTIVE header only ----
  function syncResultsToActive(){
    const active = document.querySelector('.i_search.is-open');
    if (!active) return;
    const target = active.querySelector(LIST_SEL);
    if (!target) return;

    const all = Array.from(document.querySelectorAll(LIST_SEL));
    const others = all.filter(n => n !== target);

    // If some other list received content and target is empty, move it
    const donor = others.find(n => n.childNodes.length > 0);
    if (donor && !target.childNodes.length){
      target.innerHTML = donor.innerHTML;
      donor.innerHTML  = '';
    } else {
      // Otherwise always clear non-active lists
      others.forEach(n => { if (n.childNodes.length) n.innerHTML=''; });
    }
  }

  const debounced = (fn,t=80)=>{ let h; return ()=>{ clearTimeout(h); h=setTimeout(fn,t);} };
  const syncDebounced = debounced(syncResultsToActive,80);

  // watch all .sb_items for updates from existing AJAX code
  document.querySelectorAll(LIST_SEL).forEach(n=>{
    new MutationObserver(syncDebounced).observe(n, {childList:true, subtree:true});
  });

  // also sync when typing to catch immediate writes
  document.addEventListener('input', e=>{
    if (e.target && e.target.matches(INPUT_SEL)) syncDebounced();
  }, true);

})();
</script>
<script>
(function(){
  "use strict";

  const INPUT_SEL = "#search_creator";

  /* mark .i_search as 'querying' when input has text */
  function updateQueryingState(inputEl){
    var root = inputEl.closest('.i_search');
    if(!root) return;
    if ((inputEl.value || '').trim().length > 0) {
      root.classList.add('querying');
    } else {
      root.classList.remove('querying');
    }
  }

  /* remove duplicates: if a profile exists in live results, drop it from Recents */
  function dedupeRecents(root){
    if(!root) return;
    const results = root.querySelectorAll('.sb_items .i_message_wrpper a[href]');
    const recents = root.querySelectorAll('.mf_profiles .i_message_wrpper a[href]');
    if(!results.length || !recents.length) return;

    const seen = new Set(Array.from(results).map(a => a.href));
    recents.forEach(a => {
      if (seen.has(a.href)) {
        const row = a.closest('.i_message_wrpper');
        if (row) row.remove();
      }
    });
  }

  /* hook input typing */
  document.addEventListener('input', function(e){
    const t = e.target;
    if (!t || !t.matches(INPUT_SEL)) return;
    updateQueryingState(t);
    dedupeRecents(t.closest('.i_search'));
  }, true);

  /* run once on focus/click too */
  ['focus','click'].forEach(evt=>{
    document.addEventListener(evt, function(e){
      const t = e.target;
      if (!t || !t.matches(INPUT_SEL)) return;
      updateQueryingState(t);
      dedupeRecents(t.closest('.i_search'));
    }, true);
  });

  /* observe live results; whenever they change, de-dupe against Recents */
  const observeResults = (root)=>{
    const list = root && root.querySelector('.sb_items');
    if (!list) return;
    new MutationObserver(() => dedupeRecents(root))
      .observe(list, { childList:true, subtree:true });
  };

  /* set up observers for all header instances */
  document.querySelectorAll('.i_search').forEach(observeResults);

})();
</script>
