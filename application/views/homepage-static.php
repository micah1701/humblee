<?php

declare(strict_types=1);

use Humblee\Foundation\Draw;

/** @var array<string, mixed> $content */

// Draw::content($content, "pagebody_hero");

// TODO: replace with /media/{id}/filename.ext paths once images are added to the media manager
$screenshots = [
    '/application/img/CMS-homepage.png',
    '/application/img/CMS-media-manager.png',
    '/application/img/CMS-blocks-tool.png',
    '/application/img/CMS-editor-seo.png',
    '/application/img/CMS-page-properties.png',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Humblee is a lightweight PHP framework with a fully featured CMS built in — giving developers solid tools for custom apps while giving content teams everything they need to manage a site." />
    <title>Humblee — PHP Framework &amp; CMS</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0c0e16;
            --bg-card: #141722;
            --bg-card-hover: #191d2e;
            --border: rgba(255, 255, 255, 0.07);
            --border-hover: rgba(255, 255, 255, 0.14);
            --text: #dde1f0;
            --muted: #6e7696;
            --accent: #7c6cff;
            --accent-soft: rgba(124, 108, 255, 0.12);
            --accent-rim: rgba(124, 108, 255, 0.28);
            --dev: #34d399;
            --dev-bg: rgba(52, 211, 153, 0.07);
            --dev-rim: rgba(52, 211, 153, 0.22);
            --cms: #fb923c;
            --cms-bg: rgba(251, 146, 60, 0.07);
            --cms-rim: rgba(251, 146, 60, 0.22);
            --radius: 14px;
            --radius-sm: 9px;
            --sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            --mono: 'Fira Code', 'Cascadia Code', Consolas, 'Courier New', monospace;
        }

        html {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* ── Layout ─────────────────────────────────────────────── */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ── Nav ────────────────────────────────────────────────── */
        nav {
            padding: 18px 0;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(12, 14, 22, 0.88);
            backdrop-filter: blur(14px);
        }

        .nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.03em;
            text-decoration: none;
        }

        .logo em {
            color: var(--accent);
            font-style: normal;
        }

        .nav-links {
            display: flex;
            gap: 28px;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.15s;
        }

        .nav-links a:hover {
            color: var(--text);
            text-decoration: none;
        }

        .nav-cta {
            background: var(--accent);
            color: #fff !important;
            padding: 7px 16px;
            border-radius: 7px;
            font-weight: 600 !important;
        }

        .nav-cta:hover {
            background: #9180ff;
        }

        /* ── Buttons ────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 0.925rem;
            font-weight: 600;
            transition: all 0.18s;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: #9180ff;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-ghost {
            border: 1px solid var(--border);
            color: var(--text);
            background: var(--bg-card);
        }

        .btn-ghost:hover {
            border-color: var(--border-hover);
            background: var(--bg-card-hover);
            text-decoration: none;
        }

        /* ── Hero ───────────────────────────────────────────────── */
        .hero {
            min-height: 580px;
            display: flex;
            align-items: center;
            padding: 96px 0 88px;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .hero .container {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .hero-slider {
            position: absolute;
            inset: 0;
            z-index: 0;
            cursor: zoom-in;
        }

        .hero-slide {
            position: absolute;
            inset: 0;
            background-size: contain;
            background-position: center right;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1.2s ease-in-out;
        }

        .hero-slide.active {
            opacity: 1;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(to right,
                    rgba(12, 14, 22, 0.97) 0%,
                    rgba(12, 14, 22, 0.90) 32%,
                    rgba(12, 14, 22, 0.55) 62%,
                    rgba(12, 14, 22, 0.06) 100%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
            margin-right: auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent-soft);
            border: 1px solid var(--accent-rim);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 28px;
        }

        .hero h1 {
            font-size: clamp(2.1rem, 5.5vw, 3.7rem);
            font-weight: 800;
            line-height: 1.13;
            letter-spacing: -0.035em;
            color: #fff;
            margin: 0 0 20px;
        }

        .hero-lead {
            font-size: 1.1rem;
            color: var(--muted);
            max-width: 520px;
            margin: 0 0 40px;
            line-height: 1.72;
        }

        .hero-ctas {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* ── Screenshot modal ───────────────────────────────────── */
        .hero-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.92);
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .hero-modal.open {
            display: flex;
        }

        .hero-modal-img {
            max-width: 92vw;
            max-height: 88vh;
            object-fit: contain;
            border-radius: 6px;
            box-shadow: 0 4px 80px rgba(0, 0, 0, 0.7);
        }

        .hero-modal-close {
            position: absolute;
            top: 20px;
            right: 24px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            font-size: 1rem;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
            line-height: 1;
        }

        .hero-modal-close:hover {
            background: rgba(255, 255, 255, 0.16);
        }

        /* ── Stat strip ─────────────────────────────────────────── */
        .stat-strip {
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 20px 0;
        }

        .stat-inner {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 48px;
            flex-wrap: wrap;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--muted);
        }

        .stat strong {
            color: var(--text);
            font-weight: 600;
        }

        .stat-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            opacity: 0.4;
        }

        /* ── Audience split ─────────────────────────────────────── */
        .audiences {
            padding: 88px 0;
        }

        .audience-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .audience-card {
            border-radius: var(--radius);
            padding: 36px;
            border: 1px solid var(--border);
        }

        .audience-card.dev {
            background: var(--dev-bg);
            border-color: var(--dev-rim);
        }

        .audience-card.cms {
            background: var(--cms-bg);
            border-color: var(--cms-rim);
        }

        .audience-eyebrow {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 5px;
            margin-bottom: 16px;
        }

        .dev .audience-eyebrow {
            background: rgba(52, 211, 153, 0.12);
            color: var(--dev);
        }

        .cms .audience-eyebrow {
            background: rgba(251, 146, 60, 0.12);
            color: var(--cms);
        }

        .audience-card h2 {
            font-size: 1.45rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .audience-card>p {
            color: var(--muted);
            font-size: 0.925rem;
            margin-bottom: 24px;
        }

        .check-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .check-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--text);
        }

        .check-list li::before {
            content: '✓';
            flex-shrink: 0;
            font-weight: 700;
            margin-top: 1px;
        }

        .dev .check-list li::before {
            color: var(--dev);
        }

        .cms .check-list li::before {
            color: var(--cms);
        }

        /* ── Section header ─────────────────────────────────────── */
        .section-header {
            text-align: center;
            margin-bottom: 56px;
        }

        .section-eyebrow {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--accent);
            display: block;
            margin-bottom: 12px;
        }

        .section-header h2 {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #fff;
            margin-bottom: 12px;
        }

        .section-header p {
            color: var(--muted);
            max-width: 480px;
            margin: 0 auto;
            line-height: 1.65;
        }

        /* ── Features grid ──────────────────────────────────────── */
        .features {
            padding: 88px 0;
            border-top: 1px solid var(--border);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 26px;
            transition: border-color 0.18s, background 0.18s;
        }

        .feature-card:hover {
            background: var(--bg-card-hover);
            border-color: var(--accent-rim);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--accent-soft);
            border: 1px solid var(--accent-rim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 16px;
        }

        .feature-card h3 {
            font-size: 0.975rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 7px;
        }

        .feature-card p {
            font-size: 0.855rem;
            color: var(--muted);
            line-height: 1.62;
        }

        /* ── Philosophy ─────────────────────────────────────────── */
        .philosophy {
            padding: 88px 0;
            border-top: 1px solid var(--border);
        }

        .philosophy-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 72px;
            align-items: center;
        }

        .philosophy h2 {
            font-size: clamp(1.7rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 18px;
        }

        .philosophy p {
            color: var(--muted);
            margin-bottom: 14px;
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .code-block {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 26px 28px;
            font-family: var(--mono);
            font-size: 0.8rem;
            color: var(--text);
            line-height: 1.75;
            overflow-x: auto;
            tab-size: 2;
        }

        .c-comment {
            color: #3d4266;
        }

        .c-kw {
            color: #7c6cff;
        }

        .c-str {
            color: #34d399;
        }

        .c-fn {
            color: #fb923c;
        }

        .c-var {
            color: #e879f9;
        }

        .c-num {
            color: #fb923c;
        }

        /* ── Security ───────────────────────────────────────────── */
        .security {
            padding: 88px 0;
            border-top: 1px solid var(--border);
        }

        .security-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .security-item {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }

        .sec-icon {
            font-size: 1.35rem;
            flex-shrink: 0;
            line-height: 1;
            margin-top: 2px;
        }

        .security-item h3 {
            font-size: 0.925rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }

        .security-item p {
            font-size: 0.845rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ── CTA ────────────────────────────────────────────────── */
        .cta-section {
            padding: 100px 0;
            text-align: center;
            border-top: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            bottom: -80px;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 350px;
            background: radial-gradient(ellipse at 50% 100%, rgba(124, 108, 255, 0.1) 0%, transparent 65%);
            pointer-events: none;
        }

        .cta-section h2 {
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #fff;
            margin-bottom: 14px;
        }

        .cta-section p {
            color: var(--muted);
            max-width: 420px;
            margin: 0 auto 36px;
        }

        .cta-ctas {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ── Footer ─────────────────────────────────────────────── */
        footer {
            padding: 28px 0;
            border-top: 1px solid var(--border);
        }

        .footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        footer p {
            font-size: 0.78rem;
            color: var(--muted);
            max-width: 680px;
            line-height: 1.6;
        }

        footer a {
            color: var(--muted);
        }

        footer a:hover {
            color: var(--text);
        }

        .footer-gh {
            font-size: 0.85rem;
            color: var(--muted);
            white-space: nowrap;
        }

        .footer-gh:hover {
            color: var(--text);
        }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 860px) {
            .audience-grid {
                grid-template-columns: 1fr;
            }

            .philosophy-inner {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .security-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .nav-links {
                display: none;
            }

            .stat-inner {
                gap: 24px;
            }

            .hero {
                text-align: center;
            }

            .hero-overlay {
                background: rgba(12, 14, 22, 0.88);
            }

            .hero-content {
                max-width: 100%;
            }

            .hero h1 {
                margin: 0 auto 20px;
            }

            .hero-lead {
                margin: 0 auto 40px;
            }

            .hero-ctas {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }

            .audience-card {
                padding: 26px;
            }

            .hero {
                padding: 64px 0 56px;
            }

            .audiences,
            .features,
            .philosophy,
            .security,
            .cta-section {
                padding: 64px 0;
            }
        }
    </style>

    <!-- Analytics Tracking Code -->
    <script>
        window.ANALYTICS_CONFIG = {
            trackingId: '+H0xXhj4YCT0IhXr',
            apiUrl: 'https://gmvshvbfvqujlktpqllf.supabase.co/functions/v1/track'
        };
    </script>
    <script src="https://analytics.ad-hoc.app/analytics.js" defer></script>

</head>

<body>

    <!-- ── Nav ──────────────────────────────────────────────── -->
    <nav>
        <div class="nav-inner">
            <a href="/" class="logo">hum<em>blee</em></a>
            <div class="nav-links">
                <a href="/docs" style="line-height: 2rem">Documentation</a>
                <a href="https://github.com/micah1701/humblee" class="nav-cta">GitHub →</a>
            </div>
        </div>
    </nav>

    <!-- ── Hero ─────────────────────────────────────────────── -->
    <section class="hero">
        <div class="hero-slider" id="heroSlider">
            <?php foreach ($screenshots as $i => $src): ?>
                <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>" style="background-image:url('<?= htmlspecialchars($src) ?>')"></div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">Open Source &nbsp;·&nbsp; MIT License &nbsp;·&nbsp; PHP</div>
                <h1>Build your app your way.<br>Manage it without the headache.</h1>
                <p class="hero-lead">
                    Humblee is a lightweight PHP framework with a fully featured content management system built in.
                    Developers get a clean foundation they can extend freely.
                    Content teams get everything they need to publish and manage a site.
                </p>
                <div class="hero-ctas">
                    <a href="/docs" class="btn btn-primary">Read the Docs</a>
                    <a href="https://github.com/micah1701/humblee" class="btn btn-ghost">Get on GitHub &nbsp;→</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Stat strip ────────────────────────────────────────── -->
    <div class="stat-strip">
        <div class="stat-inner">
            <div class="stat"><strong>PHP 7.4+</strong></div>
            <div class="stat-dot"></div>
            <div class="stat"><strong>MIT License</strong><span>&nbsp;— free to use, modify, and deploy</span></div>
            <div class="stat-dot"></div>
            <div class="stat"><strong>No plugin system</strong><span>&nbsp;— extend with plain PHP</span></div>
            <div class="stat-dot"></div>
            <div class="stat"><strong>Open source</strong><span>&nbsp;since 2017</span></div>
        </div>
    </div>

    <!-- ── Two audiences ─────────────────────────────────────── -->
    <section class="audiences">
        <div class="container">
            <div class="audience-grid">

                <div class="audience-card dev">
                    <span class="audience-eyebrow">For Developers</span>
                    <h2>A foundation you can build on — not fight with</h2>
                    <p>Humblee handles the parts that are the same on every project so you can focus on the parts that aren't.</p>
                    <ul class="check-list">
                        <li>URL routing and controller dispatch ready to go</li>
                        <li>Lightweight database library with safe, readable query syntax — no raw SQL required</li>
                        <li>Built-in handler for AJAX requests: role checks, token validation, JSON output</li>
                        <li>Encryption helpers for hashing, authentication codes, and encrypting text or files</li>
                        <li>Send transactional emails or SMS messages with simple built-in methods</li>
                        <li>Personalization and multi-language content support in the core</li>
                        <li>No plugin system by design — extend freely using plain PHP, your way</li>
                    </ul>
                </div>

                <div class="audience-card cms">
                    <span class="audience-eyebrow">For Content Teams</span>
                    <h2>A CMS that gives you control without needing a developer</h2>
                    <p>Manage your pages, media, and users through a clean admin interface — and never lose a version of anything.</p>
                    <ul class="check-list">
                        <li>Visual page editor with draft, preview, and live publishing</li>
                        <li>Unlimited revision history — roll back any piece of content at any time</li>
                        <li>Role-based access: control who can view, edit, or publish anything on the site</li>
                        <li>Media library for uploading, organizing, and managing images and files</li>
                        <li>User manager with self-service registration and password recovery</li>
                        <li>Optional two-factor login verification via text message</li>
                        <li>Personalized content targeting for different audiences or languages</li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Features grid ─────────────────────────────────────── -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <span class="section-eyebrow">What's included</span>
                <h2>Everything you need. Nothing you don't.</h2>
                <p>Humblee ships with a focused set of tools — not a kitchen sink — so there's less to learn and less to maintain.</p>
            </div>
            <div class="feature-grid">

                <div class="feature-card">
                    <div class="feature-icon">🗺️</div>
                    <h3>Page Routing</h3>
                    <p>Define routes through code or manage them from the CMS page manager. Both approaches use the same dispatch layer, so you pick what fits.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Content Management</h3>
                    <p>Editors write in a visual editor or through custom forms built for specific content types. Every save creates a revision you can roll back at any time.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🗄️</div>
                    <h3>Database ORM</h3>
                    <p>Powered by the lightweight Idiorm library — clean query syntax with protection against injection attacks at every step. No raw SQL needed.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Role-Based Access</h3>
                    <p>Assign roles to users, then restrict pages, files, or CMS features to whichever roles apply. No code changes required.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📁</div>
                    <h3>Media Manager</h3>
                    <p>Upload, rename, and organize files through a browser interface. Files can be locked behind user roles or encrypted at rest for sensitive content.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Encryption</h3>
                    <p>Built-in helpers for hashing, generating authentication codes, and encrypting text or files — backed by PHP's native cryptography functions.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📬</div>
                    <h3>Transactional Messaging</h3>
                    <p>Send email notifications and SMS text messages from your app using built-in methods. Twilio integration handles the text messaging side.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Personalization &amp; Localization</h3>
                    <p>Serve different content to different audiences based on user segments or URL structure. Multi-language content is managed in the same CMS interface.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>AJAX Endpoints</h3>
                    <p>Extend the built-in request handler for custom interactions. Token validation, role checks, cache headers, and JSON formatting are handled for you.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Philosophy + code block ───────────────────────────── -->
    <section class="philosophy">
        <div class="container">
            <div class="philosophy-inner">
                <div>
                    <span class="section-eyebrow">Philosophy</span>
                    <h2>No plugins. No magic.<br>Just PHP.</h2>
                    <p>Most frameworks grow into platforms — and platforms grow into something you spend time managing instead of building. Humblee takes a different path: handle the foundation, then step aside.</p>
                    <p>There's no plugin marketplace because custom functionality requires custom code. Humblee gives you routing, auth, a database layer, and a CMS. After that, you write the parts that make your app yours — in plain PHP, exactly the way you want.</p>
                    <p>If you're a developer who likes to build things your own way, this was made for you.</p>
                </div>
                <div class="code-block"><span class="c-comment">// Extend the base controller — your app, your rules</span>
                    <span class="c-kw">class</span> ProductController <span class="c-kw">extends</span> AppController {

                    <span class="c-kw">public function</span> <span class="c-fn">index</span>() {
                    <span class="c-comment">// Require a role in one line</span>
                    <span class="c-var">$this</span>-><span class="c-fn">require_role</span>(<span class="c-str">'member'</span>);

                    <span class="c-comment">// Clean ORM query — no raw SQL</span>
                    <span class="c-var">$products</span> = \ORM::<span class="c-fn">for_table</span>(<span class="c-str">'products'</span>)
                    -><span class="c-fn">where</span>(<span class="c-str">'active'</span>, <span class="c-num">1</span>)
                    -><span class="c-fn">find_many</span>();

                    <span class="c-comment">// Render your view</span>
                    <span class="c-var">$this</span>-><span class="c-fn">view</span>(<span class="c-str">'products/index'</span>, [
                    <span class="c-str">'products'</span> => <span class="c-var">$products</span>
                    ]);
                    }

                    }
                </div>
            </div>
        </div>
    </section>

    <!-- ── Security ──────────────────────────────────────────── -->
    <section class="security">
        <div class="container">
            <div class="section-header">
                <span class="section-eyebrow">Security</span>
                <h2>Secure defaults you don't have to think about</h2>
                <p>The things that trip up most web apps are handled before you write your first line of custom code.</p>
            </div>
            <div class="security-grid">

                <div class="security-item">
                    <div class="sec-icon">🛡️</div>
                    <div>
                        <h3>CSRF Protection</h3>
                        <p>Every state-changing form submission is validated with a session-based authentication token. Forged cross-site requests are rejected before they reach your controller.</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="sec-icon">🔒</div>
                    <div>
                        <h3>Hashed Passwords</h3>
                        <p>Passwords are never stored in plain text. Humblee uses PHP's native password hashing, which handles modern algorithm recommendations automatically.</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="sec-icon">⏱️</div>
                    <div>
                        <h3>Login Rate Limiting</h3>
                        <p>Repeated failed login attempts are slowed down and logged to the database. Brute-force attacks burn out before they make meaningful progress.</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="sec-icon">📱</div>
                    <div>
                        <h3>Two-Factor Authentication</h3>
                        <p>Optionally require users to confirm logins with an SMS verification code, adding a second layer of protection for accounts that need it.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ── CTA ───────────────────────────────────────────────── -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to start building?</h2>
            <p>Humblee is free, open source, and available on GitHub under the MIT License.</p>
            <div class="cta-ctas">
                <a href="/docs" class="btn btn-primary">Read the Documentation</a>
                <a href="https://github.com/micah1701/humblee" class="btn btn-ghost">Get the Code &nbsp;→</a>
            </div>
        </div>
    </section>

    <!-- ── Footer ────────────────────────────────────────────── -->
    <footer>
        <div class="footer-inner">
            <p>
                &copy; 2017&ndash;2026 Micah J. Murray.
                Humblee is an open source project offered by
                <a href="https://creativeadhocsolutions.com">Creative Ad-Hoc Solutions</a>
                under the MIT License and is provided &ldquo;AS IS&rdquo; without warranty of any kind,
                express or implied.
            </p>
            <a href="https://github.com/micah1701/humblee" class="footer-gh">GitHub →</a>
        </div>
    </footer>


    <!-- ── Screenshot modal ─────────────────────────────────── -->
    <div class="hero-modal" id="heroModal" role="dialog" aria-modal="true" aria-label="CMS screenshot preview">
        <button class="hero-modal-close" id="heroModalClose" aria-label="Close preview">✕</button>
        <img class="hero-modal-img" id="heroModalImg" src="" alt="CMS screenshot" />
    </div>

    <script>
        (function() {
            var slides = document.querySelectorAll('.hero-slide');
            var slider = document.getElementById('heroSlider');
            var modal = document.getElementById('heroModal');
            var img = document.getElementById('heroModalImg');
            var current = 0;
            var timer = null;

            function show(idx) {
                slides[current].classList.remove('active');
                current = ((idx % slides.length) + slides.length) % slides.length;
                slides[current].classList.add('active');
            }

            function start() {
                timer = setInterval(function() {
                    show(current + 1);
                }, 5000);
            }

            function stop() {
                clearInterval(timer);
                timer = null;
            }

            function open() {
                var bg = slides[current].style.backgroundImage;
                img.src = bg.replace(/^url\(["']?|["']?\)$/g, '');
                modal.classList.add('open');
                stop();
            }

            function close() {
                modal.classList.remove('open');
                img.src = '';
                start();
            }

            start();
            slider.addEventListener('click', open);
            document.getElementById('heroModalClose').addEventListener('click', close);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) close();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('open')) close();
            });
        })();
    </script>

</body>

</html>