<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <body style="margin:0;padding:0;background:#f4f7fb;font-family:Roboto,Arial,sans-serif;color:#1e293b;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 40px rgba(15,23,42,0.08);">
                        <tr>
                            <td style="background:#0033a0;padding:20px 28px;color:#fff;font-size:18px;font-weight:700;">Travelera</td>
                        </tr>
                        <tr>
                            <td style="padding:28px;">
                                <p style="margin:0 0 12px;font-size:16px;">Your verification code is</p>
                                <p style="margin:0 0 20px;font-size:32px;letter-spacing:8px;font-weight:700;color:#0033a0;">{{ $code }}</p>
                                <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;">This code expires in {{ config('otp.expire_minutes', 10) }} minutes. If you did not request it, you can ignore this email.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
