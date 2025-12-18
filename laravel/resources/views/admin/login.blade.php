<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="MyCities Admin Portal">
    <meta name="author" content="MyCities">

    <title>MyCities - Admin Login</title>

    <!-- Custom fonts -->
    <link href="{{ url('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a252f 0%, #2c3e50 50%, #34495e 100%);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 40px 40px 30px;
            text-align: center;
        }

        .login-header img {
            max-width: 180px;
            height: auto;
            margin-bottom: 15px;
        }

        .login-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-top: 8px;
        }

        .login-body {
            padding: 40px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 18px 20px;
            font-size: 17px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            color: #1f2937;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #2980b9;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(41, 128, 185, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
            font-size: 16px;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 5px;
            font-size: 18px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #2980b9;
        }

        .form-input.has-toggle {
            padding-right: 55px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #2980b9;
            cursor: pointer;
        }

        .checkbox-wrapper label {
            font-size: 15px;
            color: #4b5563;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            font-size: 17px;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, #2980b9 0%, #1c5a85 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1c5a85 0%, #164466 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(41, 128, 185, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 28px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            padding: 0 15px;
            color: #9ca3af;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .forgot-link {
            display: block;
            text-align: center;
            color: #2980b9;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #1c5a85;
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            padding: 20px 40px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .login-footer p {
            color: #6b7280;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 25px 25px;
            }
            
            .login-body {
                padding: 30px 25px;
            }
            
            .form-input {
                padding: 16px 18px;
                font-size: 16px;
            }
            
            .btn-login {
                padding: 16px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="{{ \Illuminate\Support\Facades\URL::to(\Illuminate\Support\Facades\Storage::url('public/images/logo.PNG')) }}" alt="MyCities Logo" onerror="this.style.display='none'">
                <h1>Administrator Portal</h1>
                <p>Sign in to manage MyCities</p>
            </div>

            <div class="login-body">
                @if(\Illuminate\Support\Facades\Session::has('alert-message'))
                    <div class="alert {{ \Illuminate\Support\Facades\Session::get('alert-class', 'alert-info') }}">
                        {{ \Illuminate\Support\Facades\Session::get('alert-message') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope" style="margin-right: 8px; color: #6b7280;"></i>
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            class="form-input" 
                            placeholder="Enter your email address"
                            required 
                            autocomplete="email"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock" style="margin-right: 8px; color: #6b7280;"></i>
                            Password
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                class="form-input has-toggle" 
                                placeholder="Enter your password"
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()" title="Show/Hide Password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="remember" id="remember">
                            <label for="remember">Remember me on this device</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt" style="margin-right: 10px;"></i>
                        Sign In
                    </button>
                </form>

                <div class="divider">
                    <span>or</span>
                </div>

                <a href="{{ route('admin.forgot-password') }}" class="forgot-link">
                    <i class="fas fa-key" style="margin-right: 8px;"></i>
                    Forgot your password?
                </a>
            </div>

            <div class="login-footer">
                <p>&copy; {{ date('Y') }} MyCities. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>
