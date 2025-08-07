<div class="login-content">
    <div class="lc-block toggled" id="l-login">
        <div class="lcb-form">
            <div class="text-center">
                <i class="zmdi zmdi-account-circle zmdi-hc-5x"></i>
                <h2 class="m-t-20">PPGS Task Management</h2>
                <p class="m-b-20">Sign in to start your session</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="functions/auth.php" method="POST">
                <div class="form-group fg-line">
                    <div class="fg-line">
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                </div>

                <div class="form-group fg-line">
                    <div class="fg-line">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="remember">
                            <i class="input-helper"></i>
                            Remember me
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block waves-effect">
                    <i class="zmdi zmdi-sign-in"></i> Sign In
                </button>
            </form>
        </div>

        <div class="lcb-navigation">
            <a href="index.php?page=register" data-ma-action="login-switch" data-ma-block="#l-register">
                <i class="zmdi zmdi-account-add"></i> <span>Register</span>
            </a>
            <a href="#" data-ma-action="login-switch" data-ma-block="#l-forget-password">
                <i class="zmdi zmdi-help"></i> <span>Forgot Password?</span>
            </a>
        </div>
    </div>

    <!-- Register Block -->
    <div class="lc-block" id="l-register">
        <div class="lcb-form">
            <div class="text-center">
                <i class="zmdi zmdi-account-add zmdi-hc-5x"></i>
                <h2 class="m-t-20">Create Account</h2>
                <p class="m-b-20">Fill in the form below to get instant access</p>
            </div>

            <form action="functions/register.php" method="POST">
                <div class="form-group fg-line">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>

                <div class="form-group fg-line">
                    <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                </div>

                <div class="form-group fg-line">
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>

                <div class="form-group fg-line">
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block waves-effect">
                    <i class="zmdi zmdi-account-add"></i> Register
                </button>
            </form>
        </div>

        <div class="lcb-navigation">
            <a href="index.php?page=login" data-ma-action="login-switch" data-ma-block="#l-login">
                <i class="zmdi zmdi-sign-in"></i> <span>Sign In</span>
            </a>
        </div>
    </div>

    <!-- Forgot Password Block -->
    <div class="lc-block" id="l-forget-password">
        <div class="lcb-form">
            <div class="text-center">
                <i class="zmdi zmdi-help zmdi-hc-5x"></i>
                <h2 class="m-t-20">Forgot Password?</h2>
                <p class="m-b-20">Enter your email address to reset your password</p>
            </div>

            <form action="functions/forgot_password.php" method="POST">
                <div class="form-group fg-line">
                    <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block waves-effect">
                    <i class="zmdi zmdi-email"></i> Reset Password
                </button>
            </form>
        </div>

        <div class="lcb-navigation">
            <a href="index.php?page=login" data-ma-action="login-switch" data-ma-block="#l-login">
                <i class="zmdi zmdi-sign-in"></i> <span>Sign In</span>
            </a>
        </div>
    </div>
</div> 