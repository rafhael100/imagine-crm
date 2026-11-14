<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>{{ $copy['title'] }}</title>
<style>
  :root { --orange:#F9941D; --ink:#19201A; --grey:#617066; --border:#E4E9E3;
    --soft:#F4F7F3; --white:#fff; --green:#30563F; }
  * { box-sizing:border-box; }
  html { scroll-behavior:smooth; }
  body { margin:0; font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,
    "Segoe UI",sans-serif; background:#F7F9F6; color:var(--ink); }
  a { color:inherit; }
  a:focus-visible { outline:3px solid var(--orange); outline-offset:3px; border-radius:6px; }
  .banner { text-align:center; background:#283F32; color:#E9F3EC; padding:12px 20px;
    font-weight:750; letter-spacing:.025em; font-size:11px; }
  .shell { min-height:calc(100vh - 42px); display:grid; grid-template-columns:235px minmax(0,1fr); }
  .sidebar { background:#fff; border-right:1px solid var(--border); padding:29px 18px;
    display:flex; flex-direction:column; gap:30px; }
  .brand { display:flex; align-items:center; gap:10px; text-decoration:none;
    font-size:21px; font-weight:850; letter-spacing:-.07em; padding:0 10px; }
  .mark { background:var(--orange); border-radius:11px; width:34px; height:34px;
    display:grid; place-items:center; font-weight:900; font-size:23px; }
  .brand small { font-size:12px; color:#D27811; letter-spacing:.02em; }
  .side-nav { display:grid; gap:6px; }
  .side-nav a { text-decoration:none; padding:13px 15px; border-radius:11px;
    color:#4E6254; font-size:14px; font-weight:650; }
  .side-nav a:hover, .side-nav a:focus-visible { background:#FFF2DF; color:#704309; }
  .side-nav .active { background:#FFF2DF; color:#714B19; }
  .side-bottom { margin-top:auto; padding:15px 12px; background:#FAFBF9;
    border:1px solid var(--border); border-radius:14px; font-size:12px;
    line-height:1.5; color:#677468; }
  .main { padding:34px clamp(22px,4vw,62px) 52px; min-width:0; }
  .top { display:flex; align-items:center; justify-content:space-between;
    gap:18px; margin-bottom:38px; }
  .back { text-decoration:none; color:#57685C; font-weight:730; font-size:13px; }
  .back:hover { color:#BA751C; }
  .languages { display:flex; gap:6px; }
  .languages a { font-size:12px; text-decoration:none; display:flex; align-items:center; gap:6px;
    border:1px solid transparent; padding:8px 10px; border-radius:10px; color:var(--grey); }
  .languages a[aria-current=page] { background:#FFF3E1; border-color:#F4D5AC; color:#704109; }
  .flag { font-size:19px; }
  h1 { font-size:clamp(29px,3.6vw,45px); line-height:1.17; letter-spacing:-.065em; margin:0 0 14px; }
  .intro { color:var(--grey); line-height:1.65; font-size:15px; margin:0; max-width:760px; }
  .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr));
    gap:15px; margin:31px 0 42px; }
  .stat { background:white; border:1px solid var(--border); border-radius:17px;
    padding:23px 22px; box-shadow:0 7px 20px #1F312408; }
  .stat-label { color:#637467; font-weight:640; font-size:12px; margin:0 0 12px; }
  .stat-number { font-size:37px; font-weight:820; line-height:1.05;
    letter-spacing:-.045em; margin:0; }
  .stat:last-child .stat-number { color:#B37A2D; }
  .section-head { display:flex; align-items:center; justify-content:space-between;
    gap:20px; margin:0 0 20px; }
  h2 { margin:0; font-size:21px; letter-spacing:-.035em; }
  .section-note { color:var(--grey); font-size:12px; }
  .board-scroll { overflow-x:auto; padding-bottom:12px; }
  .board { display:grid; grid-template-columns:repeat(4,minmax(190px,1fr)); gap:13px;
    min-width:805px; }
  .lane { background:#ECF1EA; padding:12px; border-radius:16px; min-height:245px; }
  .lane-head { display:flex; justify-content:space-between; align-items:center;
    font-size:13px; font-weight:760; padding:4px 4px 14px; }
  .lane-count { color:#5D705D; border-radius:20px; background:#DFE8DD; font-size:11px;
    padding:4px 8px; }
  .opportunity { background:#fff; border-radius:12px; border:1px solid #E1E9DF;
    margin:0 0 10px; padding:16px 13px; box-shadow:0 4px 10px #1F2C1F08; }
  .opportunity strong { display:block; font-size:12px; letter-spacing:.02em; margin-bottom:8px; }
  .opportunity small { color:#849085; font-size:10px; display:block; }
  .label { display:inline-block; margin-top:12px; border-radius:7px; padding:5px 8px;
    font-size:10px; font-weight:760; color:#AF651C; background:#FFF1E0; }
  .tasks { margin-top:48px; }
  .task-list { background:white; border:1px solid var(--border); border-radius:16px;
    padding:8px 20px; }
  .task { display:flex; align-items:center; justify-content:space-between; gap:14px;
    min-height:72px; border-bottom:1px solid var(--border); }
  .task:last-child { border-bottom:0; }
  .task-identity { display:flex; gap:13px; align-items:center; min-width:0; }
  .task-icon { width:37px; height:37px; flex:0 0 37px; background:#FFF3E3;
    border-radius:11px; display:grid; place-items:center; color:#A36517; font-size:18px; }
  .task strong { display:block; font-size:12px; overflow-wrap:anywhere; }
  .task small { display:block; color:#8B968C; font-size:10px; margin-top:5px; }
  .task-day { flex:0 0 auto; font-size:12px; color:#6B796F; }
  .hint { padding:17px 19px; border:1px solid #F2DFBE; border-radius:13px;
    color:#805D25; background:#FFF9EE; font-size:12px; line-height:1.65; margin:21px 0 0; }
  footer { color:#809082; font-size:11px; margin-top:50px; }
  @media(max-width:930px) {
    .shell { grid-template-columns:1fr; }
    .sidebar { flex-direction:row; align-items:center; padding:15px 20px; gap:12px; }
    .side-nav { display:flex; margin-left:auto; gap:2px; }
    .side-nav a { padding:10px; font-size:12px; }
    .side-bottom { display:none; }
    .main { padding:28px 22px 46px; }
  }
  @media(max-width:630px) {
    .sidebar { flex-wrap:wrap; }
    .side-nav { width:100%; justify-content:space-between; }
    .top { margin-bottom:30px; }
    .languages a { padding:5px 5px; font-size:0; }
    .flag { font-size:21px; }
    .stats { gap:9px; }
    .stat { padding:18px 11px; }
    .stat-number { font-size:27px; }
    .stat-label { font-size:11px; }
    .section-head { display:block; }
    .section-note { display:block; margin-top:8px; }
    .task-list { padding:6px 12px; }
  }
</style>
</head>
<body>
<div class="banner" role="status">{{ $copy['eyebrow'] }}</div>
<div class="shell">
    <aside class="sidebar" aria-label="Demo navigation">
        <a class="brand" href="/?lang={{ $lang }}"><span class="mark" aria-hidden="true">i</span>
            <span>imagine <small>CRM</small></span></a>
        <nav class="side-nav">
            <a class="active" href="#pipeline">{{ $copy['pipeline'] }}</a>
            <a href="#tasks">{{ $copy['tasks'] }}</a>
        </nav>
        <p class="side-bottom">{{ $copy['board_note'] }}</p>
    </aside>
    <main class="main">
        <div class="top">
            <a class="back" href="/?lang={{ $lang }}">← {{ $copy['back'] }}</a>
            <nav class="languages" aria-label="Choose language">
                <a href="/demo?lang=pt" aria-label="Português (Brasil)" @if($lang === 'pt') aria-current="page" @endif><span class="flag" aria-hidden="true">🇧🇷</span> PT</a>
                <a href="/demo?lang=en" aria-label="English (United States)" @if($lang === 'en') aria-current="page" @endif><span class="flag" aria-hidden="true">🇺🇸</span> EN</a>
                <a href="/demo?lang=es" aria-label="Español (España)" @if($lang === 'es') aria-current="page" @endif><span class="flag" aria-hidden="true">🇪🇸</span> ES</a>
            </nav>
        </div>
        <section aria-labelledby="demo-title">
            <h1 id="demo-title">{{ $copy['heading'] }}</h1>
            <p class="intro">{{ $copy['intro'] }}</p>
            <div class="stats">
                <div class="stat"><p class="stat-label">{{ $copy['opportunities'] }}</p><p class="stat-number">{{ $opportunity_count }}</p></div>
                <div class="stat"><p class="stat-label">{{ $copy['pending'] }}</p><p class="stat-number">{{ $task_count }}</p></div>
                <div class="stat"><p class="stat-label">{{ $copy['actions'] }}</p><p class="stat-number">0</p></div>
            </div>
        </section>
        <section id="pipeline" aria-labelledby="pipeline-title">
            <div class="section-head"><h2 id="pipeline-title">{{ $copy['pipeline'] }}</h2><span class="section-note">{{ $copy['board_note'] }}</span></div>
            <div class="board-scroll">
                <div class="board">
                    @foreach($pipeline as $stage => $leads)
                    <div class="lane">
                        <div class="lane-head"><span>{{ $copy[$stage] }}</span><span class="lane-count">{{ count($leads) }}</span></div>
                        @foreach($leads as $lead)
                        <article class="opportunity">
                            <strong>{{ $lead['label'] }}</strong>
                            <small>{{ $lead['reference'] }}</small>
                            <span class="label">{{ $lead['detail'] }}</span>
                        </article>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section id="tasks" class="tasks" aria-labelledby="tasks-title">
            <div class="section-head"><h2 id="tasks-title">{{ $copy['tasks'] }}</h2><span class="section-note">{{ $copy['task_note'] }}</span></div>
            <div class="task-list">
                @foreach($tasks as $task)
                <article class="task">
                    <div class="task-identity">
                        <span class="task-icon" aria-hidden="true">✓</span>
                        <span><strong>{{ $task['subject'] }}</strong><small>{{ $task['reference'] }}</small></span>
                    </div>
                    <span class="task-day">{{ $task['day'] }}</span>
                </article>
                @endforeach
            </div>
            <p class="hint">{{ $copy['no_access'] }}</p>
        </section>
        <footer>{{ $copy['footer'] }}</footer>
    </main>
</div>
</body>
</html>
