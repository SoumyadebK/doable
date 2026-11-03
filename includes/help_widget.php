<?php
/**
 * DOable Help Widget — embeddable partial (simple / browse-only version)
 * -----------------------------------------------------------------------
 * Include this file once from your shared layout (e.g. includes/footer.php)
 * and it will render a floating help bubble on every page that includes it.
 *
 *   <?php include_once(__DIR__ . '/help_widget.php'); ?>
 *
 * This is the simplified version: menu-driven topic browsing only, no
 * free-text search and no AI. Purely static — reads doable_kb.json client-side,
 * no server logic, no API key, nothing to secure beyond the JSON file itself.
 *
 * All CSS classes/IDs are prefixed with "dhb-" (Doable Help Bot) and all JS
 * is wrapped in an IIFE, so this is safe to drop into pages that already use
 * Bootstrap, jQuery, or any other framework without name collisions.
 *
 * CONFIG: set the path doable_kb.json will be served from. This must be a
 * URL path reachable by the browser (i.e. under your public web root), not
 * a filesystem include path.
 */
$DHB_KB_JSON_URL = '/assets/help/doable_kb.json';
?>
<style>
  #dhb-widget{ --dhb-brand:#2f6fed; --dhb-brand-dark:#1f4fc4; --dhb-panel:#ffffff;
    --dhb-text:#1c2430; --dhb-muted:#6b7686; --dhb-border:#e2e6ee; --dhb-accent-bg:#eef3ff; }
  #dhb-widget, #dhb-widget *{ box-sizing:border-box; }
  #dhb-widget{ font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }

  #dhb-widget .dhb-bubble{
    position:fixed;bottom:24px;right:24px;width:58px;height:58px;border-radius:50%;
    background:var(--dhb-brand);color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:26px;cursor:pointer;box-shadow:0 6px 18px rgba(47,111,237,.4);z-index:2000;border:none;
  }
  #dhb-widget .dhb-bubble:hover{ background:var(--dhb-brand-dark); }

  #dhb-widget .dhb-panel{
    position:fixed;bottom:96px;right:24px;width:380px;max-width:92vw;height:600px;max-height:80vh;
    background:var(--dhb-panel);border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.18);
    display:none;flex-direction:column;overflow:hidden;z-index:2000;border:1px solid var(--dhb-border);
    color:var(--dhb-text);font-size:13px;line-height:1.5;
  }
  #dhb-widget .dhb-panel.dhb-open{ display:flex; }
  #dhb-widget .dhb-panel header{ background:var(--dhb-brand);color:#fff;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;flex:0 0 auto; }
  #dhb-widget .dhb-title{ font-weight:600;font-size:15px; }
  #dhb-widget .dhb-sub{ font-size:11px;opacity:.85; }
  #dhb-widget .dhb-close-btn{ background:none;border:none;color:#fff;font-size:18px;cursor:pointer;opacity:.85; }
  #dhb-widget .dhb-close-btn:hover{ opacity:1; }

  #dhb-widget .dhb-error-box{ margin:14px;border:1px solid #f3b3b3;background:#fdecec;color:#7a1f1f;border-radius:10px;padding:10px 12px;font-size:12.5px; }

  #dhb-widget .dhb-tree{ flex:1;overflow-y:auto;padding:10px 8px; }
  #dhb-widget .dhb-sec{ margin-bottom:4px; }
  #dhb-widget .dhb-sec-title{ padding:8px 10px;font-size:12.5px;font-weight:600;color:var(--dhb-text);cursor:pointer;border-radius:6px;display:flex;justify-content:space-between; }
  #dhb-widget .dhb-sec-title:hover{ background:var(--dhb-accent-bg); }
  #dhb-widget .dhb-arrow{ transition:transform .15s;color:var(--dhb-muted); }
  #dhb-widget .dhb-sec.dhb-open .dhb-arrow{ transform:rotate(90deg); }
  #dhb-widget .dhb-art-list{ display:none;padding-left:12px; }
  #dhb-widget .dhb-sec.dhb-open .dhb-art-list{ display:block; }
  #dhb-widget .dhb-art-item{ padding:6px 10px;font-size:12.5px;color:var(--dhb-muted);cursor:pointer;border-radius:6px; }
  #dhb-widget .dhb-art-item:hover{ background:var(--dhb-accent-bg);color:var(--dhb-text); }

  #dhb-widget .dhb-article-view{ display:none;flex:1;overflow-y:auto;padding:14px; }
  #dhb-widget .dhb-article-view.dhb-open{ display:block; }
  #dhb-widget .dhb-back{ font-size:12px;color:var(--dhb-brand);cursor:pointer;margin-bottom:10px;display:inline-block; }
  #dhb-widget .dhb-article-view .dhb-tag{ display:inline-block;background:var(--dhb-brand);color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;margin-bottom:6px; }
  #dhb-widget .dhb-article-view h4{ margin:0 0 6px;font-size:14px; }
  #dhb-widget .dhb-field{ margin-top:6px; }
  #dhb-widget .dhb-article-view ul{ margin:4px 0 4px 18px;padding:0; }
  #dhb-widget .dhb-nested{ margin-left:10px;border-left:2px solid var(--dhb-border);padding-left:8px;margin-top:4px; }
</style>

