@php $visit = $share->surveyVisit; $college = $share->college; @endphp
<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:0;background:#f1f4f1;font-family:Segoe UI,Arial,sans-serif;color:#1c2a20">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f1;padding:28px 12px">
    <tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #dfe7e0">
            <tr>
                <td style="background:#0f4a24;padding:22px 28px;color:#ffffff">
                    <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#c9e3cf">CPSU · Office of Quality Assurance</div>
                    <div style="font-size:20px;font-weight:700;margin-top:4px">{{ $fresh ? 'Your new access code' : 'Accreditation documents shared with you' }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:26px 28px 8px;font-size:15px;line-height:1.6">
                    <p style="margin:0 0 14px">Good day, {{ $share->name }}.</p>
                    @if ($fresh)
                        <p style="margin:0 0 14px">Here is a new one-time code for the documents of <b>{{ $college->name }}</b> under <b>{{ $visit->name }}</b>.</p>
                    @else
                        <p style="margin:0 0 14px">The Office of Quality Assurance of Central Philippines State University has given you read-only access to the evidence filed by <b>{{ $college->name }}</b> for the <b>{{ $visit->name }}</b>.</p>
                        @if ($share->note)<p style="margin:0 0 14px;padding:10px 14px;background:#f4f7f4;border-left:3px solid #1f7a3a;border-radius:4px">{{ $share->note }}</p>@endif
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding:6px 28px 6px">
                    <div style="font-size:12px;color:#5b6b60;text-transform:uppercase;letter-spacing:.08em">One-time code</div>
                    <div style="font-size:32px;font-weight:700;letter-spacing:.3em;font-family:Consolas,monospace;color:#0f4a24;margin:4px 0 2px">{{ $code }}</div>
                    <div style="font-size:13px;color:#5b6b60">Works once and expires in {{ \App\Models\VisitShare::OTP_MINUTES }} minutes. You can request a new code from the link page.</div>
                </td>
            </tr>
            <tr>
                <td style="padding:22px 28px 8px">
                    <a href="{{ $link }}" style="display:inline-block;background:#1f7a3a;color:#ffffff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:8px">Open the shared documents</a>
                </td>
            </tr>
            <tr>
                <td style="padding:16px 28px 26px;font-size:13px;line-height:1.6;color:#5b6b60">
                    Access ends on <b>{{ $share->expires_at->format('F j, Y') }}</b>.<br>
                    These files are confidential. Please don't forward this email; the link only works together with a code sent to this address.
                </td>
            </tr>
            <tr>
                <td style="padding:14px 28px;background:#f4f7f4;border-top:1px solid #dfe7e0;font-size:12px;line-height:1.5;color:#6b7a70">
                    This is an automated message from the CPSU Online Accreditation System. Please do not reply. This mailbox is not monitored.
                    For questions, contact the CPSU Office of Quality Assurance.
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
