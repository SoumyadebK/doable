(function () {
  'use strict';

  // ── Config (injected by the script tag data attributes) ───────────────────
  var script      = document.currentScript;
  var accountId   = script.getAttribute('data-account')  || '';
  var locationId  = script.getAttribute('data-location') || '';
  var frameBase   = script.getAttribute('data-frame-url') || 'https://doable.net/chatbot/chatbot-location-frame.html';
  var brandColor  = script.getAttribute('data-color')    || '#c8102e';

  // ── Prevent double-init ───────────────────────────────────────────────────
  if (document.getElementById('doable-launcher-btn')) return;

  // ── Inject styles ─────────────────────────────────────────────────────────
  var style = document.createElement('style');
  style.textContent = [
    '#doable-launcher-btn {',
    '  position: fixed; bottom: 28px; right: 28px; z-index: 2147483646;',
    '  width: 60px; height: 60px; border-radius: 50%;',
    '  background: ' + brandColor + ';',
    '  box-shadow: 0 4px 18px rgba(0,0,0,0.25);',
    '  border: none; cursor: pointer;',
    '  display: flex; align-items: center; justify-content: center;',
    '  transition: transform 0.2s;',
    '}',
    '#doable-launcher-btn:hover { transform: scale(1.08); }',
    '#doable-launcher-btn svg { width: 28px; height: 28px; fill: #fff; }',

    '#doable-iframe-wrap {',
    '  position: fixed; bottom: 100px; right: 28px; z-index: 2147483645;',
    '  width: 380px; height: 600px; max-height: calc(100vh - 120px);',
    '  border-radius: 20px;',
    '  box-shadow: 0 12px 48px rgba(0,0,0,0.15);',
    '  overflow: hidden;',
    '  transform: scale(0.92) translateY(16px);',
    '  opacity: 0; pointer-events: none;',
    '  transition: transform 0.22s cubic-bezier(.34,1.56,.64,1), opacity 0.18s ease;',
    '}',
    '#doable-iframe-wrap.open {',
    '  transform: scale(1) translateY(0);',
    '  opacity: 1; pointer-events: all;',
    '}',
    '#doable-iframe-wrap iframe {',
    '  width: 100%; height: 100%; border: none; display: block; border-radius: 20px;',
    '}',

    '@media (max-width: 767px) {',
    '  #doable-iframe-wrap {',
    '    right: 0; bottom: 0; left: 0;',
    '    width: 100%; height: 85vh; max-height: 85vh;',
    '    border-radius: 20px 20px 0 0;',
    '    transform: translateY(20px) scale(0.98);',
    '  }',
    '  #doable-launcher-btn { bottom: 20px; right: 20px; }',
    '}',
  ].join('\n');
  document.head.appendChild(style);

  // ── Launcher button ───────────────────────────────────────────────────────
  var btn = document.createElement('button');
  btn.id = 'doable-launcher-btn';
  btn.setAttribute('aria-label', 'Open chat');
  btn.innerHTML =
    '<svg id="doable-icon-chat" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>' +
    '<svg id="doable-icon-close" viewBox="0 0 24 24" style="display:none"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
  document.body.appendChild(btn);

  // ── iframe wrapper (created lazily on first open) ─────────────────────────
  var wrap   = null;
  var iframe = null;

  function buildIframe() {
    wrap = document.createElement('div');
    wrap.id = 'doable-iframe-wrap';

    iframe = document.createElement('iframe');
    var src = frameBase +
      '?account='  + encodeURIComponent(accountId) +
      '&location=' + encodeURIComponent(locationId);
    iframe.src   = src;
    iframe.title = 'DOable Concierge';
    iframe.setAttribute('allow', 'autoplay');

    wrap.appendChild(iframe);
    document.body.appendChild(wrap);
  }

  // ── Toggle ────────────────────────────────────────────────────────────────
  var isOpen   = false;
  var iframeReady = false;

  btn.addEventListener('click', function () {
    if (!wrap) buildIframe();
    isOpen = !isOpen;
    wrap.classList.toggle('open', isOpen);
    document.getElementById('doable-icon-chat').style.display  = isOpen ? 'none'  : 'block';
    document.getElementById('doable-icon-close').style.display = isOpen ? 'block' : 'none';
    // Store session open state so page navigations don't re-trigger auto-open
    try { sessionStorage.setItem('doable_opened', '1'); } catch(e) {}
  });

  // ── postMessage: resize iframe height if content grows ───────────────────
  window.addEventListener('message', function (e) {
    if (!e.data || e.data.type !== 'doable-resize') return;
    if (wrap) {
      var maxH = Math.min(e.data.height, window.innerHeight - 120);
      wrap.style.height = maxH + 'px';
    }
  });

  // ── Auto-open: 4s desktop / 10s mobile, once per session ─────────────────
  try {
    if (!sessionStorage.getItem('doable_opened')) {
      var isMobile = window.innerWidth < 768;
      setTimeout(function () {
        if (!isOpen) {
          if (!wrap) buildIframe();
          sessionStorage.setItem('doable_opened', '1');
          isOpen = true;
          wrap.classList.add('open');
          document.getElementById('doable-icon-chat').style.display  = 'none';
          document.getElementById('doable-icon-close').style.display = 'block';
        }
      }, isMobile ? 10000 : 4000);
    }
  } catch(e) {}

})();
