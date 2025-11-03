(function () {
  function init() {
    // ⬇️ put everything that was previously at top-level here
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();


/* Followers/subscribers @mention autocomplete
   - Works in <textarea> AND contenteditable elements
   - Binds automatically (also after AJAX loads)               */

(function () {
  const API = (window.mfBaseUrl || "/") + "requests/request.php?f=mentionFollowers&q=";
  const BOX_ID = "mf-mention-box";

  // dropdown
  const box = document.createElement("div");
  box.id = BOX_ID;
  box.style.cssText =
    "position:absolute;z-index:999999;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:6px 0;box-shadow:0 10px 25px rgba(0,0,0,.08);display:none;max-height:280px;overflow:auto;min-width:220px;font:14px/1.2 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial";
  document.body.appendChild(box);

  let active = null;        // element (textarea or contenteditable)
  let items = [];           // fetched users
  let sel   = 0;            // selected index

  const debounce = (fn, ms = 120) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };

  function hide(){ box.style.display = "none"; items = []; }
  function show(list){
    items = list || []; sel = 0;
    if(!items.length){ hide(); return; }
    box.innerHTML = items.map((u,i)=>`
      <div data-i="${i}" style="display:flex;gap:10px;align-items:center;padding:8px 12px;cursor:pointer;background:${i===sel?'#f3f4f6':'#fff'}">
        <img src="${u.avatar}" style="width:28px;height:28px;border-radius:50%;object-fit:cover">
        <div>
          <div style="font-weight:600">@${u.username}</div>
          <div style="font-size:12px;color:#6b7280">${u.name}</div>
        </div>
      </div>
    `).join("");

    const r = getCaretRect(active);
    const left = (r?.left ?? active.getBoundingClientRect().left) + window.scrollX;
    const top  = ((r?.bottom ?? active.getBoundingClientRect().bottom) + 6) + window.scrollY;

    box.style.left = left + "px";
    box.style.top  = top  + "px";
    box.style.width = Math.max(220, active.getBoundingClientRect().width) + "px";
    box.style.display = "block";
  }

  // ----- caret helpers -----
  function isCE(el){ return el && el.nodeType===1 && el.isContentEditable; }

  function getCaretInfo(el){
    /* returns { token, range } where token is text after '@' up to caret,
       and range is a DOM Range covering the token (so we can replace it). */
    if (!isCE(el)) {
      const caret = el.selectionStart || 0;
      const v = el.value;
      // find start '@'
      let s = caret - 1;
      while (s >= 0 && /\S/.test(v[s]) && v[s] !== '@') s--;
      if (v[s] !== '@') return null;
      const token = v.slice(s + 1, caret);
      return { token, startIndex: s, caret };
    } else {
      const sel = document.getSelection();
      if (!sel || sel.rangeCount === 0) return null;
      const range = sel.getRangeAt(0).cloneRange();

      // make a working range backward to find '@'
      let wr = range.cloneRange();
      wr.collapse(true);
      wr.setStart(el, 0);

      const container = document.createElement("span");
      container.appendChild(wr.cloneContents());
      const text = container.textContent || "";

      // position in plain text
      const caretPos = text.length;
      let s = caretPos - 1;
      while (s >= 0 && /\S/.test(text[s]) && text[s] !== '@') s--;
      if (text[s] !== '@') return null;
      const token = text.slice(s + 1, caretPos);

      // build a real DOM range that covers the token
      const tokenRange = document.createRange();
      // walk nodes to map plain-text offsets back to nodes
      let remainingStart = s + 1; // first char after '@'
      let remainingEnd   = caretPos;
      let startNode=null,startOffset=0,endNode=null,endOffset=0;

      (function walk(node){
        if (endNode) return;
        if (node.nodeType === 3) { // text
          const len = node.nodeValue.length;
          if (remainingStart > 0) {
            if (remainingStart <= len) { startNode = node; startOffset = remainingStart; }
            remainingStart -= len;
          } else if (!startNode) { startNode = node; startOffset = 0; }

          if (remainingEnd > 0) {
            if (remainingEnd <= len) { endNode = node; endOffset = remainingEnd; }
            remainingEnd -= len;
          } else if (!endNode) { endNode = node; endOffset = 0; }
        } else if (node.nodeType === 1) {
          for (let i=0;i<node.childNodes.length;i++) walk(node.childNodes[i]);
        }
      })(el);

      if (!startNode || !endNode) return null;
      tokenRange.setStart(startNode, startOffset);
      tokenRange.setEnd(endNode, endOffset);
      return { token, domRange: tokenRange };
    }
  }

  function getCaretRect(el){
    if (isCE(el)) {
      const sel = document.getSelection();
      if (sel && sel.rangeCount) {
        const r = sel.getRangeAt(0).cloneRange();
        if (r.getBoundingClientRect) return r.getBoundingClientRect();
      }
      return el.getBoundingClientRect();
    } else {
      // create a hidden mirror to measure caret (good enough fallback)
      const r = el.getBoundingClientRect();
      return { left: r.left, bottom: r.bottom };
    }
  }

  function insertUser(el, user){
    if (!isCE(el)) {
      const v = el.value;
      const info = getCaretInfo(el);
      if (!info) return hide();
      // info.startIndex is position of '@'
      // find end of token (non-whitespace)
      let e = info.caret;
      while (e < v.length && /\S/.test(v[e])) e++;
      const before = v.slice(0, info.startIndex);
      const after  = v.slice(e);
      const ins = '@' + user.username + ' ';
      el.value = before + ins + after;
      const np = (before + ins).length;
      el.setSelectionRange(np, np);
      el.focus();
      hide();
      return;
    }

    const info = getCaretInfo(el);
    if (!info || !info.domRange) return hide();
    const insText = document.createTextNode('@' + user.username + ' ');
    info.domRange.deleteContents();
    info.domRange.insertNode(insText);

    // move caret after inserted text
    const sel = document.getSelection();
    const afterRange = document.createRange();
    afterRange.setStart(insText, insText.nodeValue.length);
    afterRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(afterRange);
    el.focus();
    hide();
  }

  // ----- fetch -----
  function fetchUsers(q){
    if (!q) { hide(); return; }
    fetch(API + encodeURIComponent(q), { credentials: "same-origin" })
      .then(r => r.json())
      .then(arr => Array.isArray(arr) ? arr : (arr.items || []))
      .then(show)
      .catch(hide);
  }
  const request = debounce(fetchUsers, 120);

  // ----- bindings -----
  function maybeTrigger(el){
    active = el;
    const info = getCaretInfo(el);
    if (!info || !info.token) { hide(); return; }
    request(info.token);
  }

  function bind(el){
    if (el.dataset.mfMentionBound) return;
    el.dataset.mfMentionBound = "1";

    const handler = () => maybeTrigger(el);
    const keyHandler = (ev)=>{
      if (box.style.display === 'block') {
        if (ev.key === 'ArrowDown'){ sel=Math.min(sel+1, items.length-1); show(items); ev.preventDefault(); }
        else if (ev.key === 'ArrowUp'){ sel=Math.max(sel-1, 0); show(items); ev.preventDefault(); }
        else if (ev.key === 'Enter'){ ev.preventDefault(); insertUser(el, items[sel]); }
        else if (ev.key === 'Escape'){ hide(); }
      }
    };

    if (isCE(el)) {
      el.addEventListener('keyup', handler);
      el.addEventListener('input', handler);
      el.addEventListener('keydown', keyHandler);
      el.addEventListener('blur', ()=> setTimeout(hide, 150));
    } else {
      el.addEventListener('keyup', handler);
      el.addEventListener('keydown', keyHandler);
      el.addEventListener('blur', ()=> setTimeout(hide, 150));
    }
  }

  function bindAll(){
    document.querySelectorAll([
      'textarea',                    // all textareas (live chat: .lmSize)
      '[contenteditable="true"]',    // comment/post boxes that are divs
      '.comment_textarea',
      '.i_post_textarea',
      '.message_text_textarea',
    ].join(',')).forEach(bind);
  }
  bindAll();
  new MutationObserver(bindAll).observe(document.documentElement, {childList:true, subtree:true});

  // click on dropdown
  document.addEventListener("mousedown", (e)=>{
    const it = e.target.closest('#'+BOX_ID+' [data-i]');
    if (!it) return;
    e.preventDefault();
    insertUser(active, items[+it.dataset.i]);
  });
})();
