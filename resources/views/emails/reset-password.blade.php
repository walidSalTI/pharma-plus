<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background-color: #dc2626; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 32px 24px; color: #333333; line-height: 1.6; }
        .token-box { background-color: #fef2f2; border: 1px solid #dc2626; border-radius: 6px; padding: 16px; text-align: center; margin: 24px 0; }
        .token { font-size: 24px; font-weight: bold; letter-spacing: 4px; color: #dc2626; }
        .footer { background-color: #f9fafb; padding: 16px 24px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pharma Plus</h1>
        </div>
        <div class="body">
            <p>Hello {{ $user->f_name }},</p>
            <p>We received a request to reset your password. Please use the following code:</p>
            <div class="token-box">
                <div class="token">{{ $token }}</div>
            </div>
            <p>This code will expire in <strong>60 minutes</strong>.</p>
            <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Pharma Plus. All rights reserved.
        </div>
    </div>
</body>
</html>
