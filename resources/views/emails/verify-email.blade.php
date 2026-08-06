<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background-color: #2563eb; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 32px 24px; color: #333333; line-height: 1.6; }
        .token-box { background-color: #f0f4ff; border: 1px solid #2563eb; border-radius: 6px; padding: 20px; text-align: center; margin: 24px 0; }
        .token { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2563eb; }
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
            <p>Thank you for registering with Pharma Plus. Please use the following verification code to verify your email address:</p>
            <div class="token-box">
                <div class="token">{{ $token }}</div>
            </div>
            <p>This code will expire in <strong>24 hours</strong>.</p>
            <p>If you did not create an account, please ignore this email.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Pharma Plus. All rights reserved.
        </div>
    </div>
</body>
</html>
