/* Authentication screens (vanilla JS, no dependencies) */
(function () {
    'use strict';

    var $ = function (s, c) { return (c || document).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
    var body = document.body, page = body.getAttribute('data-page');
    var base = body.getAttribute('data-base') || '';
    var csrf = ($('meta[name="csrf-token"]') || {}).content || '';
    var msgBox = $('#msg');

    function loader(on, text) { if (window.AppLoader) { on ? AppLoader.show(text) : AppLoader.hide(); } }

    function say(text, type) {
        if (!msgBox) return;
        if (!text) { msgBox.hidden = true; return; }
        msgBox.className = 'alert ' + (type || 'err'); msgBox.textContent = text; msgBox.hidden = false;
        msgBox.scrollIntoView({block: 'nearest', behavior: 'smooth'});
    }

    function post(path, data, label) {
        loader(true, label);
        return fetch(base + path, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(data || {})
        }).then(function (r) {
            return r.json().catch(function () { return {success: false, message: 'Unexpected server response.'}; }).then(function (d) { d._status = r.status; return d; });
        }).catch(function () {
            return {success: false, message: 'Network error. Please check your connection and try again.'};
        }).then(function (d) { loader(false); return d; });
    }

    var emailOk = function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); };

    /* show / hide password */
    $$('[data-toggle-pw]').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = b.parentNode.querySelector('input'); var show = i.type === 'password';
            i.type = show ? 'text' : 'password'; b.textContent = show ? 'Hide' : 'Show';
        });
    });

    /* password strength */
    function strength(pw) {
        var s = 0;
        if (pw.length >= 8) s++; if (pw.length >= 12) s++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++; if (/[^A-Za-z0-9]/.test(pw)) s++;
        return Math.min(s, 4);
    }
    var pwInput = $('#password'), meter = $('#meter'), meterText = $('#meterText');
    if (pwInput && meter) {
        pwInput.addEventListener('input', function () {
            var s = strength(pwInput.value), cols = ['#a3262f', '#a3262f', '#c58b2c', '#b07d2e', '#6b7d2b'], names = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'];
            meter.style.width = (pwInput.value ? (s + 1) * 20 : 0) + '%'; meter.style.background = cols[s];
            if (meterText) meterText.textContent = pwInput.value ? names[s] : 'Use letters, numbers and a symbol for a strong password.';
        });
    }

    /* ---------- 6-box OTP input ---------- */
    function OtpBoxes(root, onComplete) {
        var boxes = [];
        for (var i = 0; i < 6; i++) {
            var b = document.createElement('input');
            b.type = 'text'; b.inputMode = 'numeric'; b.maxLength = 1; b.setAttribute('aria-label', 'Digit ' + (i + 1));
            b.autocomplete = i === 0 ? 'one-time-code' : 'off';
            root.appendChild(b); boxes.push(b);
        }
        var value = function () { return boxes.map(function (b) { return b.value; }).join(''); };
        var mark = function () { boxes.forEach(function (b) { b.classList.toggle('filled', !!b.value); }); };
        var fire = function () { if (value().length === 6 && onComplete) onComplete(value()); };
        function fill(digits, from) {
            digits.split('').forEach(function (d, k) { if (boxes[from + k]) boxes[from + k].value = d; });
            var next = Math.min(from + digits.length, 5); boxes[next].focus(); mark(); fire();
        }
        boxes.forEach(function (b, idx) {
            b.addEventListener('input', function () {
                var d = b.value.replace(/\D/g, '');
                if (d.length > 1) { b.value = ''; fill(d.slice(0, 6 - idx), idx); return; }   // autofill / multi-char
                b.value = d; mark();
                if (d && idx < 5) boxes[idx + 1].focus();
                fire();
            });
            b.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !b.value && idx > 0) { boxes[idx - 1].value = ''; boxes[idx - 1].focus(); mark(); e.preventDefault(); }
                else if (e.key === 'ArrowLeft' && idx > 0) boxes[idx - 1].focus();
                else if (e.key === 'ArrowRight' && idx < 5) boxes[idx + 1].focus();
                else if (e.key === 'Enter') { e.preventDefault(); fire(); }
            });
            b.addEventListener('paste', function (e) {
                var t = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '');
                if (t) { e.preventDefault(); fill(t.slice(0, 6), t.length >= 6 ? 0 : idx); }
            });
            b.addEventListener('focus', function () { b.select(); });
        });
        return {
            value: value,
            focus: function () { boxes[0].focus(); },
            clear: function () { boxes.forEach(function (b) { b.value = ''; }); mark(); },
            state: function (s) { root.classList.remove('bad', 'good'); if (s) root.classList.add(s); },
            lock: function (on) { boxes.forEach(function (b) { b.disabled = on; }); }
        };
    }

    function Cooldown(btn, label, seconds) {
        var t, left;
        function start(sec) {
            clearInterval(t); left = sec || seconds; btn.disabled = true; btn.textContent = label + ' in ' + left + 's';
            t = setInterval(function () {
                left--; if (left <= 0) { clearInterval(t); btn.disabled = false; btn.textContent = label; }
                else btn.textContent = label + ' in ' + left + 's';
            }, 1000);
        }
        return {start: start};
    }

    function wrongCode(otp, text) {
        say(text || 'Incorrect code. Please try again.');
        otp.state('bad'); otp.clear();
        setTimeout(function () { otp.state(''); otp.focus(); }, 450);
    }

    /* ======================= LOGIN ======================= */
    if (page === 'login') {
        var lf = $('#loginForm');
        lf.addEventListener('submit', function (e) {
            e.preventDefault(); say('');
            var email = $('#email').value.trim(), pw = $('#password').value;
            if (!emailOk(email)) { say('Enter a valid email address.'); $('#email').focus(); return; }
            if (!pw) { say('Enter your password.'); $('#password').focus(); return; }
            post('/api/v1/auth/login', {email: email, password: pw}, 'Signing you in...').then(function (d) {
                if (d.success) {
                    say('Signed in. Redirecting...', 'ok'); loader(true, 'Signing you in...');
                    window.location.href = body.getAttribute('data-redirect') || (base + '/');
                } else if (d.unverified && d.user_id) {
                    window.location.href = body.getAttribute('data-verify-url') + '?user_id=' + encodeURIComponent(d.user_id);
                } else {
                    say(d.message || 'Unable to sign in.');
                    $('#password').value = ''; $('#password').focus();
                }
            });
        });
    }

    /* ====================== REGISTER ===================== */
    if (page === 'register') {
        var rf = $('#registerForm');
        rf.addEventListener('submit', function (e) {
            e.preventDefault(); say('');
            var v = function (id) { return $('#' + id).value.trim(); };
            var first = v('first_name'), email = v('email'), phone = v('phone').replace(/[\s-]/g, ''), pw = $('#password').value, cpw = $('#password_confirmation').value;
            var err = !first ? 'Enter your first name.' : !emailOk(email) ? 'Enter a valid email address.'
                : (phone && !/^(\+91|91)?[6-9]\d{9}$/.test(phone)) ? 'Enter a valid 10-digit mobile number or leave it blank.'
                : pw.length < 8 ? 'Password must be at least 8 characters.' : pw !== cpw ? 'Passwords do not match.' : '';
            if (err) { say(err); return; }
            post('/api/v1/auth/register', {first_name: first, last_name: v('last_name'), email: email, phone: phone, password: pw, password_confirmation: cpw}, 'Creating your account...').then(function (d) {
                if (d.success && d.data) {
                    say('Account created. Sending you to verification...', 'ok'); loader(true, 'Almost there...');
                    window.location.href = body.getAttribute('data-verify-url') + '?user_id=' + encodeURIComponent(d.data.user_id);
                } else say(d.message || 'Unable to create your account.');
            });
        });
    }

    /* ================= VERIFY EMAIL (OTP) ================ */
    if (page === 'verify') {
        var uid = body.getAttribute('data-user');
        var vBtn = $('#submitBtn'), rBtn = $('#resendBtn'), busy = false;
        var otpV = OtpBoxes($('#otp'), function () { submit(); });
        var cd = Cooldown(rBtn, 'Resend code', 60);
        if (!/^\d+$/.test(uid || '') || +uid <= 0) {
            say('This verification link is invalid. Please register again.'); otpV.lock(true); vBtn.disabled = true;
        } else { cd.start(60); otpV.focus(); }

        function submit() {
            if (busy) return;
            var code = otpV.value();
            if (code.length !== 6) { say('Enter all 6 digits of the code.'); return; }
            busy = true; say('');
            post('/api/v1/auth/verify-email', {user_id: +uid, otp: code}, 'Verifying your code...').then(function (d) {
                busy = false;
                if (d.success) {
                    otpV.state('good'); otpV.lock(true);
                    $('#verifyView').hidden = true; $('#doneView').hidden = false; say('');
                    setTimeout(function () { window.location.href = body.getAttribute('data-login-url'); }, 2200);
                } else if (/already verified/i.test(d.message || '')) {
                    window.location.href = body.getAttribute('data-login-url');
                } else wrongCode(otpV, d.message);
            });
        }
        vBtn.addEventListener('click', submit);
        rBtn.addEventListener('click', function () {
            say('');
            post('/api/v1/auth/resend-verification-otp', {user_id: +uid}, 'Sending a new code...').then(function (d) {
                if (d.success) { say('A new code has been sent to your email.', 'ok'); otpV.clear(); otpV.focus(); cd.start(60); }
                else {
                    say(d.message || 'Could not resend the code.');
                    var m = /(\d+)\s*seconds/.exec(d.message || ''); if (m) cd.start(+m[1]);
                }
            });
        });
    }

    /* ================== FORGOT PASSWORD ================== */
    if (page === 'forgot') {
        var show = function (n) {
            $$('.step').forEach(function (s) { s.hidden = s.getAttribute('data-step') !== String(n); });
            $$('[data-step-bar]').forEach(function (b) { b.classList.toggle('on', +b.getAttribute('data-step-bar') <= Math.min(n, 3)); });
            say('');
        };
        var otpF = OtpBoxes($('#otp'), function () { verifyReset(); });
        var fr = $('#resendBtn'), cdF = Cooldown(fr, 'Resend code', 60), fBusy = false;

        $('#emailForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var email = $('#email').value.trim();
            if (!emailOk(email)) { say('Enter a valid email address.'); return; }
            post('/api/v1/auth/forgot-password', {email: email}, 'Sending OTP to your email...').then(function (d) {
                if (d.success) { $('#sentTo').textContent = email; show(2); cdF.start(60); otpF.focus(); }
                else say(d.message || 'Unable to send the reset code.');
            });
        });
        function verifyReset() {
            if (fBusy) return; var code = otpF.value();
            if (code.length !== 6) { say('Enter all 6 digits of the code.'); return; }
            fBusy = true;
            post('/api/v1/auth/verify-password-reset-otp', {otp: code}, 'Verifying your code...').then(function (d) {
                fBusy = false;
                if (d.success) { show(3); $('#password').focus(); } else wrongCode(otpF, d.message);
            });
        }
        $('#verifyBtn').addEventListener('click', verifyReset);
        fr.addEventListener('click', function () {
            post('/api/v1/auth/resend-password-reset-otp', {}, 'Sending a new code...').then(function (d) {
                if (d.success) { say('A new code has been sent.', 'ok'); otpF.clear(); otpF.focus(); cdF.start(60); }
                else { say(d.message || 'Could not resend the code.'); var m = /(\d+)\s*seconds/.exec(d.message || ''); if (m) cdF.start(+m[1]); }
            });
        });
        $('#resetForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var pw = $('#password').value, cpw = $('#password_confirmation').value;
            if (pw.length < 8) { say('Password must be at least 8 characters.'); return; }
            if (pw !== cpw) { say('Passwords do not match.'); return; }
            post('/api/v1/auth/reset-password', {password: pw, password_confirmation: cpw}, 'Updating your password...').then(function (d) {
                if (d.success) { show(4); setTimeout(function () { window.location.href = body.getAttribute('data-login-url'); }, 2500); }
                else say(d.message || 'Unable to update your password.');
            });
        });
    }
})();
