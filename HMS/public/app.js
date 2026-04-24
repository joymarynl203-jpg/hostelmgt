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
})();
