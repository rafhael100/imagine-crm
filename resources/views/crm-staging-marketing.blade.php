<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $copy['title'] }}</title>
    <meta name="description" content="{{ $copy['summary'] }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <style>
        :root { color-scheme: light; --orange: #F9941D; --ink:#181D1A; --soft:#526159;
            --line:#E6EAE5; --paper:#FAFBF8; --white:#FFFFFF; --leaf:#2E4A38; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; color:var(--ink); background:var(--paper);
            font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            -webkit-font-smoothing:antialiased; }
        a { color:inherit; }
        a:focus-visible { outline:3px solid var(--orange); outline-offset:4px; border-radius:6px; }
        .skip { position:absolute; left:-100vw; top:0; padding:10px; z-index:5; background:#fff; }
        .skip:focus { left:8px; }
        .container { max-width:1180px; margin:0 auto; padding:0 28px; }
        .notice { padding:11px 20px; background:#25392F; color:#E5F3E9;
            font-weight:600; font-size:12px; text-align:center; letter-spacing:.02em; }
        header { background:#fff; border-bottom:1px solid var(--line); }
        .nav { display:flex; align-items:center; justify-content:space-between; min-height:78px;
            gap:20px; }
        .brand { display:flex; align-items:center; gap:12px; text-decoration:none; font-size:22px;
            letter-spacing:-.06em; font-weight:800; white-space:nowrap; }
        .mark { display:grid; place-items:center; width:39px; height:39px; background:var(--orange);
            color:#1F261F; border-radius:12px; font-size:25px; font-weight:900; line-height:1; }
        .brand small { color:var(--orange); font-weight:800; font-size:14px; letter-spacing:.03em; }
        .languages { display:flex; gap:6px; align-items:center; }
        .languages a { text-decoration:none; display:flex; align-items:center; gap:7px;
            padding:10px 12px; border-radius:11px; border:1px solid transparent; color:var(--soft);
            font-size:13px; font-weight:650; }
        .languages a[aria-current="page"] { border-color:#F5D09E; color:var(--ink);
            background:#FFF6E8; }
        .languages a:hover { background:#F4F6F3; }
        .flag { font-size:20px; line-height:1; }
        .hero { padding:95px 0 84px; overflow:hidden;
            background:radial-gradient(circle at 88% 14%,#FCEBD8 0,#FDF6EC 19%,transparent 46%),#FAFBF8; }
        .hero-grid { display:grid; grid-template-columns:1.15fr .85fr; gap:70px; align-items:center; }
        .eyebrow { display:inline-flex; gap:8px; align-items:center; margin:0 0 22px;
            border:1px solid #E8DDC7; background:#FFF9EF; color:#A85A0D; border-radius:40px;
            font-size:12px; font-weight:750; padding:9px 15px; letter-spacing:.035em; }
        h1 { font-size:clamp(40px,5.1vw,66px); letter-spacing:-.075em; line-height:1.075;
            margin:0 0 24px; max-width:740px; font-weight:800; }
        h1 span { color:#D97912; }
        .summary { color:var(--soft); font-size:18px; line-height:1.7; max-width:540px; margin:0 0 32px; }
        .action-row { display:flex; align-items:center; flex-wrap:wrap; gap:16px; }
        .link-secondary { display:inline-flex; align-items:center; justify-content:center;
            padding:14px 19px; border:1px solid #D8DCD5; border-radius:12px;
            background:#fff; color:#35493A; text-decoration:none; font-size:14px; font-weight:750; }
        .link-secondary:hover { background:#F0F4ED; }
        .link-hero { display:inline-flex; align-items:center; justify-content:center; text-decoration:none;
            border-radius:12px; background:var(--orange); color:#1B241D; padding:15px 22px;
            font-size:14px; font-weight:800; box-shadow:0 12px 30px #F9941D34; }
        .mock { position:relative; border:1px solid #E5E9E4; background:#fff;
            border-radius:22px; overflow:hidden; box-shadow:0 32px 90px #1B282015; transform:rotate(-1.4deg); }
        .mock-head { padding:19px 20px; border-bottom:1px solid var(--line); display:flex;
            gap:10px; align-items:center; justify-content:space-between; font-size:12px; font-weight:750; }
        .dots { display:flex; gap:5px; } .dots i { width:7px; height:7px; border-radius:50%;
            display:block; background:#E4EAE3; }
        .dots i:first-child { background:var(--orange); }
        .mock-body { display:grid; grid-template-columns:90px 1fr; min-height:250px; }
        .side { background:#F8FAF7; border-right:1px solid var(--line); padding:22px 14px; }
        .side b { display:block; height:9px; width:54px; margin:0 0 23px; border-radius:8px;
            background:#D8E2D8; } .side b:nth-child(2) { width:39px; background:#F9BC79; }
        .board { padding:19px; min-width:0; }
        .board-top { height:10px; width:55%; margin:0 0 20px; background:#CAD5CB; border-radius:10px; }
        .lanes { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .lane { background:#F5F7F4; border-radius:12px; padding:9px; min-height:170px; }
        .lane-title { height:7px; width:68%; background:#D8E0D5; border-radius:6px; margin-bottom:14px; }
        .ticket { border:1px solid #E8ECE8; border-radius:9px; background:#fff; padding:12px 9px;
            margin-bottom:8px; box-shadow:0 3px 8px #22302507; }
        .ticket b { display:block; height:7px; width:85%; background:#D6E0D8; border-radius:6px; margin-bottom:12px; }
        .ticket i { display:block; height:6px; width:53%; background:#FAD09F; border-radius:6px; }
        .features { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; padding-top:0; padding-bottom:76px; }
        .feature { background:#fff; border:1px solid var(--line); padding:23px 21px; border-radius:17px;
            font-size:14px; font-weight:730; color:#31443A; }
        .feature:before { content:"✓"; display:inline-grid; place-items:center; margin-right:10px;
            background:#EAF4E9; color:#2E6A47; width:25px; height:25px; border-radius:9px; }
        .plans { background:#fff; border-top:1px solid var(--line); padding:85px 0 92px; }
        .section-head { text-align:center; max-width:770px; margin:0 auto 40px; }
        h2 { margin:0 0 17px; font-size:clamp(29px,4vw,43px); letter-spacing:-.055em; line-height:1.13; }
        .section-head p { color:var(--soft); line-height:1.7; font-size:15px; }
        .cards { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
        .plan { min-width:0; padding:27px 23px 24px; border:1px solid #E3E9E3; border-radius:18px;
            background:#FFF; box-shadow:0 8px 24px #10241507; }
        .plan.featured { border-color:#F5B76F; box-shadow:0 10px 32px #E98A1C1A; }
        .plan-name { font-size:16px; font-weight:800; margin:0 0 24px; }
        .price { font-size:31px; font-weight:820; letter-spacing:-.055em; margin:0; }
        .price-note { color:#6A776D; font-size:12px; line-height:1.5; margin:8px 0 27px; }
        .plan-line { display:flex; gap:9px; border-top:1px solid var(--line); padding:13px 0;
            font-size:13px; color:var(--soft); }
        .plan-line:before { content:"✓"; color:#36814D; font-weight:800; }
        .disclaimer { background:#FFF8EA; color:#74521F; border:1px solid #F4DFC0;
            padding:19px 22px; border-radius:14px; font-size:13px; line-height:1.6;
            margin-top:28px; text-align:center; }
        footer { padding:36px 0; color:#5A6A60; font-size:12px; background:#F7F9F6; }
        footer .container { display:flex; justify-content:space-between; gap:12px; }
        @media(max-width:960px) {
            .hero-grid { gap:35px; }
            .cards { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .features { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media(max-width:650px) {
            .container { padding:0 19px; }
            .nav { min-height:70px; }
            .brand { font-size:19px; }
            .mark { width:35px; height:35px; }
            .languages { gap:2px; }
            .languages a { padding:8px 6px; font-size:0; }
            .flag { font-size:22px; }
            .hero { padding:65px 0 45px; }
            .hero-grid { grid-template-columns:1fr; gap:39px; }
            .summary { font-size:16px; }
            .mock { transform:none; }
            .features { padding-bottom:52px; gap:10px; }
            .feature { padding:16px 13px; font-size:12px; }
            .plans { padding:58px 0 65px; }
            .cards { gap:12px; }
            .plan { padding:19px 15px; }
            .price { font-size:26px; }
            footer .container { flex-direction:column; }
        }
        @media(max-width:380px) {
            .cards { grid-template-columns:1fr; }
            .brand small { font-size:12px; }
        }
    </style>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="notice" role="status">{{ $copy['status'] }}</div>
<header>
    <div class="container nav">
        <a class="brand" href="/?lang={{ $lang }}" aria-label="Imagine CRM">
            <span class="mark" aria-hidden="true">i</span>
            <span>imagine <small>CRM</small></span>
        </a>
        <nav class="languages" aria-label="Choose language">
            <a href="/?lang=pt" aria-label="Português (Brasil)" @if($lang === 'pt') aria-current="page" @endif><span class="flag" aria-hidden="true">🇧🇷</span> PT</a>
            <a href="/?lang=en" aria-label="English (United States)" @if($lang === 'en') aria-current="page" @endif><span class="flag" aria-hidden="true">🇺🇸</span> EN</a>
            <a href="/?lang=es" aria-label="Español (España)" @if($lang === 'es') aria-current="page" @endif><span class="flag" aria-hidden="true">🇪🇸</span> ES</a>
        </nav>
    </div>
</header>
<main id="main">
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <p class="eyebrow">{{ $copy['eyebrow'] }}</p>
                <h1>{{ $copy['headline'] }}</h1>
                <p class="summary">{{ $copy['summary'] }}</p>
                <div class="action-row">
                    <a class="link-hero" href="#plans">{{ $copy['plans_link'] }}</a>
                    <a class="link-secondary" href="/demo?lang={{ $lang }}">{{ $copy['demo_link'] }}</a>
                </div>
            </div>
            <div class="mock" aria-label="Illustrative CRM dashboard layout; no customer data" role="img">
                <div class="mock-head"><span>Imagine CRM</span><span class="dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
                <div class="mock-body">
                    <div class="side" aria-hidden="true"><b></b><b></b><b></b><b></b><b></b></div>
                    <div class="board" aria-hidden="true">
                        <div class="board-top"></div>
                        <div class="lanes">
                            <div class="lane"><div class="lane-title"></div><div class="ticket"><b></b><i></i></div><div class="ticket"><b></b><i></i></div></div>
                            <div class="lane"><div class="lane-title"></div><div class="ticket"><b></b><i></i></div></div>
                            <div class="lane"><div class="lane-title"></div><div class="ticket"><b></b><i></i></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="container features" aria-label="Planned capabilities">
        <div class="feature">{{ $copy['focus_one'] }}</div>
        <div class="feature">{{ $copy['focus_two'] }}</div>
        <div class="feature">{{ $copy['focus_three'] }}</div>
        <div class="feature">{{ $copy['focus_four'] }}</div>
    </section>
    <section class="plans" id="plans">
        <div class="container">
            <div class="section-head"><h2>{{ $copy['plan_title'] }}</h2><p>{{ $copy['plan_intro'] }}</p></div>
            <div class="cards">
                @foreach($cards as $card)
                    <article class="plan @if($card['code'] === 'essencial') featured @endif">
                        <h3 class="plan-name">{{ $card['name'] }}</h3>
                        <p class="price">{{ $card['display_price'] }}</p>
                        <p class="price-note">{{ $copy['monthly'] }}</p>
                        <div class="plan-line">{{ $card['seats'] }} {{ $copy['seats'] }}</div>
                        <div class="plan-line">{{ $card['storage'] }} • {{ $copy['storage'] }}</div>
                    </article>
                @endforeach
            </div>
            <p class="disclaimer">{{ $copy['preview'] }}</p>
        </div>
    </section>
</main>
<footer><div class="container"><span>{{ $copy['foot'] }}</span><span>© Imagine CRM • Preview</span></div></footer>
</body>
</html>
