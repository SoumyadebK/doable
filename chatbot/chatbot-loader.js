/*
 * DOable Concierge — iframe Loader Script
 * Usage: <script src="https://doable.net/chatbot/chatbot-loader.js" data-account="ACCOUNT_ID"></script>
 */
(function() {
  'use strict';

  // ── Get account ID from this script tag ─────────────
  const CURRENT_SCRIPT = document.currentScript || (function() {
    const scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();
  const ACCOUNT_ID = CURRENT_SCRIPT.getAttribute('data-account') || '';

  if (!ACCOUNT_ID) {
    console.error('DOable Concierge: data-account attribute is required on the embed script tag.');
    return;
  }

  // ── Base URL — adjust if hosted elsewhere ───────────
  const FRAME_BASE_URL = 'https://doable.net/chatbot/chatbot-frame.html';

  // ── Create the iframe ────────────────────────────────
  const iframe = document.createElement('iframe');
  iframe.id = 'doable-concierge-iframe';
  iframe.src = FRAME_BASE_URL + '?account=' + encodeURIComponent(ACCOUNT_ID);
  iframe.title = 'DOable Concierge Chat';
  iframe.setAttribute('scrolling', 'no');
  iframe.style.cssText = [
    'position: fixed',
    'bottom: 0',
    'right: 0',
    'width: 90px',
    'height: 90px',
    'border: none',
    'z-index: 999999',
    'background: transparent',
    'transition: width 0.25s ease, height 0.25s ease',
    'colorScheme: light'
  ].join(';');

  document.body.appendChild(iframe);

  // ── Listen for resize messages from the iframe ──────
  // When the chat opens, expand the iframe to fit the full chat window.
  // When closed, shrink back to just the launcher bubble size.
  const OPEN_WIDTH  = '420px';
  const OPEN_HEIGHT = '640px';
  const CLOSED_WIDTH  = '90px';
  const CLOSED_HEIGHT = '90px';

  window.addEventListener('message', function(event) {
    if (!event.data || event.data.type !== 'doable-toggle') return;
    // Optional: verify event.origin === 'https://doable.net' for extra security
    if (event.data.open) {
      iframe.style.width  = OPEN_WIDTH;
      iframe.style.height = OPEN_HEIGHT;
    } else {
      iframe.style.width  = CLOSED_WIDTH;
      iframe.style.height = CLOSED_HEIGHT;
    }
  });

  // ── Responsive sizing for mobile ────────────────────
  function applyResponsiveSize() {
    const isMobile = window.innerWidth < 480;
    if (isMobile) {
      iframe.dataset.mobileOpenWidth = '100vw';
      iframe.dataset.mobileOpenHeight = '100vh';
    }
  }
  applyResponsiveSize();
  window.addEventListener('resize', applyResponsiveSize);

  // Adjust message listener to use mobile sizes if applicable
  window.addEventListener('message', function(event) {
    if (!event.data || event.data.type !== 'doable-toggle') return;
    const isMobile = window.innerWidth < 480;
    if (event.data.open && isMobile) {
      iframe.style.width  = '100vw';
      iframe.style.height = '100vh';
      iframe.style.bottom = '0';
      iframe.style.right  = '0';
    } else if (!event.data.open) {
      iframe.style.width  = CLOSED_WIDTH;
      iframe.style.height = CLOSED_HEIGHT;
    }
  });

})();
