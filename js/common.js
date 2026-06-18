/* ==================================================
   PGLife - common.js
   Handles: Auth forms (signup/login/otp), 
            Page transitions, 
            Toast notifications
   ================================================== */

// ---- Toast Notification System ----
function showToast(message, type) {
    type = type || 'info'; // 'success', 'error', 'info', 'warning'
    var toastId = 'pglife-toast-' + Date.now();
    
    var colorMap = {
        success: { bg: '#28a745', icon: 'fa-check-circle' },
        error:   { bg: '#dc3545', icon: 'fa-times-circle' },
        warning: { bg: '#ffc107', icon: 'fa-exclamation-triangle' },
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

    // Auto-dismiss after 4 seconds
    setTimeout(function() {
        if (document.getElementById(toastId)) {
            document.getElementById(toastId).style.animation = 'pglife-slide-out 0.35s ease forwards';
            setTimeout(function() { if (document.getElementById(toastId)) document.getElementById(toastId).remove(); }, 350);
        }
    }, 4500);
}

// ---- Smooth Page Navigate ----
function navigateTo(url) {
    document.body.style.transition = 'opacity 0.28s ease, transform 0.28s ease';
    document.body.style.opacity = '0';
    document.body.style.transform = 'translateY(-6px)';
    setTimeout(function() { window.location.href = url; }, 300);
}

// Intercept regular anchor clicks for smooth navigation (except modal-openers, targets, API links, anchors)
document.addEventListener('click', function(e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    // Skip: empty href, anchors, external, modals, javascript:, download
    if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('http') || href.startsWith('//') || link.hasAttribute('data-toggle') || link.hasAttribute('data-dismiss') || link.hasAttribute('download') || link.hasAttribute('target')) return;
    e.preventDefault();
    navigateTo(href);
}, false);

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
            setTimeout(function() { navigateTo("home.php"); }, 1000);
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
        setTimeout(function() { navigateTo("dashboard.php"); }, 1200);
    } else {
        showToast(response.message || 'Invalid OTP. Please try again.', 'error');
    }
};

var login_success = function (event) {
    document.getElementById("loading").style.display = 'none';
    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast('Welcome back! Logging you in...', 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        showToast(response.message || 'Login failed. Please check your credentials.', 'error');
    }
};

var on_error = function (event) {
    document.getElementById("loading").style.display = 'none';
    showToast('Oops! A network error occurred. Please try again.', 'error');
};