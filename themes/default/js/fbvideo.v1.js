<script>
/* FBV INLINE AUTOPLAY v2 — feed autoplay + desktop/mobile pause/play */
(function () {
  if (window.__FBV_INLINE__) return;
  window.__FBV_INLINE__ = true;

  let soundEnabled = false;
  let currentPlaying = null;
  const LOG = (...a) => console.log('[FBV2]', ...a);

  // --- CSS injected once ---
  const style = document.createElement('style');
  style.textContent = `
  .i_post_image_swip_wrapper.fb-inline{ position:relative; background:#000; }
  .i_post_image_swip_wrapper.fb-inline > img,
  .i_post_image_swip_wrapper.fb-inline > .playbutton{ display:none !important; }
  .i_post_image_swip_wrapper.fb-inline video{
    width:100%; height:auto; display:block; object-fit:cover;
    border-radius:12px; background:#000;
  }
  /* Center Play/Pause button */
  .mf-pp-btn{
    position:absolute; left:50%; top:50%; transform:translate(-50%,-50%);
    width:54px; height:54px; border:0; border-radius:50%;
    background:rgba(0,0,0,.55); color:#fff; cursor:pointer; z-index:10;
    display:flex; align-items:center; justify-content:center;
  }
  .mf-pp-btn:before{
    content:""; display:block; width:0; height:0;
    border-left:16px solid #fff; border-top:10px solid transparent; border-bottom:10px solid transparent;
    transform:translateX(2px);
  }
  .mf-pp-btn.is-playing:before{
    content:""; width:16px; height:16px; background:
      linear-gradient(90deg,#fff 0 40%, transparent 40 60%, #fff 60 100%);
    border:none; transform:none;
  }
  /* Keep view chip above video but below button */
  .mf_view_chip{ z-index:9; pointer-events:none; }
  `;
  document.head.appendChild(style);

  // --- Enable sound on first interaction (policy-friendly) ---
  function enableAllSound() {
    if (soundEnabled) return;
    soundEnabled = true;
    LOG('🔊 Sound enabled by user gesture');
    document.querySelectorAll('.i_post_image_swip_wrapper.fb-inline video').forEach(v => {
      v.muted = false;
      v.dataset.wantSound = "1";
    });
  }
  ['touchstart','touchend','touchmove','mousedown','mouseup','click','scroll','wheel','keydown','keyup']
  .forEach(evt => {
    window.addEventListener(evt, enableAllSound, { once:true, passive:true, capture:true });
  });

  // --- Keep only one video playing at a time ---
  function ensureSingle(v){
    if (currentPlaying && currentPlaying !== v){
      try { currentPlaying.dataset.autoPause="1"; currentPlaying.pause(); } catch(_) {}
    }
    currentPlaying = v;
  }

  // --- Core: turn a tile into inline video with controls ---
  function setupVideo(tile){
    if (tile.classList.contains('fb-inline')) return;

    const htmlRef = (tile.getAttribute('data-html') || '').trim();
    if (!htmlRef || !htmlRef.startsWith('#video')) return;

    const holder = document.querySelector(htmlRef);
    if (!holder) return;
    const v = holder.querySelector('video');
    if (!v) return;

    // Cleanup thumbnail/lightbox artifacts
    tile.removeAttribute('data-html');
    holder.remove();
    tile.querySelectorAll('img, .playbutton').forEach(n => n.remove());
    tile.classList.add('fb-inline');

    // Poster
    const poster = tile.getAttribute('data-poster') || tile.getAttribute('data-bg') || '';
    if (poster) v.setAttribute('poster', poster);

    // Video attributes: autoplay-friendly
    v.playsInline = true; v.setAttribute('playsinline',''); v.setAttribute('webkit-playsinline',''); v.setAttribute('x5-playsinline','');
    v.controls = false;
    v.muted = true;         // must start muted
    v.loop = false;
    v.autoplay = false;     // we call play() ourselves
    v.removeAttribute('preload');

    // Append inline
    tile.appendChild(v);

    // UI: Native controls visibility timer (desktop & mobile)
    let hideTimer;
    function showControls(ms=1800){
      v.controls = true;
      clearTimeout(hideTimer);
      hideTimer = setTimeout(()=>{ v.controls=false; }, ms);
    }

    // UI: Center play/pause button
    const pp = document.createElement('button');
    pp.className = 'mf-pp-btn';
    pp.type = 'button';
    pp.setAttribute('aria-label','Play/Pause');
    tile.appendChild(pp);

    function updateBtn(){
      if (v.paused) pp.classList.remove('is-playing'); else pp.classList.add('is-playing');
    }

    // Desktop interactions
    tile.addEventListener('mousemove', ()=> showControls(1200));
    tile.addEventListener('mouseleave', ()=> { v.controls=false; });

    // Click anywhere on tile toggles playback
    tile.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      if (!soundEnabled) enableAllSound();
      v.dataset.autoPause = "0";
      if (v.paused) v.play(); else v.pause();
      showControls(2000);
    }, true);

    // Button click (doesn’t bubble)
    pp.addEventListener('click', (e)=>{
      e.preventDefault(); e.stopPropagation();
      if (!soundEnabled) enableAllSound();
      v.dataset.autoPause = "0";
      if (v.paused) v.play(); else v.pause();
      showControls(2000);
    });

    // Mobile tap: briefly show controls
    tile.addEventListener('touchend', (e)=>{ e.preventDefault(); if (!soundEnabled) enableAllSound(); showControls(2500); }, {passive:false});

    // Keep UI state in sync
    v.addEventListener('play', ()=>{
      if (soundEnabled || v.dataset.wantSound === "1") v.muted = false;
      ensureSingle(v);
      updateBtn();
    });
    v.addEventListener('pause', ()=> updateBtn());
    v.addEventListener('ended', ()=> updateBtn());
    v.addEventListener('error', (e)=> LOG('Video error', e));

    // --- Autoplay when 50% in view; pause when mostly out ---
    const tryPlay = () => {
      if (!v.paused) return;
      v.play().then(()=>{
        v.dataset.autoPause = "0";
        if (soundEnabled) v.muted = false;
        updateBtn();
        LOG('✅ autoplay ok');
      }).catch(err=>{
        // retry shortly; browsers sometimes allow after minimal delay
        setTimeout(()=>{ if (v.paused) tryPlay(); }, 400);
        LOG('⚠️ autoplay blocked:', err && err.message);
      });
    };

    const io = new IntersectionObserver((entries)=>{
      entries.forEach(({isIntersecting, intersectionRatio})=>{
        if (isIntersecting && intersectionRatio >= 0.5){
          ensureSingle(v);
          tryPlay();
          if (v.readyState < 3) v.addEventListener('canplay', tryPlay, {once:true});
        } else if (intersectionRatio <= 0.15){
          try { v.dataset.autoPause = "1"; v.pause(); } catch(_) {}
        }
      });
    }, { threshold:[0.15, 0.5, 0.75], rootMargin:'50px' });

    io.observe(v);
  }

  // Init existing tiles
  function init(root=document){
    root.querySelectorAll('.i_post_image_swip_wrapper[data-html^="#video"]').forEach(setupVideo);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Watch for dynamically added posts
  new MutationObserver(muts=>{
    for (const m of muts){
      m.addedNodes.forEach(node=>{
        if (!(node instanceof HTMLElement)) return;
        if (node.matches?.('.i_post_image_swip_wrapper[data-html^="#video"]')) {
          setupVideo(node);
        } else if (node.querySelector){
          init(node);
        }
      });
    }
  }).observe(document.body, {childList:true, subtree:true});

  LOG('Ready: feed videos autoplay, click/tap to play/pause, controls on hover/tap.');
})();
</script>
