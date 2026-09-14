<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="robots" content="noindex, nofollow">

    <title>{{ __('Scheduled Maintenance | IRDI') }}</title>

    <style>
        :root {
            color-scheme: light;
            --irdi-green: #174c3c;
            --irdi-green-dark: #10382c;
            --irdi-gold: #c6a15b;
            --irdi-gold-light: #ead8b3;
            --irdi-cream: #faf8f2;
            --irdi-text: #27272a;
            --irdi-muted: #62626a;
            --irdi-border: #e4dfd3;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            background: var(--irdi-cream);
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--irdi-text);
            background:
                radial-gradient(
                    circle at top,
                    rgba(198, 161, 91, 0.16),
                    transparent 32rem
                ),
                var(--irdi-cream);
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }

        .container {
            width: 100%;
            max-width: 44rem;
            text-align: center;
        }

        .mark {
            display: inline-flex;
            width: 5rem;
            height: 5rem;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--irdi-gold);
            border-radius: 9999px;
            color: white;
            background: var(--irdi-green);
            box-shadow: 0 12px 30px rgba(16, 56, 44, 0.18);
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.12em;
        }

        .eyebrow {
            margin: 1.75rem 0 0;
            color: #886921;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0.75rem 0 0;
            color: var(--irdi-green);
            font-size: clamp(2.25rem, 7vw, 3.5rem);
            line-height: 1.08;
            letter-spacing: -0.035em;
        }

        .introduction {
            max-width: 38rem;
            margin: 1.5rem auto 0;
            color: var(--irdi-muted);
            font-size: 1.05rem;
            line-height: 1.75;
        }

        .divider {
            width: 4rem;
            height: 0.25rem;
            margin: 2rem auto;
            border-radius: 9999px;
            background: var(--irdi-gold);
        }

        .notice {
            padding: 2rem;
            border: 1px solid var(--irdi-border);
            border-top: 4px solid var(--irdi-gold);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 18px 45px rgba(39, 39, 42, 0.08);
        }

        .notice h2 {
            margin: 0;
            color: var(--irdi-green-dark);
            font-size: 1.35rem;
        }

        .notice p {
            margin: 0.75rem auto 0;
            max-width: 32rem;
            color: var(--irdi-muted);
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .button {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            margin-top: 1.5rem;
            padding: 0.7rem 1.25rem;
            border: 1px solid transparent;
            border-radius: 0.6rem;
            color: white;
            background: var(--irdi-green);
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            transition:
                background-color 150ms ease,
                transform 150ms ease,
                box-shadow 150ms ease;
        }

        .button:hover {
            background: var(--irdi-green-dark);
            box-shadow: 0 8px 20px rgba(16, 56, 44, 0.18);
            transform: translateY(-1px);
        }

        .button:focus-visible {
            outline: 3px solid var(--irdi-gold-light);
            outline-offset: 3px;
        }

        .organization {
            margin: 2rem 0 0;
            color: var(--irdi-muted);
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .organization strong {
            display: block;
            margin-bottom: 0.2rem;
            color: var(--irdi-green);
            font-size: 0.9rem;
        }

        @media (max-width: 40rem) {
            .page {
                padding: 2rem 1rem;
            }

            .mark {
                width: 4.5rem;
                height: 4.5rem;
            }

            .notice {
                padding: 1.5rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .button {
                transition: none;
            }
        }
    </style>
</head>

<body>
<main class="page">
    <div class="container">

        <div class="mark" aria-hidden="true">
            IRDI
        </div>

        <p class="eyebrow">
            {{ __('Scheduled Maintenance') }}
        </p>

        <h1>
            {{ __('We’ll be right back.') }}
        </h1>

        <p class="introduction">
            {{ __('The IRDI website is temporarily unavailable while we perform maintenance and make improvements.') }}
        </p>

        <div class="divider" aria-hidden="true"></div>

        <section class="notice" aria-labelledby="maintenance-heading">
            <h2 id="maintenance-heading">
                {{ __('Thank you for your patience.') }}
            </h2>

            <p>
                {{ __('We’re working to restore access as quickly as possible. Please check back shortly.') }}
            </p>

            <a href="/" class="button">
                {{ __('Try Again') }}
            </a>
        </section>

        <p class="organization">
            <strong>IRDI</strong>
            International Responsible Detectorist Institute
        </p>

    </div>
</main>
</body>
</html>
