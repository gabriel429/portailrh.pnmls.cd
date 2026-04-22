<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f8fb;font-family:Arial,Helvetica,sans-serif;color:#123;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #d8e8f3;box-shadow:0 12px 34px rgba(2,49,79,.08);">
        <div style="padding:24px 28px;background:linear-gradient(135deg,#005a87,#0b78ad);color:#fff;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;opacity:.85;">E-PNMLS</div>
            <h1 style="margin:8px 0 0;font-size:24px;line-height:1.2;">{{ $subject }}</h1>
        </div>

        <div style="padding:28px;">
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                Bonjour{{ $recipientName ? ' ' . $recipientName : '' }},
            </p>

            <div style="margin:0 0 18px;padding:18px 20px;background:#f8fcff;border-left:4px solid #0b78ad;border-radius:12px;font-size:15px;line-height:1.7;white-space:pre-line;">
                {{ $body }}
            </div>

            <p style="margin:0 0 8px;font-size:14px;color:#456;">
                Expediteur : <strong>{{ $senderName }}</strong>
            </p>
            @if ($senderEmail)
                <p style="margin:0 0 8px;font-size:14px;color:#456;">
                    Repondre a : <strong>{{ $senderEmail }}</strong>
                </p>
            @endif
            @if ($attachmentName)
                <p style="margin:18px 0 0;font-size:14px;color:#456;">
                    Piece jointe : <strong>{{ $attachmentName }}</strong>
                </p>
            @endif
        </div>
    </div>
</body>
</html>
