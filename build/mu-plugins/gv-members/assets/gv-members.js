/**
 * GV Members Integration Client-side Script
 */
jQuery(document).ready(function($) {
    // Intercept clicks on links pointing to /book-a-consultation/
    // or elements with data-gv-consultation attribute
    $(document).on('click', 'a[href*="/book-a-consultation/"], [data-gv-consultation]', function(e) {
        // Skip right clicks, cmd/ctrl clicks, or if targeting another window
        if (e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        var $trigger = $('#gv-consult-trigger .os_trigger_booking');
        if ($trigger.length) {
            e.preventDefault();
            $trigger.first().trigger('click');
        }
    });
});
