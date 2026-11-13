/* Storefront behaviour (vanilla JS, no dependencies) */
(function () {
    'use strict';

    var $ = function (s, c) { return (c || document).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
    var csrf = function () { var m = $('meta[name="csrf-token"]'); return m ? m.content : ''; };
    var base = (window.APP && window.APP.base) || '';

    function api(path, opts) {
        opts = opts || {};
        var headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf()};
        if (opts.body) headers['Content-Type'] = 'application/json';
        return fetch(base + path, {
            method: opts.method || (opts.body ? 'POST' : 'GET'),
            headers: headers,
            credentials: 'same-origin',
            body: opts.body ? JSON.stringify(opts.body) : undefined
        }).then(function (r) { return r.json().then(function (d) { d._status = r.status; return d; }); });
    }

    var toastTimer;
    function toast(msg, isError) {
        var t = $('#toast');
        if (!t) return;
        t.textContent = msg;
        t.className = 'toast show' + (isError ? ' error' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { t.className = 'toast'; }, 2800);
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    /* ---------- drawers / overlays ---------- */
    var backdrop = $('.drawer-backdrop');
    function closeAll() {
        $$('.mobile-menu, .cart-drawer').forEach(function (d) { d.classList.remove('open'); d.setAttribute('aria-hidden', 'true'); });
        if (backdrop) backdrop.hidden = true;
        var so = $('#searchOverlay'); if (so) so.hidden = true;
        document.body.style.overflow = '';
    }
    function openDrawer(el) {
        closeAll();
        el.classList.add('open'); el.setAttribute('aria-hidden', 'false');
        if (backdrop) backdrop.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-open-menu],[data-open-cart],[data-open-search],[data-close-all],[data-logout]');
        if (!t) return;
        if (t.hasAttribute('data-open-menu')) openDrawer($('#mobileMenu'));
        else if (t.hasAttribute('data-open-cart')) { openDrawer($('#cartDrawer')); loadCart(); }
        else if (t.hasAttribute('data-open-search')) {
            closeAll(); $('#searchOverlay').hidden = false; document.body.style.overflow = 'hidden';
            setTimeout(function () { $('#searchInput').focus(); }, 30);
        }
        else if (t.hasAttribute('data-close-all')) { if (!t.closest('form') || t.type === 'button') closeAll(); }
        else if (t.hasAttribute('data-logout')) {
            e.preventDefault();
            api('/api/v1/auth/logout', {method: 'POST', body: {}}).then(function () { window.location = base + '/'; });
        }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeAll(); $$('.modal').forEach(function (m) { m.hidden = true; }); } });
    var so = $('#searchOverlay');
    if (so) so.addEventListener('click', function (e) { if (e.target === so) closeAll(); });

    /* ---------- modals ---------- */
    document.addEventListener('click', function (e) {
        var o = e.target.closest('[data-open-modal]');
        if (o) { var m = document.getElementById(o.getAttribute('data-open-modal')); if (m) m.hidden = false; return; }
        if (e.target.closest('[data-close-modal]') || (e.target.classList && e.target.classList.contains('modal'))) {
            var mm = e.target.closest('.modal'); if (mm) mm.hidden = true;
        }
    });

    /* ---------- live search ---------- */
    var sInput = $('#searchInput'), sRes = $('#searchResults'), sTimer, sSeq = 0;
    if (sInput) sInput.addEventListener('input', function () {
        clearTimeout(sTimer);
        var q = sInput.value.trim();
        if (q.length < 2) { sRes.innerHTML = ''; return; }
        sTimer = setTimeout(function () {
            var seq = ++sSeq;
            api('/api/v1/search/suggest?q=' + encodeURIComponent(q)).then(function (d) {
                if (seq !== sSeq) return;
                if (!d.items || !d.items.length) { sRes.innerHTML = '<p class="muted pad">No matches. Press Enter to search everything.</p>'; return; }
                sRes.innerHTML = d.items.map(function (i) {
                    return '<a class="sr-item" href="' + esc(i.url) + '"><img src="' + esc(i.image) + '" alt=""><span>' + esc(i.name) + '</span><strong>' + esc(i.price) + '</strong></a>';
                }).join('') + '<a class="link-arrow pad" style="display:inline-block" href="' + base + '/search?q=' + encodeURIComponent(q) + '">See all results &rarr;</a>';
            });
        }, 220);
    });

    /* ---------- cart ---------- */
    function setCount(n) {
        $$('[data-cart-count]').forEach(function (b) { b.textContent = n; b.hidden = !n; });
    }
    function renderCart(d) {
        setCount(d.count);
        var body = $('#cartBody'), foot = $('#cartFoot');
        if (!body) return;
        if (!d.lines.length) {
            body.innerHTML = '<div class="empty" style="border:0"><h3>Your cart is empty</h3><a class="btn btn-dark" href="' + base + '/collections/all">Continue shopping</a></div>';
            foot.hidden = true; return;
        }
        body.innerHTML = d.lines.map(function (l) {
            return '<div class="cart-line" data-v="' + l.variant_id + '">' +
                '<a href="' + esc(l.url) + '"><img src="' + esc(l.image) + '" alt=""></a>' +
                '<div><a class="name" href="' + esc(l.url) + '">' + esc(l.name) + '</a>' +
                '<p class="muted small" style="margin:2px 0">Size ' + esc(l.size) + (l.color ? ' &middot; ' + esc(l.color) : '') + '</p>' +
                '<div class="qty" style="transform:scale(.85);transform-origin:left"><button type="button" data-cq="-1" aria-label="Decrease">&minus;</button>' +
                '<input type="number" value="' + l.qty + '" min="1" max="' + l.max + '" data-cqi aria-label="Quantity"><button type="button" data-cq="1" aria-label="Increase">+</button></div></div>' +
                '<div style="text-align:right"><strong>' + esc(l.line_total) + '</strong><br><button class="link small" type="button" data-cr>Remove</button></div></div>';
        }).join('');
        foot.hidden = false;
        $('#cartSubtotal').textContent = d.subtotal;
        var fs = $('#freeShip');
        if (fs) {
            fs.innerHTML = d.free_ship_remaining > 0
                ? 'Add <strong>Rs. ' + Math.ceil(d.free_ship_remaining) + '</strong> more for <strong>free shipping</strong><div class="bar"><i style="width:' + Math.min(100, 100 - d.free_ship_remaining / d.free_shipping_over * 100) + '%"></i></div>'
                : '<strong style="color:#1d7a46">You have unlocked free shipping!</strong>';
        }
    }
    function loadCart() { return api('/api/v1/cart').then(renderCart); }
    var cartBody = $('#cartBody');
    if (cartBody) cartBody.addEventListener('click', function (e) {
        var line = e.target.closest('.cart-line'); if (!line) return;
        var id = +line.getAttribute('data-v'), inp = $('[data-cqi]', line);
        if (e.target.closest('[data-cr]')) return api('/api/v1/cart/remove', {body: {variant_id: id}}).then(renderCart);
        var b = e.target.closest('[data-cq]');
        if (b) api('/api/v1/cart/update', {body: {variant_id: id, qty: (+inp.value) + (+b.getAttribute('data-cq'))}}).then(renderCart);
    });
    if (cartBody) cartBody.addEventListener('change', function (e) {
        if (!e.target.matches('[data-cqi]')) return;
        var id = +e.target.closest('.cart-line').getAttribute('data-v');
        api('/api/v1/cart/update', {body: {variant_id: id, qty: +e.target.value}}).then(renderCart);
    });

    /* full cart page */
    var cartPage = $('#cartPage');
    if (cartPage) {
        cartPage.addEventListener('click', function (e) {
            var line = e.target.closest('.line'); if (!line) return;
            var id = +line.getAttribute('data-line'), inp = $('input', line);
            var done = function () { window.location.reload(); };
            if (e.target.closest('[data-remove-line]')) return api('/api/v1/cart/remove', {body: {variant_id: id}}).then(done);
            var inc = e.target.closest('[data-inc]'), dec = e.target.closest('[data-dec]');
            if (inc || dec) api('/api/v1/cart/update', {body: {variant_id: id, qty: (+inp.value) + (inc ? 1 : -1)}}).then(done);
        });
        cartPage.addEventListener('change', function (e) {
            if (e.target.matches('.qty input')) {
                var id = +e.target.closest('.line').getAttribute('data-line');
                api('/api/v1/cart/update', {body: {variant_id: id, qty: +e.target.value}}).then(function () { window.location.reload(); });
            }
        });
    }

    /* ---------- product page ---------- */
    var P = window.PRODUCT;
    if (P) {
        var selected = null;
        var sizesEl = $('#sizes'), hint = $('#sizeHint');
        var byId = {}; P.variants.forEach(function (v) { byId[v.id] = v; });

        function showPrice(v) {
            if (!v) return;
            var now = v.saleText || v.priceText;
            $('#priceNow').textContent = now;
            var was = $('#priceWas'), off = $('#priceOff');
            if (was) { was.textContent = v.priceText; }
            if (off && v.sale) off.textContent = 'Save ' + Math.round((v.price - v.sale) / v.price * 100) + '%';
            if (hint) hint.textContent = v.stock <= 5 ? 'Only ' + v.stock + ' left in size ' + v.size : 'Size ' + v.size + ' selected';
        }
        if (sizesEl) sizesEl.addEventListener('click', function (e) {
            var b = e.target.closest('.size'); if (!b || b.disabled) return;
            $$('.size', sizesEl).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
            b.setAttribute('aria-pressed', 'true');
            selected = byId[b.getAttribute('data-variant')];
            showPrice(selected);
            var q = $('#qtyInput'); q.max = Math.min(10, selected.stock); if (+q.value > +q.max) q.value = q.max;
        });

        function addToCart(then) {
            if (!selected) { toast('Please select a size', true); if (hint) hint.style.color = '#b3202a'; sizesEl.scrollIntoView({block: 'center', behavior: 'smooth'}); return; }
            var qty = Math.max(1, +$('#qtyInput').value || 1);
            api('/api/v1/cart/add', {body: {variant_id: selected.id, qty: qty}}).then(function (d) {
                if (!d.success) return toast(d.message || 'Could not add to cart', true);
                renderCart(d);
                then(d);
            });
        }
        var addBtn = $('#addBtn'), buyBtn = $('#buyBtn');
        if (addBtn) addBtn.addEventListener('click', function () { addToCart(function () { toast('Added to cart'); openDrawer($('#cartDrawer')); }); });
        if (buyBtn) buyBtn.addEventListener('click', function () { addToCart(function () { window.location = base + '/checkout'; }); });

        $$('[data-gallery] .thumb').forEach(function (t) {
            t.addEventListener('click', function () {
                $('#mainImage').src = t.getAttribute('data-src');
                $$('[data-gallery] .thumb').forEach(function (x) { x.classList.remove('active'); });
                t.classList.add('active');
            });
        });
    }

    /* qty +/- on product page (single stepper) */
    $$('[data-qty]').forEach(function (q) {
        var i = $('input', q);
        $('[data-dec]', q).addEventListener('click', function () { i.value = Math.max(1, (+i.value || 1) - 1); });
        $('[data-inc]', q).addEventListener('click', function () { i.value = Math.min(+i.max || 10, (+i.value || 1) + 1); });
    });

    /* tabs */
    $$('[data-tabs]').forEach(function (w) {
        $$('[data-tab]', w).forEach(function (b) {
            b.addEventListener('click', function () {
                $$('[data-tab]', w).forEach(function (x) { x.classList.remove('active'); });
                $$('.tab-panel', w).forEach(function (x) { x.classList.remove('active'); });
                b.classList.add('active'); document.getElementById(b.getAttribute('data-tab')).classList.add('active');
            });
        });
    });

    /* ---------- checkout ---------- */
    var cf = $('#checkoutForm');
    if (cf) {
        var method = function () { return ($('input[name="payment_method"]:checked', cf) || {}).value || 'online'; };
        var cInput = $('#couponInput'), cMsg = $('#couponMsg');
        var quote = function (showMsg) {
            return api('/api/v1/checkout/quote', {body: {payment_method: method(), coupon: cInput.value}}).then(function (d) {
                var r = d.rows;
                $('#sumSubtotal').textContent = r.subtotal;
                $('#sumShipping').textContent = r.shipping;
                $('#sumTotal').textContent = r.total;
                var show = function (row, el, val) { $(row).hidden = !val; if (val) $(el).textContent = val; };
                show('#rowPrepaid', '#sumPrepaid', r.prepaid);
                show('#rowCoupon', '#sumCoupon', r.coupon);
                show('#rowCod', '#sumCod', r.cod_fee);
                if (cInput.value.trim()) {
                    cMsg.className = 'small ' + (d.coupon_error ? 'err' : 'ok');
                    cMsg.textContent = d.coupon_error || ('Coupon ' + d.coupon_applied + ' applied');
                } else cMsg.textContent = '';
            });
        };
        $$('input[name="payment_method"]', cf).forEach(function (r) { r.addEventListener('change', function () { quote(); }); });
        $('#couponBtn').addEventListener('click', function () { quote(true); });
        cInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); quote(true); } });
        if (cInput.value.trim()) quote();
        /* --- email OTP before the order is placed --- */
        var verifiedEmail = (cf.getAttribute('data-verified') || '').toLowerCase();
        var om = $('#otpModal'), oIn = $('#otpInput'), oErr = $('#otpErr'), oInfo = $('#otpInfo'), oDev = $('#otpDev');
        var oTimer = $('#otpTimer'), oResend = $('#otpResend'), tick, curEmail = '';
        var emailNow = function () { return cf.elements.email.value.trim().toLowerCase(); };

        function countdown(sec) {
            clearInterval(tick); oResend.hidden = true;
            var left = sec; oTimer.textContent = 'Resend code in ' + left + 's';
            tick = setInterval(function () {
                left--; if (left <= 0) { clearInterval(tick); oTimer.textContent = ''; oResend.hidden = false; }
                else oTimer.textContent = 'Resend code in ' + left + 's';
            }, 1000);
        }
        function closeOtp() { om.hidden = true; clearInterval(tick); }
        function sendOtp() {
            oErr.textContent = ''; oDev.hidden = true;
            AppLoader.show('Sending OTP to your email...');
            return api('/api/v1/checkout/otp/send', {body: {email: curEmail}}).then(function (d) {
                AppLoader.hide();
                if (d.success) {
                    oInfo.textContent = d.message; countdown(30);
                    if (d.dev_otp) { oDev.hidden = false; oDev.textContent = 'Dev code: ' + d.dev_otp; }
                    setTimeout(function () { oIn.focus(); }, 50);
                } else {
                    oErr.textContent = d.message || 'Could not send the code.';
                    if (d.wait) countdown(d.wait);
                }
            }).catch(function () { AppLoader.hide(); oErr.textContent = 'Network error. Please try again.'; });
        }
        function openOtp() {
            curEmail = emailNow(); oIn.value = ''; om.hidden = false;
            oInfo.textContent = 'Sending a code to ' + curEmail + '...';
            sendOtp();
        }
        function verifyOtp() {
            var code = oIn.value.trim();
            if (!/^\d{6}$/.test(code)) { oErr.textContent = 'Enter the 6-digit code.'; oIn.focus(); return; }
            oErr.textContent = '';
            AppLoader.show('Verifying code...');
            api('/api/v1/checkout/otp/verify', {body: {email: curEmail, code: code}}).then(function (d) {
                if (d.success) {
                    verifiedEmail = curEmail; closeOtp();
                    AppLoader.show('Placing your order...');
                    $('#placeBtn').disabled = true;
                    cf.submit();
                } else {
                    AppLoader.hide(); oErr.textContent = d.message || 'Verification failed.'; oIn.select();
                }
            }).catch(function () { AppLoader.hide(); oErr.textContent = 'Network error. Please try again.'; });
        }
        $('#otpVerifyBtn').addEventListener('click', verifyOtp);
        oIn.addEventListener('input', function () { oIn.value = oIn.value.replace(/\D/g, '').slice(0, 6); if (oIn.value.length === 6) verifyOtp(); });
        oIn.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); verifyOtp(); } });
        oResend.addEventListener('click', sendOtp);
        $('[data-close-otp]').addEventListener('click', closeOtp);

        cf.addEventListener('submit', function (e) {
            var bad = $$('[required]', cf).filter(function (el) { return !el.value.trim(); })[0];
            if (bad) { e.preventDefault(); bad.focus(); toast('Please fill in all required fields', true); return; }
            if (emailNow() !== verifiedEmail) { e.preventDefault(); openOtp(); return; }
            AppLoader.show('Placing your order...');
            var b = $('#placeBtn'); b.disabled = true; b.textContent = 'Placing order...';
        });
    }

    /* ---------- live location -> address fields ---------- */
    function fillAddress(form, a) {
        var set = function (n, v) { var el = form.elements[n]; if (el && v) { el.value = v; el.dispatchEvent(new Event('input', {bubbles: true})); } };
        var line1 = [a.house_number, a.road].filter(Boolean).join(', ') || a.neighbourhood || a.suburb || '';
        var line2 = [a.neighbourhood, a.suburb].filter(function (x) { return x && x !== line1; }).join(', ');
        set('line1', line1); set('line2', line2);
        set('city', a.city || a.town || a.village || a.municipality || a.county || a.state_district || '');
        set('pincode', String(a.postcode || '').replace(/\D/g, '').slice(0, 6));
        var st = form.elements.state;
        if (st && a.state) {
            var want = a.state.toLowerCase().replace(/&/g, 'and').replace(/\b(nct of|union territory of)\b/g, '').trim();
            $$('option', st).some(function (o) {
                var have = o.textContent.toLowerCase().replace(/&/g, 'and');
                if (have && (have === want || have.indexOf(want) > -1 || want.indexOf(have) > -1)) { st.value = o.value || o.textContent; return true; }
                return false;
            });
        }
    }
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-use-location]');
        if (!btn) return;
        var form = btn.closest('form');
        if (!navigator.geolocation) { toast('Your browser does not support location', true); return; }
        AppLoader.show('Detecting your location...');
        navigator.geolocation.getCurrentPosition(function (pos) {
            var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&accept-language=en&lat='
                + encodeURIComponent(pos.coords.latitude) + '&lon=' + encodeURIComponent(pos.coords.longitude);
            fetch(url, {headers: {'Accept': 'application/json'}}).then(function (r) { return r.json(); }).then(function (d) {
                AppLoader.hide();
                if (!d || !d.address) { toast('Could not read your address. Please type it in.', true); return; }
                if (d.address.country_code && d.address.country_code !== 'in') toast('We deliver only within India', true);
                else toast('Address filled from your location. Please check it.');
                fillAddress(form, d.address);
            }).catch(function () { AppLoader.hide(); toast('Could not look up your address. Please type it in.', true); });
        }, function (err) {
            AppLoader.hide();
            toast(err.code === 1 ? 'Location permission denied. Allow it in your browser or type the address.' : 'Could not get your location. Please type the address.', true);
        }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 60000});
    });

    /* ---------- newsletter ---------- */
    var nf = $('#newsletterForm');
    if (nf) nf.addEventListener('submit', function (e) {
        e.preventDefault();
        api('/api/v1/newsletter', {body: {email: nf.email.value}}).then(function (d) {
            $('#newsletterMsg').textContent = d.message || '';
            if (d.success) nf.reset();
        });
    });

    /* ---------- CSP-safe replacements for inline handlers ---------- */
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });
    document.addEventListener('change', function (e) {
        if (e.target.hasAttribute && e.target.hasAttribute('data-autosubmit') && e.target.form) e.target.form.submit();
    });

    /* ---------- misc ---------- */
    var film = $('[data-play-film]');
    if (film) film.addEventListener('click', function () { toast('Add your brand film URL in the admin panel to enable playback'); });

    // keep cart badge fresh after back/forward cache restores
    window.addEventListener('pageshow', function (e) { if (e.persisted) loadCart(); });
})();
