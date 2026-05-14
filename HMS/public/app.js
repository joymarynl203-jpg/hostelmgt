(function () {
    'use strict';

    function targetsPaymentStart(url) {
        if (!url || typeof url !== 'string') {
            return false;
        }
        return url.indexOf('payment_start.php') !== -1;
    }

    document.addEventListener(
        'click',
        function (e) {
            var el = e.target && e.target.closest ? e.target.closest('a[data-hms-confirm]') : null;
            if (!el || el.tagName !== 'A') {
                return;
            }
            var msg = el.getAttribute('data-hms-confirm');
            if (!msg) {
                return;
            }
            if (targetsPaymentStart(el.getAttribute('href'))) {
                return;
            }
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        },
        true
    );

    document.addEventListener(
        'submit',
        function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') {
                return;
            }
            var msg = form.getAttribute('data-hms-confirm');
            if (!msg) {
                return;
            }
            if (targetsPaymentStart(form.getAttribute('action'))) {
                return;
            }
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        },
        true
    );

    function initPasswordShowToggles() {
        var idCounter = 0;
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.closest('.hms-password-field') || input.hasAttribute('data-hms-no-password-toggle')) {
                return;
            }
            var wrap = document.createElement('div');
            wrap.className = 'input-group hms-password-field';
            var parent = input.parentNode;
            if (!parent) {
                return;
            }
            parent.insertBefore(wrap, input);
            wrap.appendChild(input);
            var inputId = input.id;
            if (!inputId) {
                idCounter += 1;
                inputId = 'hms-pw-' + String(idCounter);
                input.id = inputId;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary';
            btn.setAttribute('aria-controls', inputId);
            btn.setAttribute('aria-label', 'Show password');
            btn.setAttribute('aria-pressed', 'false');
            btn.textContent = 'Show';
            btn.addEventListener('click', function () {
                var showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                btn.setAttribute('aria-pressed', showing ? 'true' : 'false');
                btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
                btn.textContent = showing ? 'Hide' : 'Show';
            });
            wrap.appendChild(btn);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordShowToggles);
    } else {
        initPasswordShowToggles();
    }
})();
