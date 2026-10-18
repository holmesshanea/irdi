<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Landowner Introduction &amp; Permission Packet — {{ $profile->publicName() }}</title>
    <style>
        body { font: 16px/1.55 system-ui, sans-serif; color: #18372d; margin: 0; background: #f2f4f1; }
        main { max-width: 720px; margin: 2rem auto; background: white; padding: 3rem; border-top: 8px solid #b99142; }
        h1 { font-size: 2rem; line-height: 1.2; } h2 { margin-top: 1.8rem; }
        .eyebrow { text-transform: uppercase; letter-spacing: .12em; color: #806527; font-weight: bold; }
        .qr { display: flex; align-items: center; gap: 2rem; margin: 2rem 0; }
        .qr svg { flex-shrink: 0; } a { color: #205743; overflow-wrap: anywhere; }
        .small { font-size: .86rem; color: #4b6259; } .toolbar { text-align: center; padding: 1rem; }
        button { background: #205743; color: white; border: 0; padding: .8rem 1.2rem; cursor: pointer; }
        .agreement { margin-top: 2rem; } .agreement h1 { font-size: 1.6rem; } .agreement h2 { margin-top: 1rem; font-size: 1rem; } .agreement p { margin: .5rem 0; } .fill-line { display: inline-block; border-bottom: 1px solid #35483e; min-width: 180px; height: 1.25em; vertical-align: bottom; } .wide-line { display: block; border-bottom: 1px solid #35483e; height: 1.5em; margin-bottom: .4rem; } .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 1.4rem; margin-top: 1.5rem; } .signature-line { border-top: 1px solid #35483e; padding-top: .25rem; margin-top: 1.5rem; font-size: .85rem; }
        @media print { @page { size: letter; margin: .48in; } body { background: white; font-size: 11pt; line-height: 1.35; } main { padding: 0; margin: 0; border-top-width: 5px; max-width: none; } .toolbar { display: none; } .introduction { break-after: page; page-break-after: always; } .agreement { margin-top: 0; } h1 { font-size: 1.55rem; } h2 { font-size: 1.05rem; margin-top: 1rem; } .qr { margin: 1rem 0; } .qr svg { max-width: 145px; max-height: 145px; } .agreement { font-size: 9pt; line-height: 1.18; } .agreement h1 { font-size: 1.3rem; } .agreement h2 { margin-top: .42rem; margin-bottom: .25rem; } .agreement p { margin: .22rem 0; } .agreement .wide-line { height: 1.05em; margin-bottom: .2rem; } .agreement .signatures { margin-top: .8rem; } .agreement .signature-line { margin-top: 1.1rem; } .agreement .eyebrow { margin-top: 0; } }
        @media (max-width: 600px) { main { padding: 1.5rem; margin: 0; } .qr { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print or Save as PDF — Introduction &amp; Permission Packet</button></div>
    <main class="introduction">
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
    <main class="agreement" aria-label="Landowner Metal Detecting Permission Agreement">
        <p class="eyebrow">International Responsible Detectorists Institute · Companion Document</p>
        <h1>Landowner Metal Detecting Permission Agreement</h1>
        <p class="small"><strong>Complete together in person.</strong> This document is separate from the introduction flyer. No permission is granted unless an authorized property representative and the detectorist agree and sign below. Print two copies, or make a copy of the fully signed agreement for each party.</p>
        <h2>1. People and property</h2>
        <p><strong>Detectorist:</strong> {{ $profile->publicName() }} &nbsp; <strong>IRDI profile:</strong> {{ $profile->username }}</p>
        <p><strong>Detectorist contact:</strong> <span class="fill-line" style="min-width: 280px"></span></p>
        <p><strong>Landowner / authorized representative:</strong> <span class="fill-line" style="min-width: 280px"></span></p>
        <p><strong>Landowner contact (optional):</strong> <span class="fill-line" style="min-width: 280px"></span></p>
        <p><strong>Property location / description:</strong></p><span class="wide-line"></span>
        <p><strong>Areas expressly permitted (attach a sketch if useful):</strong></p><span class="wide-line"></span>
        <h2>2. Dates, hours, and activities</h2>
        <p><strong>Permission begins:</strong> <span class="fill-line"></span> &nbsp; <strong>Ends:</strong> <span class="fill-line"></span></p>
        <p><strong>Permitted days / hours:</strong> <span class="fill-line" style="min-width: 340px"></span></p>
        <p><strong>Permitted detecting and recovery methods:</strong></p><span class="wide-line"></span>
        <h2>3. Conditions and restricted areas</h2>
        <p>Discuss access routes, gates, livestock, crops, lawns, structures, holes and ground restoration, guests, and any off-limits areas. Record the agreed conditions here:</p>
        <span class="wide-line"></span><span class="wide-line"></span><span class="wide-line"></span>
        <h2>4. Finds and legal responsibilities</h2>
        <p><strong>Agreed handling and ownership of finds:</strong></p><span class="wide-line"></span>
        <p>Both parties acknowledge that applicable property, heritage, archaeological, environmental, and reporting laws take precedence over this agreement. No activity is authorized where prohibited by law. The detectorist agrees to respect boundaries, restore disturbed ground, and stop upon request.</p>
        <h2>5. Permission and withdrawal</h2>
        <p>The undersigned landowner or authorized representative confirms their authority to permit the described activity, subject to the limits above. Permission is nonexclusive, limited to the named detectorist and agreed dates/areas, and may be withdrawn at any time. This agreement does not itself establish land ownership, waive legal rights, or certify insurance or liability coverage. IRDI is not a party to this agreement and does not grant access or independently verify the signers' identities or authority.</p>
        <div class="signatures"><div><div class="signature-line">Landowner / authorized representative signature</div><p>Date: <span class="fill-line"></span></p></div><div><div class="signature-line">Detectorist signature</div><p>Date: <span class="fill-line"></span></p></div></div>
        <p class="small">Keep a signed copy with each party. Reconfirm permission before returning. For complex ownership, liability, or local legal issues, seek appropriate advice before signing.</p>
    </main>
</body>
</html>
