/**
 * Staff Management Portal - Client scripts
 * Confirmation dialogs and form helpers
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
});
