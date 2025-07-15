<?php
session_start();

// MySQL database connection
$conn = mysqli_connect("localhost:3307", "root", "", "paas_portal");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['new_user'])) {
        // New user registration
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $created_at = date('Y-m-d H:i:s'); // Current timestamp

        if ($password === $confirm_password) {
            // Store password as plain text (not recommended)
            $plain_password = $password;
            
            $sql = "INSERT INTO users (full_name, username, email, password, created_at) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            
            // Bind null for alterpassword initially (can be updated later)
            $alterpassword = null;
            mysqli_stmt_bind_param($stmt, "sssss", $full_name, $username, $email, 
                                  $plain_password, $created_at);
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                echo "<script>alert('New account created successfully! You can now log in.');</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('Passwords do not match. Please try again.');</script>";
        }
    } elseif (isset($_POST['signin'])) {
        // Existing user login
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Compare plain text passwords (not recommended)
            if ($password === $row['password']) {
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $row['id'];
                header("Location: loginn.php");
                exit();
            } else {
                echo "<script>alert('Invalid username or password.');</script>";
            }
        } else {
            echo "<script>alert('Invalid username or password.');</script>";
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $email = $_POST['email'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password === $confirm_password) {
            // Store new password as plain text (not recommended)
            $plain_password = $new_password;
            
            $sql = "UPDATE users SET password = ? WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $plain_password, $email);
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                echo "<script>alert('Password updated successfully.');</script>";
            } else {
                echo "<script>alert('Error updating password: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('Passwords do not match. Please try again.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - PaaS Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
        }

        .close-btn:hover {
            color: #495057;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #212529;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .register-link, .login-link {
            color: #6c757d;
            font-size: 14px;
        }

        .register-link a, .login-link a {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
        }

        .register-link a:hover, .login-link a:hover {
            text-decoration: underline;
        }

        .google-btn {
            width: 100%;
            padding: 12px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            color: #3c4043;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 25px;
        }

        .google-btn:hover {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-color: #c6c6c6;
        }

        .google-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .google-icon {
            width: 18px;
            height: 18px;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .microsoft-btn {
            background: #00bcf2;
            color: white;
        }

        .divider {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            margin: 25px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            z-index: 2;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #212529;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: white;
        }

        .form-control::placeholder {
            color: #adb5bd;
        }

        .form-control:disabled {
            background: #e9ecef;
            opacity: 0.7;
        }

        .otp-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }

        .otp-input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            border: 2px solid #ced4da;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: white;
        }

        .otp-input.filled {
            border-color: #28a745;
            background: #f8fff9;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
        }

        .remember-me input[type="checkbox"] {
            margin: 0;
        }

        .forgot-password {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .otp-timer {
            color: #6c757d;
            font-size: 14px;
            text-align: center;
            margin: 10px 0;
        }

        .resend-otp {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .resend-otp:hover {
            text-decoration: underline;
        }

        .resend-otp:disabled {
            color: #6c757d;
            cursor: not-allowed;
            text-decoration: none;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 20px;
        }

        .submit-btn:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .otp-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-left: 10px;
        }

        .otp-btn:hover {
            background: #0056b3;
        }

        .otp-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .email-otp-container {
            display: flex;
            align-items: center;
        }

        .email-otp-container .form-control {
            flex: 1;
        }

        .terms {
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            line-height: 1.4;
        }

        .terms a {
            color: #007bff;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .hidden {
            display: none;
        }

        .user-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #0056b3;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Password input container */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        /* Password input field styling */
        .password-input-container input {
            width: 100%;
            padding-right: 45px; /* Make space for the eye icon */
            padding-left: 16px;
            padding-top: 12px;
            padding-bottom: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .password-input-container input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Password toggle button styling */
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .password-toggle-btn:hover {
            background-color: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .password-toggle-btn:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }

        .password-toggle-btn svg {
            width: 18px;
            height: 18px;
            stroke-width: 2;
        }

        /* Make sure the button doesn't interfere with input focus */
        .password-input-container input:focus + .password-toggle-btn {
            color: #667eea;
        }

        .back-to-login {
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }

        .back-to-login:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .header h1 {
                font-size: 28px;
            }

            .otp-input {
                width: 40px;
                height: 45px;
                font-size: 16px;
            }

            .email-otp-container {
                flex-direction: column;
                gap: 10px;
            }

            .email-otp-container .form-control {
                margin-bottom: 0;
            }

            .otp-btn {
                margin-left: 0;
                width: 100%;
            }

            .password-input-container input {
                font-size: 16px; /* Prevent zoom on iOS */
                padding-right: 40px;
            }
            
            .password-toggle-btn {
                right: 10px;
                padding: 6px;
            }
            
            .password-toggle-btn svg {
                width: 16px;
                height: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Login Form -->
        <div id="loginForm" class="form-section">
            <div class="header">
                <h1>Log in</h1>
                <div class="register-link">
                    New user? <a id="showSignupBtn">Register Now</a>
                </div>
            </div>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-container">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control"
                            placeholder="Enter your username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle-btn" id="loginPasswordToggle" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" id="rememberMe" checked>
                        Remember Me
                    </label>
                    <a href="#" class="forgot-password" id="forgotPasswordLink">Forgot Password?</a>
                </div>
                
                <button type="submit" name="signin" class="submit-btn" id="loginSubmitBtn">Sign In</button>
            </form>

            <div class="terms">
                By signing in, you agree to our 
                <a href="#" id="privacyPolicy">Privacy Policy</a> & 
                <a href="#" id="cookiePolicy">Cookie Policy</a>.
            </div>
        </div>
            
        <!-- Sign Up Form -->
        <div id="signupForm" class="form-section hidden">
            <div class="header">
                <h1>Sign Up</h1>
                <div class="login-link">
                    Already have an account? <a id="showLoginBtn">Log in</a>
                </div>
            </div>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="new_user" value="1">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create password" required>
                        <button type="button" class="password-toggle-btn" id="signupPasswordToggle" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                </div>
                
                <button type="submit" class="submit-btn">Sign Up</button>
            </form>
        </div>

        <!-- Forgot Password Form -->
        <div id="forgotPasswordForm" class="form-section hidden">
            <div class="header">
                <a href="#" class="back-to-login" id="backToLoginBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Login
                </a>
                <h1>Set New Password</h1>
                <div class="register-link">
                    Enter your new password below
                </div>
            </div>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-container">
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                </div>
            
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-container">
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        <button type="button" class="password-toggle-btn" id="newPasswordToggle" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <button type="button" class="password-toggle-btn" id="confirmNewPasswordToggle" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="updatePasswordBtn">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
       document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');
            
            const showSignupBtn = document.getElementById('showSignupBtn');
            const showLoginBtn = document.getElementById('showLoginBtn');
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            const backToLoginBtn = document.getElementById('backToLoginBtn');
            
            // Password toggle buttons
            const loginPasswordToggle = document.getElementById('loginPasswordToggle');
            const signupPasswordToggle = document.getElementById('signupPasswordToggle');
            const newPasswordToggle = document.getElementById('newPasswordToggle');
            const confirmNewPasswordToggle = document.getElementById('confirmNewPasswordToggle');
            
            // Form switching functions
            function showLogin() {
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                forgotPasswordForm.classList.add('hidden');
            }
            
            function showSignup() {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                forgotPasswordForm.classList.add('hidden');
            }
            
            function showForgotPassword() {
                loginForm.classList.add('hidden');
                signupForm.classList.add('hidden');
                forgotPasswordForm.classList.remove('hidden');
            }
            
            // Event listeners for form switching
            showSignupBtn.addEventListener('click', showSignup);
            showLoginBtn.addEventListener('click', showLogin);
            forgotPasswordLink.addEventListener('click', showForgotPassword);
            backToLoginBtn.addEventListener('click', showLogin);
            
            // Password toggle functionality
            function togglePassword(inputId, toggleBtn) {
                const passwordInput = document.getElementById(inputId);
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    `;
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    `;
                }
            }
            
            loginPasswordToggle.addEventListener('click', () => togglePassword('password', loginPasswordToggle));
            signupPasswordToggle.addEventListener('click', () => togglePassword('password', signupPasswordToggle));
            newPasswordToggle.addEventListener('click', () => togglePassword('new_password', newPasswordToggle));
            confirmNewPasswordToggle.addEventListener('click', () => togglePassword('confirm_password', confirmNewPasswordToggle));
            
            // Initialize with login form visible
            showLogin();
        });
    </script>
</body>
</html>