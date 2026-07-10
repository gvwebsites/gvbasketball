/**
 * GV Members Integration Client-side Script
 */
jQuery(document).ready(function($) {
    // Consultation CTA bridge. CTAs carry data-gv-consultation (legacy links to
    // /book-a-consultation/ are also intercepted). With multiple venues, the GV
    // venue chooser opens first; picking a venue fires the hidden LatePoint
    // trigger preset to that location, so the wizard's availability and
    // booking.location_id are venue-scoped.
    function gvOpenVenueChooser() {
        $('#gv-venue-chooser').removeAttr('hidden');
        $('#gv-venue-chooser .gv-venue-option').first().trigger('focus');
    }

    function gvCloseVenueChooser() {
        var $chooser = $('#gv-venue-chooser');
        $chooser.attr('hidden', 'hidden').removeClass('gv-venue-loading');
        $chooser.find('.gv-venue-option, .gv-venue-cancel').prop('disabled', false);
        $chooser.find('.gv-venue-loading-note').attr('hidden', 'hidden');
    }

    $(document).on('click', 'a[href*="/book-a-consultation/"], [data-gv-consultation]', function(e) {
        // Skip right clicks, cmd/ctrl clicks, or if targeting another window
        if (e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        var $triggers = $('#gv-consult-trigger .os_trigger_booking');
        if (!$triggers.length) {
            return;
        }
        e.preventDefault();

        if ($('#gv-venue-chooser').length) {
            gvOpenVenueChooser();
        } else {
            $triggers.first().trigger('click');
        }
    });

    $(document).on('click', '#gv-venue-chooser .gv-venue-option', function() {
        var venueId = $(this).attr('data-gv-venue');
        var $chooser = $('#gv-venue-chooser');

        // Keep the chooser up (in a loading state) until the LatePoint wizard
        // has actually rendered its form, so there is no dead gap where the
        // visitor might click away.
        $chooser.addClass('gv-venue-loading');
        $chooser.find('.gv-venue-option, .gv-venue-cancel').prop('disabled', true);
        $chooser.find('.gv-venue-loading-note').removeAttr('hidden');

        $('#gv-consult-trigger [data-gv-venue-trigger="' + venueId + '"] .os_trigger_booking')
            .first().trigger('click');

        var waited = 0;
        var poll = setInterval(function() {
            waited += 100;
            var wizardReady = $('.latepoint-lightbox-w form, .latepoint-lightbox-w .latepoint-step-content').length > 0;
            if (wizardReady || waited >= 10000) {
                clearInterval(poll);
                gvCloseVenueChooser();
            }
        }, 100);
    });

    $(document).on('click', '#gv-venue-chooser [data-gv-venue-close]', function() {
        gvCloseVenueChooser();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#gv-venue-chooser').length && !$('#gv-venue-chooser').attr('hidden')) {
            gvCloseVenueChooser();
        }
    });

    // Theme day-only selection UI logic.
    // LatePoint 5.6.6 renders each slot as `.timeslots .dp-timebox[data-minutes]`
    // containing `.dp-label > .dp-label-time` (e.g. "08:00 am") and a `.dp-tick`
    // ("8 am"). We keep data-minutes untouched (the wizard still submits the real
    // start_time) and only rewrite the visible text to "BOOK A CONSULTATION".
    var GV_CONSULTATION_ACTION_LABEL = 'BOOK A CONSULTATION';

    function gvMembersUpdateUI() {
        // Slot grid: relabel the visible time on each pickable timebox.
        $('.timeslots .dp-timebox').each(function() {
            var $box = $(this);
            var $time = $box.find('.dp-label-time');
            $time.addClass('gv-consult-day-action');
            if ($time.text().trim() !== GV_CONSULTATION_ACTION_LABEL) {
                $time.text(GV_CONSULTATION_ACTION_LABEL);
            }
            // The compact tick label ("8 am") also leaks the nominal time.
            $box.find('.dp-tick').css('visibility', 'hidden');
        });

        // Any already-selected time echoed in the summary/side panel.
        $('.summary-item-time, .os-selected-slot, .dp-selected-time').each(function() {
            var $this = $(this);
            if ($this.text().trim() !== '' && $this.text().trim() !== GV_CONSULTATION_ACTION_LABEL) {
                $this.text(GV_CONSULTATION_ACTION_LABEL);
            }
        });

        // The wizard summary highlights the picked slot as "July 15, 03:00pm";
        // the nominal time stays internal while the request is pending, so strip
        // the time portion and keep just the day.
        $('.sbc-highlighted-item').each(function() {
            var $this = $(this);
            var stripped = $this.text().replace(/,?\s*\d{1,2}:\d{2}\s*(am|pm)\b/i, '');
            if (stripped !== $this.text()) {
                $this.text(stripped);
            }
        });

        // Coordination note under the slot grid.
        var $timeContainer = $('.timeslots').last();
        if ($timeContainer.length && !$('#gv-coordination-note').length) {
            $timeContainer.after(
                '<div id="gv-coordination-note" class="gv-info-note">' +
                'Coach Gino will coordinate the exact 45-minute time after reviewing your request.' +
                '</div>'
            );
        }

        // Explicitly render Turnstile widgets that arrive via LatePoint's AJAX
        // step loads: api.js implicit rendering only scans the DOM when the
        // script first loads, so a container inserted later stays empty (and
        // the request would fail the server-side security check).
        if (window.turnstile && typeof window.turnstile.render === 'function') {
            $('.cf-turnstile').each(function() {
                if (!this.hasChildNodes() && !$(this).data('gvTurnstileRendered')) {
                    $(this).data('gvTurnstileRendered', true);
                    try {
                        window.turnstile.render(this);
                    } catch (err) {
                        // Already rendered or container not ready; ignore.
                    }
                }
            });
        }

        // Prefill/lock native contact fields if logged in.
        if (window.gvMembers && gvMembers.isLoggedIn) {
            var lockSel = 'input[name="customer[first_name]"], input[name="customer[last_name]"], input[name="customer[email]"], input.lp-customer-email, input.lp-customer-first-name, input.lp-customer-last-name';
            $(lockSel).prop('readonly', true).addClass('gv-locked-field');
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

    // Watch for LatePoint's dynamic DOM updates (calendar days, slot grids, step
    // transitions). Observe document.body, NOT .latepoint-w: LatePoint replaces
    // its own container's contents between steps, which would orphan an observer
    // bound to .latepoint-w and silently stop the day-only theming. A body-level
    // subtree observer survives every re-render. Debounced via setTimeout, NOT
    // requestAnimationFrame: rAF never fires in hidden/background tabs, which
    // permanently wedges a "scheduled" flag and silently stops all updates.
    if (document.body) {
        var gvUpdateTimer = null;
        var observer = new MutationObserver(function() {
            if (gvUpdateTimer) { return; }
            gvUpdateTimer = setTimeout(function() {
                gvUpdateTimer = null;
                gvMembersUpdateUI();
            }, 50);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // Double Submission Defense: Disable buttons while request is in-flight
    $(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
        var isLatepoint = ajaxOptions.url && (ajaxOptions.url.indexOf('latepoint') !== -1 || ajaxOptions.url.indexOf('os_action') !== -1);
        if (isLatepoint) {
            var $buttons = $('.latepoint-w button, .latepoint-w .latepoint-btn, .latepoint-w .os-next-btn, .latepoint-w .os-verify-btn, .latepoint-w .os-submit-btn');
            $buttons.addClass('gv-disabled').prop('disabled', true);
        }
    });

    // ==================== OTP PORTAL AUTHENTICATION FLOW ====================

    // OTP Request Form Submit
    $('#gv-otp-request-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var email = $('#gv-otp-email').val().trim();
        var $status = $('#gv-request-status');
        var $error = $('#gv-request-error');
        var $submit = $form.find('button[type="submit"]');

        if (!email) {
            $error.text('Please enter a valid email address.');
            return;
        }

        $error.text('');
        $status.text('Sending code...');
        $submit.prop('disabled', true);
        $('#gv-otp-email').prop('disabled', true);

        $.post(gvMembers.ajaxUrl, {
            action: 'gv_otp_request',
            email: email,
            nonce: gvMembers.otpNonce
        }, function(response) {
            if (response.success) {
                $status.text('');
                $('#gv-verify-target-email').text(email);
                $form.hide();
                $('#gv-otp-verify-form').show();
                $('.gv-otp-digit').first().focus();
                
                // Clear verify fields
                $('.gv-otp-digit').val('');
                $('#gv-otp-code').val('');
                $('#gv-verify-error').text('');
                $('#gv-verify-status').text(response.data.message);

                startResendCountdown();
            } else {
                $status.text('');
                $error.text(response.data.message || 'Failed to send code.');
                $submit.prop('disabled', false);
                $('#gv-otp-email').prop('disabled', false);
            }
        }).fail(function() {
            $status.text('');
            $error.text('An error occurred. Please try again.');
            $submit.prop('disabled', false);
            $('#gv-otp-email').prop('disabled', false);
        });
    });

    var resendTimer = null;
    function startResendCountdown() {
        var duration = 30;
        var $resendBtn = $('#gv-btn-resend');
        $resendBtn.prop('disabled', true).text('Resend Code (' + duration + 's)');
        
        if (resendTimer) {
            clearInterval(resendTimer);
        }

        resendTimer = setInterval(function() {
            duration--;
            if (duration <= 0) {
                clearInterval(resendTimer);
                $resendBtn.prop('disabled', false).text('Resend Code');
            } else {
                $resendBtn.text('Resend Code (' + duration + 's)');
            }
        }, 1000);
    }

    // OTP Verify Form Submit
    $('#gv-otp-verify-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var email = $('#gv-otp-email').val().trim();
        var code = '';
        
        $('.gv-otp-digit').each(function() {
            code += $(this).val();
        });
        
        $('#gv-otp-code').val(code);

        var $status = $('#gv-verify-status');
        var $error = $('#gv-verify-error');
        var $submit = $('#gv-btn-verify');
        var $resend = $('#gv-btn-resend');
        var $back = $('#gv-btn-back');

        if (code.length !== 6) {
            $error.text('Please enter the 6-digit code.');
            return;
        }

        $error.text('');
        $status.text('Verifying code...');
        $submit.prop('disabled', true);
        $resend.prop('disabled', true);
        $back.prop('disabled', true);
        $('.gv-otp-digit').prop('disabled', true);

        $.post(gvMembers.ajaxUrl, {
            action: 'gv_otp_verify',
            email: email,
            otp: code,
            nonce: gvMembers.otpNonce
        }, function(response) {
            if (response.success) {
                $status.text('Logged in! Redirecting...');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                $status.text('');
                $error.text(response.data.message || 'Invalid verification code.');
                $submit.prop('disabled', false);
                $back.prop('disabled', false);
                $('.gv-otp-digit').prop('disabled', false).val('');
                $('#gv-otp-code').val('');
                $('.gv-otp-digit').first().focus();
                
                // Re-enable resend if countdown finished
                if ($resend.text() === 'Resend Code') {
                    $resend.prop('disabled', false);
                }
            }
        }).fail(function() {
            $status.text('');
            $error.text('An error occurred. Please try again.');
            $submit.prop('disabled', false);
            $back.prop('disabled', false);
            $('.gv-otp-digit').prop('disabled', false);
            if ($resend.text() === 'Resend Code') {
                $resend.prop('disabled', false);
            }
        });
    });

    // Resend Code Click
    $('#gv-btn-resend').on('click', function() {
        var email = $('#gv-otp-email').val().trim();
        var $status = $('#gv-verify-status');
        var $error = $('#gv-verify-error');
        
        $error.text('');
        $status.text('Resending code...');
        $(this).prop('disabled', true);

        $.post(gvMembers.ajaxUrl, {
            action: 'gv_otp_request',
            email: email,
            nonce: gvMembers.otpNonce
        }, function(response) {
            if (response.success) {
                $status.text(response.data.message);
                startResendCountdown();
            } else {
                $status.text('');
                $error.text(response.data.message || 'Failed to resend code.');
                $('#gv-btn-resend').prop('disabled', false);
            }
        }).fail(function() {
            $status.text('');
            $error.text('An error occurred. Please try again.');
            $('#gv-btn-resend').prop('disabled', false);
        });
    });

    // Back to Email Click
    $('#gv-btn-back').on('click', function() {
        if (resendTimer) {
            clearInterval(resendTimer);
        }
        $('#gv-otp-verify-form').hide();
        $('#gv-otp-request-form').show();
        $('#gv-otp-email').prop('disabled', false).focus();
        $('#gv-otp-request-form').find('button[type="submit"]').prop('disabled', false);
    });

    // Digit Inputs Handling: paste / backspace / auto-focus
    $('.gv-otp-digit').on('keydown', function(e) {
        var $this = $(this);
        var index = $('.gv-otp-digit').index($this);

        if (e.key === 'Backspace') {
            if ($this.val() === '') {
                if (index > 0) {
                    $('.gv-otp-digit').eq(index - 1).val('').focus();
                }
            } else {
                $this.val('');
            }
            e.preventDefault();
        }
    });

    $('.gv-otp-digit').on('input', function(e) {
        var $this = $(this);
        var index = $('.gv-otp-digit').index($this);
        var val = $this.val().replace(/[^0-9]/g, '');
        $this.val(val);

        if (val !== '' && index < 5) {
            $('.gv-otp-digit').eq(index + 1).focus();
        }
        
        // Auto-verify if all digits entered
        var code = '';
        $('.gv-otp-digit').each(function() {
            code += $(this).val();
        });
        if (code.length === 6) {
            $('#gv-otp-verify-form').trigger('submit');
        }
    });

    $('.gv-otp-digit').on('paste', function(e) {
        e.preventDefault();
        var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
        var pastedData = clipboardData.getData('text').trim().replace(/[^0-9]/g, '');

        if (pastedData.length >= 6) {
            $('.gv-otp-digit').each(function(i) {
                $(this).val(pastedData.charAt(i));
            });
            $('#gv-otp-verify-form').trigger('submit');
        }
    });
});
