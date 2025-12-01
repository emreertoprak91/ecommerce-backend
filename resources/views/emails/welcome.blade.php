<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to E-Commerce</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white !important;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #5a67d8;
        }
        .footer {
            background: #374151;
            color: #9ca3af;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛒 Welcome to E-Commerce!</h1>
        <p>Your account has been created successfully</p>
    </div>

    <div class="content">
        <h2>Hi {{ $userName }}! 👋</h2>

        <p>Thank you for registering at our e-commerce platform. We're excited to have you on board!</p>

        <div class="info-box">
            <strong>Your Account Details:</strong>
            <ul>
                <li><strong>Name:</strong> {{ $userName }}</li>
                <li><strong>Email:</strong> {{ $userEmail }}</li>
                <li><strong>Registered:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</li>
            </ul>
        </div>

        <p>Please verify your email address by clicking the button below:</p>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">✅ Verify Email Address</a>
        </div>

        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; background: #e5e7eb; padding: 10px; border-radius: 5px; font-size: 12px;">
            {{ $verificationUrl }}
        </p>

        <p><strong>What's next?</strong></p>
        <ul>
            <li>✅ Verify your email address</li>
            <li>🔐 Complete your profile</li>
            <li>🛍️ Start shopping!</li>
        </ul>

        <p>If you didn't create this account, please ignore this email.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} E-Commerce. All rights reserved.</p>
        <p>This email was sent to {{ $userEmail }}</p>
        <p>
            <a href="#" style="color: #9ca3af;">Unsubscribe</a> |
            <a href="#" style="color: #9ca3af;">Privacy Policy</a> |
            <a href="#" style="color: #9ca3af;">Terms of Service</a>
        </p>
    </div>
</body>
</html>
