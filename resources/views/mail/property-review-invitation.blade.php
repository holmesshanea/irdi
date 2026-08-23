<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRDI Property Owner Feedback</title>
</head>

<body style="margin:0; padding:0; background:#f4f4f5; font-family:Arial, sans-serif; color:#27272a;">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:40px 20px;">

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                role="presentation"
                style="max-width:600px; background:#ffffff; border-radius:12px;"
            >

                <tr>
                    <td style="padding:40px;">

                        <h1 style="margin:0; color:#174121; font-size:26px; text-align:center;">
                            Property Owner Feedback
                        </h1>

                        <p style="margin:30px 0 0; font-size:16px; line-height:1.6;">
                            {{ $profile->profile_name }} has invited you to provide feedback
                            about your experience with them as an IRDI Detectorist.
                        </p>

                        <p style="margin:20px 0 0; font-size:16px; line-height:1.6;">
                            You do not need an IRDI account to participate.
                        </p>

                        <p style="margin:20px 0 0; font-size:16px; line-height:1.6;">
                            Before submitting feedback, IRDI will ask you to verify this email address.
                            Your email address will remain private and will not be displayed publicly.
                        </p>

                        <div style="margin:32px 0; text-align:center;">
                            <a
                                href="{{ $reviewUrl }}"
                                style="
                                    display:inline-block;
                                    padding:14px 24px;
                                    background:#174121;
                                    color:#ffffff;
                                    text-decoration:none;
                                    border-radius:8px;
                                    font-weight:bold;
                                "
                            >
                                Leave Property Owner Feedback
                            </a>
                        </div>

                        <p style="margin:20px 0 0; font-size:14px; line-height:1.6; color:#71717a;">
                            This invitation can only be used once and expires {{ $expiresAt }}.
                        </p>

                        <p style="margin:20px 0 0; font-size:14px; line-height:1.6; color:#71717a;">
                            If you were not expecting this invitation, you may simply ignore this email.
                        </p>

                        <hr style="margin:32px 0; border:0; border-top:1px solid #e4e4e7;">

                        <p style="margin:0; font-size:12px; line-height:1.6; color:#71717a; text-align:center;">
                            IRDI — International Responsible Detectorist Institute
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
