<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailSubject }}</title>
</head>
<body style="margin:0;background:#f6f5f3;color:#14182b;font-family:Inter,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f5f3;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;">
                    <tr>
                        <td style="padding:28px 28px 8px;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;color:#475569;">PaySomeoneToWrite</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 0;font-size:28px;line-height:1.25;font-weight:600;color:#0b1023;">{{ $heading }}</td>
                    </tr>
                    @foreach ($lines as $line)
                        <tr>
                            <td style="padding:12px 28px 0;font-size:16px;line-height:1.55;color:#334155;">{{ $line }}</td>
                        </tr>
                    @endforeach
                    @if ($actionUrl)
                        <tr>
                            <td style="padding:24px 28px 0;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#0b1023;color:#ffffff;text-decoration:none;border-radius:999px;padding:12px 22px;font-size:15px;font-weight:600;">{{ $actionLabel }}</a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:28px;font-size:13px;line-height:1.5;color:#64748b;">
                            @if ($essential)
                                This message is about your account security or a payment. It is sent even if you have turned other email off.
                            @else
                                You can turn this kind of email off.
                                <a href="{{ $preferencesUrl }}" style="color:#0b1023;">Email preferences</a>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
