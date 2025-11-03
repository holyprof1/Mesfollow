/* Timer + viewers sync + floating hearts */
(function(){
  const hud = document.querySelector('.tthud');
  if(!hud) return;

  // elapsed timer
  const startAttr = parseInt(hud.getAttribute('data-start'), 10);
  const start = isNaN(startAttr) ? Math.floor(Date.now()/1000) : startAttr;
  const out = document.getElementById('tthudElapsed');

  function fmt(sec){
    const m = Math.floor(sec/60).toString().padStart(2,'0');
    const s = (sec%60).toString().padStart(2,'0');
    return `${m}:${s}`;
  }
  setInterval(()=>{ out && (out.textContent = fmt(Math.floor(Date.now()/1000)-start)); }, 1000);

  // mirror viewers count from handler polls (it updates .sumonline already)
  const viewersSpan = document.querySelector('.tthud-viewers-num');
  setInterval(()=>{
    const dom = document.querySelector('.sumonline');
    if(dom && viewersSpan) viewersSpan.textContent = dom.textContent;
  }, 1500);

  // floating hearts when like button clicked
  const likeBtn = document.querySelector('.tthud-like');
  const heartsBox = document.getElementById('tthudHearts');
  if (likeBtn && heartsBox) {
    likeBtn.addEventListener('click', ()=>{
      const h = document.createElement('div');
      h.innerHTML = '❤';
      Object.assign(h.style, {
        position:'absolute', bottom:'0px', right:'0px', fontSize:'20px', opacity:'1',
        transform:`translateY(0)`, transition:'transform 1.5s ease, opacity 1.5s ease'
      });
      heartsBox.appendChild(h);
      setTimeout(()=>{
        h.style.transform = `translateY(-120px)`;
        h.style.opacity = '0';
      }, 20);
      setTimeout(()=> heartsBox.removeChild(h), 1600);
    });
  }
})();
