<div class="modal fade" id="login-modal" tabindex="-1" role="dialog" aria-labelledby="login-heading" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="login-heading">Login with PGLife</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="login-form" class="form" role="form" method="post" action="api/login_submit.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>

                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" name="password" autocomplete="current-password" placeholder="Password" minlength="8" required>
                    </div>

                    <div class="text-right mb-3" style="font-size: 13px;">
                        <a href="#" id="forgot-password-link" data-dismiss="modal" data-toggle="modal" data-target="#forgot-password-modal" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Forgot Password?</a>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-block btn-primary">Login</button>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <span>
                    <a href="#" data-dismiss="modal" data-toggle="modal" data-target="#signup-modal">Click here</a>
                    to register a new account
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgot-password-modal" tabindex="-1" role="dialog" aria-labelledby="forgot-heading" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgot-heading">Reset Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Step 1: Send Recovery OTP -->
                <form id="forgot-form" class="form" role="form" method="post" action="api/forgot_password.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                    <p class="text-muted text-center" style="font-size: 13px; line-height: 1.5;">Enter your registered email address. We will send you an OTP to reset your password.</p>
                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-block btn-primary">Send Verification OTP</button>
                    </div>
                </form>

                <!-- Step 2: Input OTP & New Password -->
                <form id="reset-form" class="form" role="form" method="post" action="api/reset_password.php" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                    <div class="text-center mb-3">
                        <i class="fas fa-envelope-open-text text-primary" style="font-size: 36px; color: var(--primary-color) !important;"></i>
                    </div>
                    <p class="text-muted text-center" style="font-size: 13px; line-height: 1.5;">Enter the 6-digit OTP code sent to your email and your new password.</p>
                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" name="otp" placeholder="6-Digit OTP" maxlength="6" minlength="6" required>
                    </div>
                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" name="password" id="reset-password-input" autocomplete="new-password" placeholder="New Password (min 8 characters)" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-block btn-success">Reset Password</button>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <span>Back to <a href="#" data-dismiss="modal" data-toggle="modal" data-target="#login-modal">Login</a></span>
            </div>
        </div>
    </div>
</div>