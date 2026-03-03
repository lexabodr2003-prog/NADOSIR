<?php
// OCFilter Diagnostics page - safe to access from mobile
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OCFilter Diagnostics v14</title>
<style>
body { font-family: monospace; padding: 10px; background: #1a1a1a; color: #00ff00; font-size: 12px; }
.ok { color: #00ff00; }
.err { color: #ff4444; }
.warn { color: #ffaa00; }
.info { color: #44aaff; }
#log { border: 1px solid #333; padding: 10px; margin: 10px 0; min-height: 200px; max-height: 500px; overflow-y: auto; word-break: break-all; }
button { background: #333; color: #fff; border: 1px solid #666; padding: 10px 16px; margin: 4px; cursor: pointer; font-size: 14px; border-radius: 4px; }
</style>
</head>
<body>
<h3>OCFilter Diag v14</h3>
<div><b>UA:</b> <span id="ua"></span></div>
<div><b>Platform:</b> <span id="plat"></span></div>
<div id="log"></div>
<button onclick="runDiag()">Run Diagnostics</button>
<button onclick="testAPI()">Test API Click</button>
<button onclick="clearLog()">Clear</button>

<script>
var logEl = document.getElementById('log');
function L(cls, msg) {
  var d = document.createElement('div');
  d.className = cls;
  d.textContent = new Date().toISOString().substr(11,8) + ' ' + msg;
  logEl.appendChild(d);
  logEl.scrollTop = logEl.scrollHeight;
}
function clearLog() { logEl.innerHTML = ''; }

document.getElementById('ua').textContent = navigator.userAgent.slice(0, 120);
document.getElementById('plat').textContent = navigator.platform + ' touches=' + navigator.maxTouchPoints;

function runDiag() {
  clearLog();
  L('info', '=== DIAGNOSTICS START ===');
  
  L('info', '1. jQuery: ' + (typeof jQuery !== 'undefined' ? 'v'+jQuery.fn.jquery : 'NOT FOUND'));
  if (typeof jQuery === 'undefined') { L('err', 'CRITICAL: jQuery missing'); return; }
  
  var $ = jQuery;
  L('info', '2. $.fn.ocfilter: ' + typeof $.fn.ocfilter);
  
  var $mod = $('[id^="ocf-module-"]');
  L('info', '3. ocf-modules: ' + $mod.length + (($mod.length > 0) ? ' id='+$mod.first().attr('id') : ''));
  
  if ($mod.length) {
    var inst = $mod.first().data('ocfilter');
    L(inst ? 'ok' : 'err', '4. data("ocfilter"): ' + (inst ? 'FOUND' : 'NULL - OCFilter NOT initialized!'));
    
    if (inst) {
      L('ok', '   addParams=' + typeof inst.addParams);
      L('ok', '   removeParams=' + typeof inst.removeParams);
      L('ok', '   search=' + typeof inst.search);
      L('ok', '   getParams()=' + (typeof inst.getParams === 'function' ? '"'+inst.getParams()+'"' : 'N/A'));
      L('ok', '   options.index=' + (inst.options ? inst.options.index : '?'));
    } else {
      var dataKeys = Object.keys($mod.first().data());
      L('warn', '   all data keys: [' + dataKeys.join(', ') + ']');
      L('warn', '   _ocf_config_1: ' + (window['_ocf_config_1'] ? 'EXISTS' : 'NOT SET'));
    }
  }
  
  var btns = document.querySelectorAll('[id^="ocf-module-"] .ocf-value');
  L('info', '5. .ocf-value btns: ' + btns.length);
  if (btns.length > 0) {
    var b = btns[0];
    L('info', '   btn[0]: id='+b.id+' tag='+b.tagName+' fk='+b.getAttribute('data-filter-key')+' vid='+b.getAttribute('data-value-id'));
    L(b._ocf14 ? 'ok' : 'warn', '   v14 attached: ' + (b._ocf14 ? 'YES' : 'NO'));
  }
  
  try {
    if ($mod.length) {
      var ev = $._data($mod[0], 'events') || {};
      var evk = Object.keys(ev);
      L('info', '6. jQuery events on module: [' + evk.join(', ') + ']');
      L(ev.click ? 'ok' : 'err', '   click handlers: ' + (ev.click ? ev.click.length : 'NONE'));
    }
  } catch(e) {
    L('warn', '6. Events check failed: ' + e.message);
  }
  
  L('info', '7. $.ajaxSettings.cache=' + $.ajaxSettings.cache + ' timeout=' + $.ajaxSettings.timeout);
  L('info', '8. _ocf_config_1: ' + (window['_ocf_config_1'] ? JSON.stringify(window['_ocf_config_1']).slice(0,80) : 'NOT SET'));
  L('info', '9. ocf-mobile-fix v14 attached: ' + (typeof window._ocf14_ready !== 'undefined' ? 'YES' : 'check btns'));
  L('info', '=== END ===');
}

function testAPI() {
  L('info', '=== TEST DIRECT API ===');
  if (typeof jQuery === 'undefined') { L('err', 'jQuery missing'); return; }
  var $ = jQuery;
  
  var $mod = $('[id^="ocf-module-"]').first();
  var inst = $mod.data('ocfilter');
  if (!inst) { L('err', 'No instance - OCFilter not initialized'); return; }
  
  var $btn = $('[id^="ocf-module-"] .ocf-value[data-filter-key]').first();
  if (!$btn.length) { L('err', 'No .ocf-value buttons'); return; }
  
  var fk = $btn.attr('data-filter-key');
  var vid = $btn.attr('data-value-id');
  L('info', 'Calling addParams('+fk+', '+vid+')');
  
  try {
    inst.addParams(fk, vid);
    L('ok', 'addParams OK. getParams()="' + inst.getParams() + '"');
    
    $(document).one('ajaxComplete', function(e, xhr, settings) {
      L('ok', 'AJAX done: ' + settings.url.slice(0,70) + ' status='+xhr.status);
      try {
        var r = JSON.parse(xhr.responseText);
        L('ok', 'total=' + r.total + ' href=' + (r.href||'').slice(0,60));
      } catch(e2) {
        L('warn', 'Response not JSON: ' + xhr.responseText.slice(0,80));
      }
    });
    
    inst.search($btn, fk);
    L('ok', 'search() called - waiting for AJAX...');
  } catch(e) {
    L('err', 'ERROR: ' + e.message);
  }
}

// Override console to capture
var origLog = console.log, origErr = console.error, origWarn = console.warn;
console.log = function() { origLog.apply(console, arguments); var msg = Array.prototype.join.call(arguments, ' '); if (msg.indexOf('OCFilter') !== -1) L('info', msg.slice(0,150)); };
console.error = function() { origErr.apply(console, arguments); var msg = Array.prototype.join.call(arguments, ' '); if (msg.indexOf('OCFilter') !== -1) L('err', msg.slice(0,150)); };
console.warn = function() { origWarn.apply(console, arguments); var msg = Array.prototype.join.call(arguments, ' '); if (msg.indexOf('OCFilter') !== -1) L('warn', msg.slice(0,150)); };

setTimeout(runDiag, 3000);
</script>

<!-- Load all necessary scripts like the real page does -->
<script src="/catalog/view/javascript/jquery/jquery-3.4.1.min.js"></script>
<script src="/catalog/view/javascript/ocfilter-mobile-fix.js?v=14.0"></script>
<script>
// Simulate OCFilter module initialization
(function() {
  function tryLoad() {
    if (typeof jQuery === 'undefined') { setTimeout(tryLoad, 100); return; }
    var $ = jQuery;
    
    // Load ocfilter.js
    var s = document.createElement('script');
    s.src = '/catalog/view/javascript/ocfilter48/ocfilter.js?v=4.8.2&_=' + Math.floor(Date.now()/300000);
    s.onload = function() {
      L('ok', 'ocfilter.js loaded, $.fn.ocfilter=' + typeof $.fn.ocfilter);
    };
    s.onerror = function() { L('err', 'ocfilter.js FAILED to load'); };
    document.head.appendChild(s);
  }
  tryLoad();
})();
</script>
</body>
</html>
