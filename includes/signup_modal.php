<div class="modal fade" id="signup-modal" tabindex="-1" role="dialog" aria-labelledby="signup-heading" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signup-heading">Signup with PGLife</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Main Signup Form -->
                <form id="signup-form" class="form" role="form" method="post" action="api/signup_submit.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                    
                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" name="full_name" placeholder="Full Name" aria-label="Full Name" maxlength="30" required>
                    </div>

                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-phone-alt"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" name="phone" placeholder="Phone Number" aria-label="Phone Number" maxlength="10" minlength="10" required>
                    </div>

                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control" name="email" placeholder="Email" aria-label="Email Address" required>
                    </div>

                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" name="password" autocomplete="new-password" placeholder="Password" aria-label="Password" minlength="8" required>
                    </div>

                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-building"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" name="institution_or_organization" placeholder="College / Company (Optional)" aria-label="College or Company" maxlength="150">
                    </div>

                    <div class="form-group">
                        <span>I'm a</span>
                        <input type="radio" class="ml-3" id="gender-male" name="gender" value="male" required />
                        <label for="gender-male" class="mb-0">Male</label>
                        <input type="radio" class="ml-3" id="gender-female" name="gender" value="female" required />
                        <label for="gender-female" class="mb-0">Female</label>
                    </div>
                    <div class="form-group border-top pt-3">
                        <span class="font-weight-bold">Profile Type:</span>
                        <input type="radio" class="ml-3" id="role-seeker" name="role" value="seeker" checked required /> 
                        <label for="role-seeker" class="mb-0">PG Seeker</label>
                        <input type="radio" class="ml-3" id="role-owner" name="role" value="owner" required /> 
                        <label for="role-owner" class="mb-0">PG Owner</label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-block btn-primary">Send Verification OTP</button>
                    </div>

                    <p style="font-size: 12px; color: #94A3B8; text-align: center; margin-top: 8px; line-height: 1.5;">
                        By signing up, you agree to our
                        <a href="/terms" target="_blank" style="color: var(--primary-color); text-decoration: none;">Terms of Service</a>
                        and
                        <a href="/privacy" target="_blank" style="color: var(--primary-color); text-decoration: none;">Privacy Policy</a>.
                    </p>
                </form>

                <!-- OTP Verification Form -->
                <form id="otp-form" class="form" role="form" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                    <div class="text-center mb-3">
                        <i class="fas fa-envelope-open-text text-primary" style="font-size: 36px;"></i>
                    </div>
                    <p class="text-muted text-center">We have sent a <strong>6-digit OTP</strong> to your email address. Please check your inbox (and spam folder) and enter the code below to complete your registration.</p>
                    <div class="input-group form-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" name="otp" id="otp-input" placeholder="Enter 6-Digit OTP" aria-label="6-Digit OTP" maxlength="6" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-block btn-success">Verify OTP & Create Account</button>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <span>Already have an account?
                    <a href="#" data-dismiss="modal" data-toggle="modal" data-target="#login-modal">Login</a>
                </span>
            </div>
        </div>
    </div>
</div>