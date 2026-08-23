<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>New IRDI Contact Message</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: Arial, Helvetica, sans-serif; color: #27272a;">

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    role="presentation"
    style="background-color: #f4f4f5; padding: 40px 20px;"
>
    <tr>
        <td align="center">

            <table
                width="600"
                cellpadding="0"
                cellspacing="0"
                role="presentation"
                style="width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden;"
            >

                {{-- Header --}}
                <tr>
                    <td style="background-color: #174121; padding: 28px 32px; text-align: center;">
                        <div style="font-size: 26px; font-weight: bold; color: #ffffff;">
                            IRDI
                        </div>

                        <div style="margin-top: 6px; font-size: 14px; color: #ffffff;">
                            International Responsible Detectorist Institute
                        </div>
                    </td>
                </tr>

                {{-- Gold accent --}}
                <tr>
                    <td style="height: 5px; background-color: #C28B08;"></td>
                </tr>

                {{-- Content --}}
                <tr>
                    <td style="padding: 32px;">

                        <h1 style="margin: 0 0 16px; font-size: 22px; color: #174121;">
                            New Contact Message
                        </h1>

                        <p style="margin: 0 0 24px; line-height: 1.6; color: #52525b;">
                            A new message has been submitted through the IRDI website contact form.
                        </p>

                        {{-- Contact information --}}
                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            role="presentation"
                            style="background-color: #f4f4f5; border-left: 4px solid #C28B08;"
                        >
                            <tr>
                                <td style="padding: 20px;">

                                    <p style="margin: 0 0 12px;">
                                        <strong>Name:</strong>
                                        {{ $name }}
                                    </p>

                                    <p style="margin: 0 0 12px;">
                                        <strong>Email:</strong>
                                        {{ $email }}
                                    </p>

                                    <p style="margin: 0;">
                                        <strong>Subject:</strong>
                                        {{ $contactSubject }}
                                    </p>

                                </td>
                            </tr>
                        </table>

                        <h2 style="margin: 30px 0 12px; font-size: 18px; color: #174121;">
                            Message
                        </h2>

                        <div style="line-height: 1.7; color: #3f3f46;">
                            {!! nl2br(e($body)) !!}
                        </div>

                        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e4e4e7;">

                        <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #71717a;">
                            You can reply directly to this email to respond to
                            <strong>{{ $name }}</strong>.
                        </p>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding: 20px 32px; text-align: center; background-color: #fafafa; font-size: 12px; color: #71717a;">
                        IRDI — International Responsible Detectorist Institute
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