<div id="dhb-widget">
  <button class="dhb-bubble" id="dhb-bubble" title="Help">💬</button>

  <div class="dhb-panel" id="dhb-panel">
    <header>
      <div>
        <div class="dhb-title">DOable Help</div>
        <div class="dhb-sub">Browse help topics</div>
      </div>
      <button class="dhb-close-btn" id="dhb-close-btn">✕</button>
    </header>

    <div class="dhb-tree" id="dhb-tree"></div>
    <div class="dhb-article-view" id="dhb-article-view">
      <span class="dhb-back" id="dhb-back-btn">← Back to topics</span>
      <div id="dhb-article-content"></div>
    </div>
  </div>
</div>

<script>
(function(){
  var DHB_KB_URL = <?php echo json_encode($DHB_KB_JSON_URL); ?>;
  var KB = null;

  function $id(id){ return document.getElementById(id); }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function labelize(key){
    return key.replace(/_/g,' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); });
  }

  function renderField(key, value){
    if (value === null || value === undefined) return '';
    if (typeof value === 'string'){
      return '<div class="dhb-field"><strong>' + labelize(key) + ':</strong> ' + escapeHtml(value) + '</div>';
    }
    if (Array.isArray(value)){
      if (value.length === 0) return '';
      if (typeof value[0] === 'string'){
        return '<div class="dhb-field"><strong>' + labelize(key) + ':</strong><ul>' +
          value.map(function(v){ return '<li>' + escapeHtml(v) + '</li>'; }).join('') + '</ul></div>';
      }
      if (typeof value[0] === 'object'){
        var items = value.map(function(obj){
          var head = obj.name || obj.title || obj.action || obj.step || '';
          var rest = Object.entries(obj).filter(function(e){ return ['name','title','action','step'].indexOf(e[0]) === -1; });
          var restHtml = rest.map(function(e){ return renderField(e[0], e[1]); }).join('');
          return '<li>' + (head ? '<strong>' + escapeHtml(String(head)) + '</strong>' : '') +
            (restHtml ? '<div class="dhb-nested">' + restHtml + '</div>' : '') + '</li>';
        }).join('');
        return '<div class="dhb-field"><strong>' + labelize(key) + ':</strong><ul>' + items + '</ul></div>';
      }
    }
    if (typeof value === 'object'){
      var inner = Object.entries(value).map(function(e){ return renderField(e[0], e[1]); }).join('');
      return '<div class="dhb-field"><strong>' + labelize(key) + ':</strong><div class="dhb-nested">' + inner + '</div></div>';
    }
    return '';
  }

  function renderArticle(article, sectionTitle){
    var html = '<span class="dhb-tag">' + escapeHtml(sectionTitle) + '</span><h4>' + escapeHtml(article.title) + '</h4>';
    Object.entries(article).forEach(function(e){
      if (['id','slug','title'].indexOf(e[0]) === -1) html += renderField(e[0], e[1]);
    });
    return html;
  }

  function buildTree(){
    var tree = $id('dhb-tree');
    tree.innerHTML = KB.sections.map(function(sec){
      return '<div class="dhb-sec" data-dhb-sec="' + sec.id + '">' +
        '<div class="dhb-sec-title" data-dhb-toggle="' + sec.id + '">' +
          '<span>' + escapeHtml(sec.title) + '</span><span class="dhb-arrow">›</span>' +
        '</div>' +
        '<div class="dhb-art-list">' +
          (sec.articles || []).map(function(a){
            return '<div class="dhb-art-item" data-dhb-sec-id="' + sec.id + '" data-dhb-art-id="' + a.id + '">' + escapeHtml(a.title) + '</div>';
          }).join('') +
        '</div></div>';
    }).join('');

    tree.querySelectorAll('[data-dhb-toggle]').forEach(function(el){
      el.addEventListener('click', function(){
        el.closest('.dhb-sec').classList.toggle('dhb-open');
      });
    });
    tree.querySelectorAll('[data-dhb-art-id]').forEach(function(el){
      el.addEventListener('click', function(){
        openArticle(el.getAttribute('data-dhb-sec-id'), el.getAttribute('data-dhb-art-id'));
      });
    });
  }

  function openArticle(secId, artId){
    var sec = KB.sections.find(function(s){ return s.id === secId; });
    var art = sec.articles.find(function(a){ return a.id === artId; });
    $id('dhb-article-content').innerHTML = renderArticle(art, sec.title);
    $id('dhb-tree').style.display = 'none';
    $id('dhb-article-view').classList.add('dhb-open');
  }

  function init(){
    $id('dhb-bubble').addEventListener('click', function(){ $id('dhb-panel').classList.add('dhb-open'); });
    $id('dhb-close-btn').addEventListener('click', function(){ $id('dhb-panel').classList.remove('dhb-open'); });

    $id('dhb-back-btn').addEventListener('click', function(){
      $id('dhb-tree').style.display = 'block';
      $id('dhb-article-view').classList.remove('dhb-open');
    });

    fetch(DHB_KB_URL)
      .then(function(r){ if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function(data){ KB = data; buildTree(); })
      .catch(function(err){
        $id('dhb-tree').innerHTML = '<div class="dhb-error-box"><b>Could not load help content</b><br>' +
          escapeHtml(err.message) + '<br><br>Check that DHB_KB_JSON_URL in help_widget.php points to where doable_kb.json is actually served from.</div>';
      });
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
