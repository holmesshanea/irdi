<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Landowner Introduction — {{ $profile->publicName() }}</title>
    <style>
        body { font: 16px/1.55 system-ui, sans-serif; color: #18372d; margin: 0; background: #f2f4f1; }
        main { max-width: 720px; margin: 2rem auto; background: white; padding: 3rem; border-top: 8px solid #b99142; }
        h1 { font-size: 2rem; line-height: 1.2; } h2 { margin-top: 1.8rem; }
        .eyebrow { text-transform: uppercase; letter-spacing: .12em; color: #806527; font-weight: bold; }
        .qr { display: flex; align-items: center; gap: 2rem; margin: 2rem 0; }
        .qr svg { flex-shrink: 0; } a { color: #205743; overflow-wrap: anywhere; }
        .small { font-size: .86rem; color: #4b6259; } .toolbar { text-align: center; padding: 1rem; }
        button { background: #205743; color: white; border: 0; padding: .8rem 1.2rem; cursor: pointer; }
        @media print { @page { size: letter; margin: .6in; } body { background: white; } main { padding: 0; margin: 0; border-top-width: 5px; } .toolbar { display: none; } }
        @media (max-width: 600px) { main { padding: 1.5rem; margin: 0; } .qr { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print or Save as PDF</button></div>
    <main>
        <p class="eyebrow">International Responsible Detectorists Institute</p>
        <h1>A respectful request for permission to metal detect</h1>
        <p>Hello,</p>
        <p>My name is <strong>{{ $profile->publicName() }}</strong>. I enjoy metal detecting and am writing to respectfully ask whether you would consider allowing me to detect on your property, under conditions you choose.</p>
        <p>I recognize that access to private property is a privilege, never an entitlement. I am committed to asking permission first, respecting boundaries and restrictions, caring for the land, and following applicable laws and responsible recovery practices.</p>
        <h2>Review my IRDI public profile</h2>
        <p>You can scan this QR code to see my current public IRDI profile, membership standing, and any available published landowner feedback. Membership is not a guarantee of conduct or an endorsement of access.</p>
        <div class="qr"><div aria-label="QR code linking to public member profile">{!! $qrCode !!}</div><div><strong>{{ $profile->publicName() }}</strong><br><a href="{{ route('member-profiles.show', $profile) }}">{{ route('member-profiles.show', $profile) }}</a><p class="small">Current standing is shown on the live profile, not certified by this printed flyer.</p></div></div>
        <h2>Your property, your decision</h2>
        <p>You are under no obligation to grant permission. If you are willing to discuss a visit, we can agree on where, when, and under what conditions detecting may take place before any activity begins. Please do not consider this flyer itself a grant of permission.</p>
        <p>Thank you for considering my request.</p>
        <p>Sincerely,<br><strong>{{ $profile->publicName() }}</strong></p>
        <hr><p class="small">IRDI encourages ethical metal detecting, historical preservation, and respect for landowners. Learn more at <a href="{{ route('code-of-ethics') }}">{{ route('code-of-ethics') }}</a>.</p>
    </main>
</body>
</html>
