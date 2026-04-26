<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #0a0e1a; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #0a0e1a; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellspacing="0" cellpadding="0"
                    style="background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.03) 100%); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 40px 20px; text-align: center;">
                            <h1
                                style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">
                                🏏 Bid<span style="color: #00e676;">Buzz</span>
                            </h1>
                            <p
                                style="margin: 8px 0 0; font-size: 13px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 2px;">
                                Own the Game. Win the Bid.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <div
                                style="height: 1px; background: linear-gradient(to right, transparent, rgba(0,230,118,0.3), transparent);">
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 40px;">
                            <p
                                style="margin: 0 0 20px; font-size: 15px; color: rgba(255,255,255,0.8); line-height: 1.6;">
                                Use the verification code below to sign in to your BidBuzz account.
                            </p>

                            <!-- OTP Code -->
                            <div
                                style="text-align: center; padding: 24px; background: rgba(0,230,118,0.06); border: 1px solid rgba(0,230,118,0.15); border-radius: 12px; margin: 0 0 20px;">
                                <p
                                    style="margin: 0 0 8px; font-size: 12px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">
                                    Verification Code
                                </p>
                                <p
                                    style="margin: 0; font-size: 36px; font-weight: 700; color: #00e676; letter-spacing: 8px; font-family: 'Courier New', monospace;">
                                    {{ $otp }}
                                </p>
                            </div>

                            <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.4); line-height: 1.6;">
                                This code expires in <strong style="color: rgba(255,255,255,0.6);">5 minutes</strong>.
                                If you didn't request this code, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px 28px;">
                            <div
                                style="height: 1px; background: linear-gradient(to right, transparent, rgba(255,255,255,0.06), transparent); margin-bottom: 20px;">
                            </div>
                            <p style="margin: 0; font-size: 12px; color: rgba(255,255,255,0.25); text-align: center;">
                                &copy; {{ date('Y') }} BidBuzz. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>