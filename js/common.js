/* ==================================================
   PGLife - common.js
   Handles: Auth forms (signup/login/otp),
            Page transitions (no-blink),
            Toast notifications
   ================================================== */

// ---- Toast Notification System ----
function showToast(message, type) {
    type = type || 'info'; // 'success', 'error', 'info', 'warning'
    var toastId = 'pglife-toast-' + Date.now();

    var colorMap = {
        success: { bg: '#28a745', icon: 'fa-check-circle' },
        error:   { bg: '#dc3545', icon: 'fa-times-circle' },
        warning: { bg: '#e07b00', icon: 'fa-exclamation-triangle' },
        info:    { bg: '#17a2b8', icon: 'fa-info-circle' }
    };
    var style = colorMap[type] || colorMap['info'];

    var container = document.getElementById('pglife-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'pglife-toast-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999999;display:flex;flex-direction:column;gap:8px;max-width:360px;';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.id = toastId;
    toast.innerHTML = '<i class="fas ' + style.icon + '" style="margin-right:10px;font-size:16px;flex-shrink:0;"></i><span style="line-height:1.4;">' + message + '</span><button onclick="document.getElementById(\'' + toastId + '\').remove()" style="background:none;border:none;color:white;font-size:20px;margin-left:auto;cursor:pointer;line-height:1;padding:0 0 0 10px;">&times;</button>';
    toast.style.cssText = 'background:' + style.bg + ';color:white;padding:14px 18px;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.25);display:flex;align-items:center;animation:pglife-slide-in 0.35s ease;font-size:14px;font-weight:500;';

    if (!document.getElementById('pglife-toast-style')) {
        var s = document.createElement('style');
        s.id = 'pglife-toast-style';
        s.textContent = '@keyframes pglife-slide-in{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:translateX(0)}} @keyframes pglife-slide-out{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(60px)}}';
        document.head.appendChild(s);
    }

    container.appendChild(toast);

    // Auto-dismiss after 4.5 seconds
    setTimeout(function() {
        if (document.getElementById(toastId)) {
            document.getElementById(toastId).style.animation = 'pglife-slide-out 0.35s ease forwards';
            setTimeout(function() { if (document.getElementById(toastId)) document.getElementById(toastId).remove(); }, 350);
        }
    }, 4500);
}

// ---- Smooth Page Navigate (zero-blink) ----
// The page starts invisible (opacity:0 on html element, set immediately by inline style).
// We animate body out, navigate, and the new page fades in via CSS animation.
function navigateTo(url) {
    // Prevent double-navigation
    if (window._navigating) return;
    window._navigating = true;

    var el = document.body;
    el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-8px)';
    el.style.pointerEvents = 'none';

    setTimeout(function() {
        window.location.href = url;
    }, 270);
}

// Intercept anchor clicks for smooth navigation
// (skip: modal-openers, external links, anchors, API, download, target=_blank)
document.addEventListener('click', function(e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:') ||
        href.startsWith('http') || href.startsWith('//') || href.startsWith('mailto:') ||
        link.hasAttribute('data-toggle') || link.hasAttribute('data-dismiss') ||
        link.hasAttribute('download') || link.hasAttribute('target')) return;
    e.preventDefault();
    navigateTo(href);
}, false);

// Intercept browser Back/Forward so fade-in runs
window.addEventListener('pageshow', function(e) {
    // Restore body visibility if page was restored from bfcache
    if (e.persisted) {
        document.body.style.transition = 'none';
        document.body.style.opacity = '1';
        document.body.style.transform = 'none';
        document.body.style.pointerEvents = '';
        window._navigating = false;
    }
});

