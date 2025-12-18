<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="MyCities Admin Password Reset">
    <meta name="author" content="MyCities">

    <title>MyCities - Reset Password</title>

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

        .reset-container {
            width: 100%;
            max-width: 480px;
        }

        .reset-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .reset-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 40px 40px 30px;
            text-align: center;
        }

        .reset-header .icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .reset-header .icon-circle i {
            font-size: 36px;
            color: #ffffff;
        }

        .reset-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .reset-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-top: 8px;
            line-height: 1.5;
        }

        .reset-body {
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

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-box p {
            color: #0369a1;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .info-box strong {
            color: #0c4a6e;
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

        .btn-reset {
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

        .btn-reset:hover {
            background: linear-gradient(135deg, #1c5a85 0%, #164466 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(41, 128, 185, 0.4);
        }

        .btn-reset:active {
            transform: translateY(0);
        }

        .btn-reset:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
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

        .back-link {
            display: block;
            text-align: center;
            color: #2980b9;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #1c5a85;
            text-decoration: underline;
        }

        .reset-footer {
            text-align: center;
            padding: 20px 40px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .reset-footer p {
            color: #6b7280;
            font-size: 13px;
        }

        .success-state {
            text-align: center;
            padding: 30px 0;
        }

        .success-state .icon-circle {
            width: 100px;
            height: 100px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .success-state .icon-circle i {
            font-size: 48px;
            color: #16a34a;
        }

        .success-state h2 {
            color: #1f2937;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .success-state p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .reset-header {
                padding: 30px 25px 25px;
            }
            
            .reset-body {
                padding: 30px 25px;
            }
            
            .form-input {
                padding: 16px 18px;
                font-size: 16px;
            }
            
            .btn-reset {
                padding: 16px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="icon-circle">
                    <i class="fas fa-key"></i>
                </div>
                <h1>Reset Password</h1>
                <p>Enter your admin email to receive password reset instructions</p>
            </div>

            <div class="reset-body">
                @if(\Illuminate\Support\Facades\Session::has('alert-message'))
                    <div class="alert {{ \Illuminate\Support\Facades\Session::get('alert-class', 'alert-info') }}">
                        {{ \Illuminate\Support\Facades\Session::get('alert-message') }}
                    </div>
                @endif

                @if(\Illuminate\Support\Facades\Session::has('success'))
                    <div class="success-state">
                        <div class="icon-circle">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2>Email Sent!</h2>
                        <p>Password reset instructions have been sent to <strong>saleam.essop@gmail.com</strong></p>
                        <p style="margin-top: 15px; font-size: 13px;">Please check your inbox and follow the instructions.</p>
                    </div>
                @else
                    <div class="info-box">
                        <p>
                            <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                            For security, password reset details will be sent to the registered administrator email: <strong>saleam.essop@gmail.com</strong>
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.forgot-password.submit') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope" style="margin-right: 8px; color: #6b7280;"></i>
                                Admin Email Address
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                class="form-input" 
                                placeholder="Enter your admin email"
                                required 
                                autocomplete="email"
                            >
                        </div>

                        <button type="submit" class="btn-reset">
                            <i class="fas fa-paper-plane" style="margin-right: 10px;"></i>
                            Send Reset Instructions
                        </button>
                    </form>
                @endif

                <div class="divider">
                    <span>or</span>
                </div>

                <a href="{{ route('login') }}" class="back-link">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                    Back to Login
                </a>
            </div>

            <div class="reset-footer">
                <p>&copy; {{ date('Y') }} MyCities. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>






