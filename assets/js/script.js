/**
 * Staff Management Portal - Client scripts
 * Confirmation dialogs, password show/hide, form helpers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Confirm before delete/suspend (fallback if onclick not used)
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-wrap')) {
            document.querySelectorAll('.dropdown-menu.open').forEach(function(m) {
                m.classList.remove('open');
            });
        }
    });

    // Show/hide password on all password fields
    document.querySelectorAll('input[type=password]').forEach(function(input) {
        if (input.closest('.password-wrap')) return;
        var wrap = document.createElement('div');
        wrap.className = 'password-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle';
        btn.setAttribute('aria-label', 'Show password');
        btn.textContent = 'Show';
        btn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                btn.setAttribute('aria-label', 'Hide password');
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.setAttribute('aria-label', 'Show password');
                btn.textContent = 'Show';
            }
        });
        wrap.appendChild(btn);
    });
});