// ---- Load Event ----
window.addEventListener("load", function () {
    // --- Signup Form ---
    var signup_form = document.getElementById("signup-form");
    if (signup_form) {
        signup_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(signup_form);

            XHR.addEventListener("load", signup_success);
            XHR.addEventListener("error", on_error);
            XHR.open("POST", "api/signup_submit.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // --- OTP Form ---
    var otp_form = document.getElementById("otp-form");
    if (otp_form) {
        otp_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(otp_form);

            XHR.addEventListener("load", otp_success);
            XHR.addEventListener("error", on_error);
            XHR.open("POST", "api/signup_verify.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // --- Login Form ---
    var login_form = document.getElementById("login-form");
    if (login_form) {
        login_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(login_form);

            XHR.addEventListener("load", login_success);
            XHR.addEventListener("error", on_error);
            XHR.open("POST", "api/login_submit.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // --- Forgot Password Form ---
    var forgot_form = document.getElementById("forgot-form");
    if (forgot_form) {
        forgot_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(forgot_form);

            XHR.addEventListener("load", function(e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'OTP sent! Please check your email.', 'success');
                        
                        // Transition to Reset Form
                        var ff = document.getElementById("forgot-form");
                        var rf = document.getElementById("reset-form");
                        ff.style.transition = 'opacity 0.3s ease';
                        ff.style.opacity = '0';
                        setTimeout(function() {
                            ff.style.display = "none";
                            rf.style.opacity = '0';
                            rf.style.display = "block";
                            setTimeout(function() {
                                rf.style.transition = 'opacity 0.3s ease';
                                rf.style.opacity = '1';
                            }, 50);
                        }, 300);
                    } else {
                        showToast(response.message || 'Failed to send recovery OTP.', 'error');
                    }
                } catch(err) {
                    showToast('An error occurred during password reset request.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/forgot_password.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // --- Reset Password Form ---
    var reset_form = document.getElementById("reset-form");
    if (reset_form) {
        reset_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(reset_form);

            XHR.addEventListener("load", function(e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'Password reset successfully!', 'success');
                        setTimeout(function() {
                            // Reset modal states and switch back to login modal
                            window.$("#forgot-password-modal").modal("hide");
                            
                            // Reset forms inside modal
                            document.getElementById("forgot-form").reset();
                            document.getElementById("forgot-form").style.display = "block";
                            document.getElementById("forgot-form").style.opacity = "1";
                            document.getElementById("reset-form").reset();
                            document.getElementById("reset-form").style.display = "none";

                            window.$("#login-modal").modal("show");
                        }, 1200);
                    } else {
                        showToast(response.message || 'Failed to reset password.', 'error');
                    }
                } catch(err) {
                    showToast('An error occurred during password verification.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/reset_password.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }
});

// ---- Success/Error Handlers ----
var signup_success = function (event) {
    document.getElementById("loading").style.display = 'none';
    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        if (response.otp_required) {
            showToast(response.message || 'OTP sent! Please check your email.', 'success');
            // Smooth transition from signup form to OTP form
            var sf = document.getElementById("signup-form");
            var of = document.getElementById("otp-form");
            sf.style.transition = 'opacity 0.3s ease';
            sf.style.opacity = '0';
            setTimeout(function() {
                sf.style.display = "none";
                of.style.opacity = '0';
                of.style.display = "block";
                setTimeout(function() {
                    of.style.transition = 'opacity 0.3s ease';
                    of.style.opacity = '1';
                    var otpInput = document.getElementById('otp-input');
                    if (otpInput) otpInput.focus();
                }, 50);
            }, 300);
        } else {
            showToast(response.message || 'Account created successfully!', 'success');
            setTimeout(function() { navigateTo("/home"); }, 1000);
        }
    } else {
        showToast(response.message || 'Signup failed. Please try again.', 'error');
    }
};

var otp_success = function (event) {
    document.getElementById("loading").style.display = 'none';
    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast(response.message || 'Account verified! Welcome to PGLife!', 'success');
        setTimeout(function() { navigateTo("/dashboard"); }, 1200);
    } else {
        showToast(response.message || 'Invalid OTP. Please try again.', 'error');
    }
};

var login_success = function (event) {
    document.getElementById("loading").style.display = 'none';
    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast('Welcome back! Logging you in...', 'success');
        setTimeout(function() {
            var el = document.body;
            el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function() { location.reload(); }, 250);
        }, 550);
    } else {
        showToast(response.message || 'Login failed. Please check your credentials.', 'error');
    }
};

var on_error = function (event) {
    document.getElementById("loading").style.display = 'none';
    showToast('Oops! A network error occurred. Please try again.', 'error');
};