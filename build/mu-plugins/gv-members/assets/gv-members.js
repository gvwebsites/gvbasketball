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
        // Prefill/lock native contact fields if logged in
        if (window.gvMembers && gvMembers.isLoggedIn) {
            var select = 'input[name="customer[first_name]"], input[name="customer[last_name]"], input[name="customer[email]"], input.lp-customer-email, input.lp-customer-first-name, input.lp-customer-last-name';
            $(select).prop('readonly', true).addClass('gv-locked-field');
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

