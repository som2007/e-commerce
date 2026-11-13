<?php /* Full-screen blocking loader. Use AppLoader.show('message') / AppLoader.hide(). */ ?>
<style>
#appLoader{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;flex-direction:column;gap:18px;background:rgba(30,6,14,.9);backdrop-filter:blur(3px);color:#fff;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;text-align:center;padding:24px}
#appLoader.on{display:flex}
#appLoader .al-ring{width:62px;height:62px;border-radius:50%;border:5px solid rgba(255,255,255,.18);border-top-color:#d6a84f;border-right-color:#f0d594;animation:alspin .8s linear infinite}
#appLoader .al-msg{font-size:1.05rem;font-weight:600;letter-spacing:.01em}
#appLoader .al-sub{font-size:.85rem;opacity:.7;margin-top:-10px}
@keyframes alspin{to{transform:rotate(360deg)}}
</style>
<div id="appLoader" role="alertdialog" aria-live="assertive" aria-busy="true" aria-label="Loading">
    <div class="al-ring"></div>
    <div class="al-msg" id="appLoaderMsg">Please wait...</div>
    <div class="al-sub">Please do not close or refresh this page</div>
</div>
<script nonce="<?= csp_nonce() ?>">
(function () {
    var el = document.getElementById('appLoader'), msg = document.getElementById('appLoaderMsg'), depth = 0, guard;
    window.AppLoader = {
        show: function (text) {
            depth++; msg.textContent = text || 'Please wait...'; el.className = 'on';
            document.documentElement.style.overflow = 'hidden';
            clearTimeout(guard); guard = setTimeout(function () { AppLoader.reset(); }, 45000); // never get stuck
        },
        hide: function () { depth = Math.max(0, depth - 1); if (!depth) AppLoader.reset(); },
        reset: function () { depth = 0; el.className = ''; document.documentElement.style.overflow = ''; clearTimeout(guard); }
    };
    // bfcache / back button should never show a stuck loader
    window.addEventListener('pageshow', function () { AppLoader.reset(); });

    // jQuery based pages (login / register / OTP screens)
    function hook() {
        if (!window.jQuery || window.__alHooked) return;
        window.__alHooked = true;
        var label = function (u) {
            u = String(u || '').toLowerCase();
            if (/resend|forgot|send/.test(u)) return 'Sending OTP to your email...';
            if (/verify|otp/.test(u)) return 'Verifying...';
            if (/login/.test(u)) return 'Signing you in...';
            if (/register/.test(u)) return 'Creating your account...';
            if (/reset/.test(u)) return 'Updating your password...';
            return 'Please wait...';
        };
        jQuery(document).ajaxSend(function (e, x, o) { AppLoader.show(label(o.url)); });
        jQuery(document).ajaxComplete(function () { AppLoader.hide(); });
    }
    hook(); window.addEventListener('load', hook);
})();
</script>
