<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <body style="margin:0;padding:0;background:#f4f7fb;font-family:Roboto,Arial,sans-serif;color:#1e293b;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 40px rgba(15,23,42,0.08);">
                        <tr>
                            <td style="background:#0033a0;padding:20px 28px;color:#fff;font-size:18px;font-weight:700;">Travelera · New query</td>
                        </tr>
                        <tr>
                            <td style="padding:28px;">
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">A new {{ strtolower($query->intentLabel()) }} was submitted from the website.</p>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.7;">
                                    <tr><td style="color:#64748b;width:120px;padding:4px 0;">Type</td><td style="font-weight:600;">{{ $query->intentLabel() }}</td></tr>
                                    <tr><td style="color:#64748b;padding:4px 0;">Name</td><td style="font-weight:600;">{{ $query->name ?: '—' }}</td></tr>
                                    <tr><td style="color:#64748b;padding:4px 0;">Email</td><td style="font-weight:600;">{{ $query->email }}</td></tr>
                                    <tr><td style="color:#64748b;padding:4px 0;">Phone</td><td style="font-weight:600;">{{ $query->phone ?: '—' }}</td></tr>
                                    <tr><td style="color:#64748b;padding:4px 0;vertical-align:top;">Message</td><td style="font-weight:600;white-space:pre-wrap;">{{ $query->message ?: '—' }}</td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
