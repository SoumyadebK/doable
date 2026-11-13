/*
 * DOable Concierge — Location iframe Loader Script
 * Usage: <script src="https://doable.net/chatbot/chatbot-location-loader.js" data-account="ACCOUNT_ID" data-location="LOCATION_ID"></script>
 */
(function() {
  'use strict';

  // ── Get account + location from this script tag ─────
  const CURRENT_SCRIPT = document.currentScript || (function() {
    const scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();
  const ACCOUNT_ID  = CURRENT_SCRIPT.getAttribute('data-account')  || '';
  const LOCATION_ID = CURRENT_SCRIPT.getAttribute('data-location') || '';

  if (!ACCOUNT_ID) {
    console.error('DOable Concierge: data-account attribute is required on the embed script tag.');
    return;
  }
  if (!LOCATION_ID) {
    console.error('DOable Concierge: data-location attribute is required on the embed script tag.');
    return;
  }

  // ── Base URL ─────────────────────────────────────────
  const FRAME_BASE_URL = 'https://doable.net/chatbot/chatbot-location-frame.html';

  // ── Create the iframe ────────────────────────────────
  const iframe = document.createElement('iframe');
  iframe.id    = 'doable-concierge-iframe';
  iframe.src   = FRAME_BASE_URL + '?account=' + encodeURIComponent(ACCOUNT_ID) + '&location=' + encodeURIComponent(LOCATION_ID);
  iframe.title = 'DOable Concierge Chat';
  iframe.setAttribute('scrolling', 'no');
  iframe.style.cssText = [
    'position: fixed',
    'bottom: 0',
    'right: 20px',
    'width: 90px',
    'height: 90px',
    'border: none',
    'z-index: 999999',
    'background: transparent',
    'transition: width 0.25s ease, height 0.25s ease',
    'colorScheme: light'
  ].join(';');

  document.body.appendChild(iframe);

  // ── Listen for resize messages from the iframe ───────
  const OPEN_WIDTH    = '420px';
  const OPEN_HEIGHT   = '640px';
  const CLOSED_WIDTH  = '90px';
  const CLOSED_HEIGHT = '90px';

  window.addEventListener('message', function(event) {
    if (!event.data || event.data.type !== 'doable-toggle') return;
    const isMobile = window.innerWidth < 480;
    if (event.data.open) {
      iframe.style.width  = isMobile ? '100vw' : OPEN_WIDTH;
      iframe.style.height = isMobile ? '100vh' : OPEN_HEIGHT;
      iframe.style.right  = isMobile ? '0' : '20px';
      iframe.style.bottom = '0';
    } else {
      iframe.style.width  = CLOSED_WIDTH;
      iframe.style.height = CLOSED_HEIGHT;
      iframe.style.right  = isMobile ? '0' : '20px';
    }
  });

})();
