<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <body style="margin:0;padding:0;background:#f4f7fb;font-family:Roboto,Arial,sans-serif;color:#1e293b;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 40px rgba(15,23,42,0.08);">
                        <tr>
                            <td style="background:#0033a0;padding:20px 28px;color:#fff;font-size:18px;font-weight:700;">Travelera</td>
                        </tr>
                        <tr>
                            <td style="padding:28px;">
                                <p style="margin:0 0 8px;font-size:20px;font-weight:700;">
                                    {{ $booking->payment_status === 'pending' ? 'Your trip is on hold' : 'Your booking is confirmed' }}
                                </p>
                                <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#64748b;">
                                    Hi {{ $booking->guest_name }}, here are the details for
                                    <strong style="color:#0f172a;">{{ $booking->booking_reference }}</strong>.
                                </p>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.7;background:#f8fafc;border-radius:12px;padding:8px 0;">
                                    <tr><td style="padding:8px 16px;color:#64748b;">Trip</td><td style="padding:8px 16px;font-weight:600;">{{ $booking->title() }}</td></tr>
                                    @if ($booking->departureAt())
                                        <tr><td style="padding:8px 16px;color:#64748b;">Departure</td><td style="padding:8px 16px;font-weight:600;">{{ $booking->departureAt()->format('D, d M Y · H:i') }}</td></tr>
                                    @endif
                                    @if ($booking->arrivalAt())
                                        <tr><td style="padding:8px 16px;color:#64748b;">Arrival</td><td style="padding:8px 16px;font-weight:600;">{{ $booking->arrivalAt()->format('D, d M Y · H:i') }}</td></tr>
                                    @endif
                                    <tr><td style="padding:8px 16px;color:#64748b;">Travelers</td><td style="padding:8px 16px;font-weight:600;">{{ $booking->travelers }}</td></tr>
                                    @if ($booking->airline_pnr)
                                        <tr><td style="padding:8px 16px;color:#64748b;">Airline PNR</td><td style="padding:8px 16px;font-weight:600;">{{ $booking->airline_pnr }}</td></tr>
                                    @endif
                                    <tr><td style="padding:8px 16px;color:#64748b;">Total</td><td style="padding:8px 16px;font-weight:600;">{{ strtoupper($booking->currency ?? 'INR') }} {{ number_format((float) $booking->total_amount, 2) }}</td></tr>
                                    <tr><td style="padding:8px 16px;color:#64748b;">Payment</td><td style="padding:8px 16px;font-weight:600;text-transform:capitalize;">{{ $booking->payment_status }}</td></tr>
                                </table>
                                <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                    Need help? Email
                                    <a href="mailto:support@travelera.us" style="color:#0033a0;">support@travelera.us</a>
                                    or call <a href="tel:+18886526415" style="color:#0033a0;">+1 888 652 6415</a>.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
