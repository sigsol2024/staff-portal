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
});
