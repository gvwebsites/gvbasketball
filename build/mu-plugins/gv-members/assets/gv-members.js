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

    // Theme day-only selection UI logic
    function gvMembersUpdateUI() {
        // Find LatePoint time slot label elements and replace their visible text
        var slotSelectors = [
            '.slot-time',
            '.time-slot',
            '.lp-time-slot',
            '.os-time-slot',
            '.time-slot-time',
            '.lp-slot-time',
            '.os-slot-time',
            '.latepoint-time-slot',
            '.latepoint-slot-time',
            '.time-slots .slot-time-w',
            '.time-slots .slot-time',
            '.time-slot-option',
            '.os-time-option'
        ];

        slotSelectors.forEach(function(sel) {
            $(sel).each(function() {
                var $this = $(this);
                // Keep nominal data but replace visible label
                var trimmed = $this.text().trim();
                if (trimmed !== '' && trimmed !== 'Request this day' && !trimmed.match(/^Request this day/)) {
                    // Store original text if we ever need it
                    if (!$this.attr('data-original-time')) {
                        $this.attr('data-original-time', trimmed);
                    }
                    $this.text('Request this day');
                }
            });
        });

        // Also handle the selected time in review/summary step or step header
        var summarySelectors = [
            '.selected-time',
            '.booking-time',
            '.summary-time',
            '.lp-selected-time',
            '.os-selected-time',
            '.lp-summary-time',
            '.review-time',
            '.step-review-time',
            '.review-booking-time'
        ];

        summarySelectors.forEach(function(sel) {
            $(sel).each(function() {
                var $this = $(this);
                var trimmed = $this.text().trim();
                if (trimmed !== '' && trimmed !== 'Request this day') {
                    $this.text('Request this day');
                }
            });
        });

        // Add coordination note in time/date selection step
        var $timeContainer = $('.step-time-slots, .time-slots, .time-slots-w, .latepoint-time-slots, .os-time-slots-w');
        if ($timeContainer.length && !$('#gv-coordination-note').length) {
            $timeContainer.after(
                '<div id="gv-coordination-note" class="gv-info-note">' +
                'Coach Gino will coordinate the exact 45-minute time after reviewing your request.' +
                '</div>'
            );
        }

        // Add coordination note in review step if visible
        var $reviewContainer = $('.step-review, .lp-step-review, .os-step-review, .booking-review, .latepoint-booking-summary');
        if ($reviewContainer.length && !$('#gv-coordination-note-review').length) {
            $reviewContainer.append(
                '<div id="gv-coordination-note-review" class="gv-info-note">' +
                'Coach Gino will coordinate the exact 45-minute time after reviewing your request.' +
                '</div>'
            );
        }
    }

    // Call UI update on load
    gvMembersUpdateUI();

    // Re-run UI update after LatePoint finishes step transitions or AJAX updates
    $(document).ajaxComplete(function(event, jqXHR, ajaxOptions) {
        if (ajaxOptions.url && (ajaxOptions.url.indexOf('latepoint') !== -1 || ajaxOptions.url.indexOf('os_action') !== -1)) {
            gvMembersUpdateUI();
        }
    });

    // Setup MutationObserver on LatePoint wizard container to watch for dynamic DOM updates
    var observerTarget = document.querySelector('.latepoint-w') || document.body;
    if (observerTarget) {
        var observer = new MutationObserver(function(mutations) {
            gvMembersUpdateUI();
        });
        observer.observe(observerTarget, {
            childList: true,
            subtree: true
        });
    }

    // Double Submission Defense: Disable buttons while request is in-flight
    $(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
        var isLatepoint = ajaxOptions.url && (ajaxOptions.url.indexOf('latepoint') !== -1 || ajaxOptions.url.indexOf('os_action') !== -1);
        if (isLatepoint) {
            var $buttons = $('.latepoint-w button, .latepoint-w .latepoint-btn, .latepoint-w .os-next-btn, .latepoint-w .os-verify-btn, .latepoint-w .os-submit-btn');
            $buttons.addClass('gv-disabled').prop('disabled', true);
        }
    });

    $(document).ajaxComplete(function(event, jqXHR, ajaxOptions) {
        var isLatepoint = ajaxOptions.url && (ajaxOptions.url.indexOf('latepoint') !== -1 || ajaxOptions.url.indexOf('os_action') !== -1);
        if (isLatepoint) {
            var $buttons = $('.latepoint-w button, .latepoint-w .latepoint-btn, .latepoint-w .os-next-btn, .latepoint-w .os-verify-btn, .latepoint-w .os-submit-btn');
            // LatePoint's validation errors are displayed dynamically. By re-enabling, we preserve the user's ability to fix errors and retry.
            $buttons.removeClass('gv-disabled').prop('disabled', false);
        }
    });
});
