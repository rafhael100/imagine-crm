
@php
    $language=\App\Homologation\PrivateUi::normalize($language??'pt');
    $t=fn($text,$replacements=[])=>\App\Homologation\PrivateUi::text($text,$language,$replacements);
    $displayLabel=fn($group,$code)=>\App\Homologation\PrivateUi::label($group,$code,$language);
    $say = fn($pt,$en,$es) => $language==='en' ? $en : ($language==='es' ? $es : $pt);
    $money=fn($cents)=>'R$ '.number_format($cents/100,2,',','.');
    $stages=['new'=>$t('Novo'),'qualified'=>$t('Qualificado'),'proposal'=>$t('Proposta'),'won'=>$t('Ganho'),'lost'=>$t('Perdido')];
    $sections=['overview'=>$t('Visão geral'),'leads'=>$t('Leads'),'funnels'=>$t('Funis'),'kanban'=>$t('Kanban'),'tasks'=>$t('Tarefas'),
        'reports'=>$t('Relatórios'),'team'=>$t('Equipe'),'automation'=>$t('Automações'),'settings'=>$t('Preferências'),'billing'=>$t('Plano e pedidos')];
    $section=request('section','overview'); if(!is_string($section) || !array_key_exists($section,$sections)) $section='overview';
    $languageLink = fn($target) => '?'.http_build_query($screen==='workspace' ? ['section'=>$section,'lang'=>$target] : ['lang'=>$target]);
    $seed=strtoupper(substr(bin2hex(random_bytes(4)),0,6));
@endphp
<!doctype html><html lang="{{ $language==='pt'?'pt-BR':$language }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $say('Imagine CRM · Homologação privada','Imagine CRM · Private test environment','Imagine CRM · Entorno de pruebas privado') }}</title>
<style>
:root{--orange:#f9941d;--ink:#272727;--muted:#6b6b70;--bg:#f7f7f8;--line:#e6e2dc;--soft:#fff4e5}*{box-sizing:border-box}body{margin:0;color:var(--ink);background:var(--bg);font:15px/1.55 system-ui,-apple-system,Segoe UI,sans-serif}a{color:inherit;text-decoration:none}button,input,select{font:inherit}button,a.btn{cursor:pointer}a:hover{text-decoration:underline}button,.btn{border:1px solid #dc7d13;border-radius:10px;background:var(--orange);color:#212121;padding:12px 18px;font-weight:750;display:inline-flex;align-items:center;justify-content:center;min-height:46px;gap:8px}button.secondary,.btn.secondary{background:#fff;border-color:var(--line)}button.danger{background:#fff2ef;border-color:#e4b7ad;color:#7f2d20}.quiet{font-size:13px;color:var(--muted)}.environment{background:#292929;color:#fff;text-align:center;padding:7px 16px;font-size:11px;letter-spacing:.09em}.wrap{max-width:1440px;margin:auto;padding:0 32px}.top{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:24px 0;border-bottom:1px solid var(--line)}.brand{width:178px;height:auto;display:block}.toplinks{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.languages{display:flex;gap:5px}.languages a{padding:5px 8px;border-radius:7px;font-size:12px;font-weight:750;border:1px solid var(--line)}.languages a[aria-current=true]{background:var(--soft);border-color:var(--orange)}main{padding:40px 0 60px}.hero{display:grid;grid-template-columns:1.2fr 1fr;align-items:center;gap:60px;padding:60px 0 70px}.eyebrow{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.16em;color:#a35b0b}h1{font-size:clamp(32px,4.3vw,64px);line-height:1.06;letter-spacing:-.05em;margin:18px 0 24px}h1 em{font-style:normal;color:#b4650c}h2{font-size:26px;line-height:1.2;letter-spacing:-.035em;margin:0 0 18px}h3{font-size:17px;margin:0 0 10px}p{margin:0 0 18px}.hero p{font-size:18px;color:var(--muted);max-width:650px}.actions{display:flex;gap:12px;flex-wrap:wrap}.panel,.card,.auth{background:#fff;border:1px solid var(--line);border-radius:20px;padding:26px;box-shadow:0 8px 30px #27272704}.preview{transform:rotate(-2deg);padding:28px;background:linear-gradient(135deg,#fff,#fff5e8);box-shadow:0 25px 65px #27272712}.minirow{display:flex;gap:12px;margin:18px 0}.minibar{height:100px;width:25%;background:#f9d9ad;border-radius:10px;padding:12px;font-size:12px}.minibar:nth-child(2){background:#ffbf6c}.minibar:nth-child(3){background:#f9941d}.minibar:nth-child(4){background:#ece7df}.plans,.metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.planprice{font-size:32px;font-weight:850;letter-spacing:-.04em;margin:24px 0 12px}.tag{display:inline-block;background:var(--soft);color:#8d4e08;border-radius:7px;font-size:11px;font-weight:800;padding:5px 9px}.auth{max-width:540px;margin:20px auto;padding:36px}.auth.wide{max-width:760px}.auth h1{font-size:34px}.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}input,select{display:block;width:100%;padding:12px 13px;border:1px solid #d8d4ce;border-radius:9px;background:#fff;color:var(--ink);min-height:46px}input[type=checkbox]{display:inline-block;width:auto;min-height:0;margin-right:8px}input:focus,select:focus,button:focus,a:focus{outline:3px solid #f9941d66;outline-offset:3px}.field{margin-bottom:16px}.notice{border:1px solid #efca99;background:#fff7ec;padding:14px 18px;border-radius:12px;margin-bottom:22px;font-size:13px}.notice.error{border-color:#e6b8b0;background:#fff4f1;color:#832a20}.secret{padding:14px;background:#f7f7f8;border:1px dashed #c8c0b8;border-radius:9px;word-break:break-all;font:16px/1.7 ui-monospace,monospace}.shell{display:grid;grid-template-columns:220px minmax(0,1fr);gap:28px}.sidebar{position:sticky;top:20px;align-self:start;padding:18px;background:#fff;border:1px solid var(--line);border-radius:18px}.sidebar a{display:block;padding:11px 13px;border-radius:9px;color:#5b5b60;font-size:14px;margin:4px 0}.sidebar a[aria-current=true]{color:#8d4e08;background:var(--soft);font-weight:800}.workspacehead{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:24px}.workspacehead h1{font-size:36px;margin:5px 0 12px}.metric strong{font-size:33px;display:block;letter-spacing:-.04em}.metric span{font-size:12px;color:var(--muted)}.stack{display:grid;gap:22px}.tablewrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}td,th{text-align:left;padding:14px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}.inline{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.inline input,.inline select{width:auto;max-width:240px}.inline button{font-size:12px;padding:8px 12px;min-height:40px}.kanban{display:grid;grid-template-columns:repeat(5,minmax(180px,1fr));gap:14px;overflow:auto;padding-bottom:15px}.lane{background:#eeeae4;padding:15px;border-radius:14px;min-height:280px}.lane h3{font-size:13px;display:flex;justify-content:space-between}.opportunity{background:white;border:1px solid #ded8cf;border-radius:12px;padding:14px;margin-top:12px}.opportunity strong{font-size:12px;word-break:break-word}.opportunity button{font-size:11px;min-height:34px;padding:6px 10px;width:100%}.opportunity p{font-size:12px;margin:8px 0}.bar{height:10px;background:#eee7de;border-radius:8px;overflow:hidden}.bar span{height:100%;display:block;background:var(--orange)}.reportrow{margin:16px 0}.reportrow div{display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px}.empty{padding:35px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:12px}.footer{font-size:11px;color:#7a7a80;padding:22px 0 30px;border-top:1px solid var(--line);display:flex;justify-content:space-between;gap:15px}.smallbutton{padding:8px 12px;font-size:12px;min-height:38px}ul.features{padding-left:18px;color:var(--muted);font-size:14px}code{font-size:12px}form{margin:0}.full{width:100%}.mb{margin-bottom:22px}.mt{margin-top:22px}.break{overflow-wrap:anywhere}@media(max-width:1100px){.plans,.metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.hero{gap:25px}.shell{grid-template-columns:180px minmax(0,1fr)}}@media(max-width:760px){.wrap{padding:0 16px}.top{padding:18px 0;gap:14px;flex-wrap:wrap}.brand{width:145px}.toplinks{gap:8px}.toplinks .btn{font-size:12px;padding:9px 12px}.hero{grid-template-columns:1fr;gap:30px;padding:32px 0 42px}.hero h1{font-size:42px}.hero p{font-size:16px}.preview{transform:none}.shell{grid-template-columns:1fr;gap:18px}.sidebar{position:static;display:flex;gap:5px;overflow:auto;white-space:nowrap;padding:8px}.sidebar a{margin:0;padding:9px 12px;font-size:12px}.auth{padding:24px;margin:0}.grid2{grid-template-columns:1fr}.panel,.card{padding:20px}.workspacehead h1{font-size:29px}.workspacehead{flex-direction:column}.metric strong{font-size:27px}.kanban{grid-template-columns:repeat(5,210px)}.footer{flex-direction:column}.plans{grid-template-columns:1fr 1fr}.planprice{font-size:25px}.plans .card{padding:17px}main{padding:24px 0 40px}.inline input,.inline select{max-width:100%;width:100%}.inline{align-items:stretch}.inline button{width:100%}.metrics{gap:10px}}
.stack,.sidebar{min-width:0}.workspacehead .eyebrow{overflow-wrap:anywhere;word-break:break-word}@media(max-width:760px){.shell{grid-template-columns:minmax(0,1fr)}}
.automation-runs{min-width:0}.automation-runs>.tablewrap{max-width:100%;overflow-x:auto}.automation-runs button{max-width:100%;white-space:normal;overflow-wrap:anywhere}
.stack>[data-report-funnel-breakdown]{min-width:0}
.scrollhint{display:none;font-size:13px;color:var(--muted);margin:0 0 12px}.kanban:focus-visible,.tablewrap:focus-visible{outline:3px solid var(--orange);outline-offset:3px}@media(max-width:760px){.scrollhint{display:block}}
</style></head><body>
<div class="environment">{{ $say('HOMOLOGAÇÃO PRIVADA · DADOS FICTÍCIOS · NENHUMA COBRANÇA REAL','PRIVATE TEST ENVIRONMENT · FICTIONAL DATA · NO REAL CHARGES','ENTORNO DE PRUEBAS PRIVADO · DATOS FICTICIOS · SIN COBROS REALES') }}</div><div class="wrap">
<header class="top"><a href="/" aria-label="Imagine CRM"><img class="brand" src="/assets/logo-imagine.png" alt="Imagine"></a><div class="toplinks"><a href="https://comercial-dev.imaginecomunica.com.br/" class="quiet">← {{ $t('Voltar ao site') }}</a><div class="languages" aria-label="{{ $t('Idioma') }}"><a href="{{ $languageLink('pt') }}" aria-current="{{ $language==='pt'?'true':'false' }}">PT-BR</a><a href="{{ $languageLink('en') }}" aria-current="{{ $language==='en'?'true':'false' }}">EN</a><a href="{{ $languageLink('es') }}" aria-current="{{ $language==='es'?'true':'false' }}">ES</a></div>
@if(in_array($screen,['workspace','admin']))<form method="post" action="/logout">
@csrf<button class="secondary smallbutton">{{ $t('Sair') }}</button></form>
@else<a class="btn secondary" href="/login">{{ $t('Entrar') }} ↗</a>
@endif</div></header>
<main id="main">
@if($errors->any())<div class="notice error" role="alert">
@foreach($errors->all() as $error)<div>{{ $error }}</div>
@endforeach</div>
@endif
@if(session('notice'))<div class="notice" role="status">{{ session('notice') }}</div>
@endif
@if($screen==='home')
@include('crm-marketing-home')
@elseif($screen==='signup')
@php
    $login=old('login','lab-cliente-'.strtolower($seed));
@endphp
<section class="auth wide"><span class="eyebrow">{{ $t('14 dias para explorar') }}</span><h1>{{ $t('Criar meu teste') }}</h1><p class="quiet">{{ $t('Use exclusivamente a identidade fictícia abaixo. O CNPJ é um identificador de laboratório, sem consulta ou comprovação perante a Receita.') }}</p><form method="post" action="/cadastro">
@csrf<input type="hidden" name="plan" value="{{ session('crm.private.plan','basic') }}"><div class="grid2"><div class="field"><label for="login">{{ $t('Login fictício') }}</label><input id="login" name="login" value="{{ $login }}" pattern="lab-[a-z0-9-]{3,48}" required maxlength="52"></div><div class="field"><label for="password">{{ $t('Senha') }}</label><input id="password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required><small class="quiet">{{ $t('De 12 a 72 caracteres.') }}</small></div><div class="field"><label for="company">{{ $t('Empresa fictícia') }}</label><input id="company" name="company" value="{{ old('company','EMPRESA_TESTE_'.$seed) }}" pattern="EMPRESA_TESTE_[A-Z0-9_]{1,40}" required maxlength="64"></div><div class="field"><label for="cnpj">{{ $t('CNPJ de laboratório') }}</label><select id="cnpj" name="cnpj">
@foreach($data['documents'] as $doc)<option {{ old('cnpj',session('crm.private.suggested_document'))===$doc?'selected':'' }}>{{ $doc }}</option>
@endforeach</select></div><div class="field"><label for="email">{{ $t('E-mail de laboratório') }}</label><input id="email" name="email" type="email" value="{{ old('email',$login.'@example.test') }}" required><small class="quiet">{{ $t('O login seguido de @example.test.') }}</small></div><div class="field"><label for="phone">{{ $t('Telefone fictício') }}</label><input id="phone" name="phone" value="+5500000000000" readonly required></div></div><label class="field"><input type="checkbox" name="terms" value="simulated" required>{{ $t('Entendo que estes são termos simulados de teste, sem contratação real.') }}</label><button class="full">{{ $t('Continuar') }} →</button></form><p class="quiet mt">{{ $t('Trial: 2 usuários · 20 leads · 2 automações · 2 funis · 20 MB · créditos IA 10 + 10. Módulos de anexos e IA externa permanecem identificados como indisponíveis.') }}</p></section>
@elseif($screen==='invitation')
<section class="auth wide"><span class="eyebrow">{{ $t('Equipe · Convite privado') }}</span><h1>{{ $t('Mais uma pessoa, o mesmo processo.') }}</h1><p>{{ $t('Entregue este código ao usuário fictício. Ele deve abrir') }} <strong>/convite</strong> {{ $t('e colar o código no formulário. Nenhuma mensagem foi enviada.') }}</p><div class="secret mb" data-test="invitation-code">{{ $data['invitation'] }}</div><p class="quiet">{{ $t('Válido por 24 horas · uso único · conta adicional limitada ao mesmo tenant · perfil membro.') }}</p><a class="btn" href="/painel?section=team">{{ $t('Voltar à equipe') }}</a></section>
@elseif($screen==='invitation-signup')
@php
    $login=old('login','lab-equipe-'.strtolower($seed));
@endphp
<section class="auth"><span class="eyebrow">{{ $t('Equipe · Acesso por convite') }}</span><h1>{{ $t('Seu acesso à equipe.') }}</h1><form method="post" action="/convite">
@csrf<div class="field"><label for="crm-field-a11y-1">{{ $t('Código do convite privado') }}</label><input id="crm-field-a11y-1" name="invitation" required maxlength="250" autocomplete="off"></div><div class="field"><label for="crm-field-a11y-2">{{ $t('Login fictício') }}</label><input id="crm-field-a11y-2" name="login" value="{{ $login }}" required pattern="lab-[a-z0-9-]{3,48}" maxlength="52"></div><div class="field"><label for="crm-field-a11y-3">{{ $t('E-mail de laboratório') }}</label><input id="crm-field-a11y-3" name="email" type="email" value="{{ $login.'@example.test' }}" required></div><div class="field"><label for="crm-field-a11y-4">{{ $t('Senha') }}</label><input id="crm-field-a11y-4" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></div><label class="field"><input name="terms" type="checkbox" value="simulated" required>{{ $t('Entendo que este é um cadastro fictício, sem contratação real.') }}</label><button class="full">{{ $t('Continuar') }} →</button></form></section>
@elseif($screen==='otp')
<section class="auth"><span class="eyebrow">{{ $t('Verificação de laboratório') }}</span><h1>{{ $t('Código de verificação') }}</h1><div class="notice">{{ $t('Nenhum e-mail ou SMS foi enviado. A entrega exclusiva de teste ocorre nesta sessão privada.') }}</div><div class="secret mb" data-test="otp-code">{{ $data['code'] }}</div><p class="quiet">{{ $t('Válido por 15 minutos · até 5 tentativas · uso único.') }}</p><form method="post" action="/cadastro/verificar">
@csrf<div class="field"><label for="code">{{ $t('Código de verificação') }}</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"></div><button class="full">{{ $t('Confirmar') }} →</button></form><form method="post" action="/cadastro/reenviar" class="mt">
@csrf<button class="secondary full">{{ $t('Reenviar código de laboratório') }}</button></form><p class="quiet mt"><a href="/cadastro">{{ $t('Refazer cadastro') }}</a> · {{ $t('até três emissões por contato em 15 minutos.') }}</p></section>
@elseif($screen==='setup')
<section class="auth"><span class="eyebrow">{{ $t('Proteja o seu acesso') }}</span><h1>{{ $t('Configure o MFA') }}</h1><p class="quiet">{{ $t('Adicione esta chave em um autenticador TOTP. Ela só fica disponível enquanto o cadastro está pendente. O login exige senha e código do autenticador.') }}</p><div class="secret mb" data-test="setup-secret">{{ $data['secret'] }}</div><p class="quiet break">{{ $t('Conta') }}: {{ $data['login'] }} · Imagine CRM · {{ $t('TOTP 6 dígitos / 30 segundos') }}</p><form method="post" action="/cadastro/concluir">
@csrf<div class="field"><label for="code">{{ $t('Código do autenticador') }}</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"></div><button class="full">{{ $t('Cadastrar') }} →</button></form></section>
@elseif(in_array($screen,['registered','recovered']))
<section class="auth wide"><span class="eyebrow">{{ $t('Seu acesso') }}</span><h1>{{ $t($screen==='registered'?'Trial fictício iniciado.':'Senha atualizada.') }}</h1><p>{{ $t('Guarde os códigos de recuperação em local seguro. Cada um funciona uma única vez. Eles não serão exibidos novamente.') }}</p><div class="secret mb" data-test="recovery-codes">
@foreach($data['recovery_codes'] as $code)<div>{{ $code }}</div>
@endforeach</div>
@if(isset($data['login']))<p>{{ $t('Login') }}: <strong>{{ $data['login'] }}</strong></p>
@endif<p class="quiet">{{ $t('Após configurar o MFA, aguarde o próximo código de 30 segundos antes de entrar. Nenhuma cobrança foi criada.') }}</p><a class="btn" href="/login">{{ $t('Entrar') }} →</a></section>
@elseif($screen==='login')
<section class="auth"><span class="eyebrow">{{ $t('Seu espaço Imagine') }}</span><h1>{{ $t('Bem-vindo ao CRM.') }}</h1><p class="quiet">{{ $t('Entre para acompanhar seu processo comercial.') }}</p><form method="post" action="/login">
@csrf<div class="field"><label for="login">{{ $t('Login fictício') }}</label><input id="login" name="login" required maxlength="160" autocomplete="username" value="{{ old('login') }}"></div><div class="field"><label for="password">{{ $t('Senha') }}</label><input id="password" type="password" name="password" required maxlength="72" autocomplete="current-password"></div><button class="full">{{ $t('Continuar') }} →</button></form><div class="actions mt"><a class="quiet" href="/recuperar">{{ $t('Recuperar acesso') }}</a><a class="quiet" href="/cadastro">{{ $t('Criar meu teste') }}</a></div></section>
@elseif($screen==='mfa')
<section class="auth"><span class="eyebrow">{{ $t('Verificação em duas etapas') }}</span><h1>{{ $t('Código do autenticador') }}</h1><form method="post" action="/mfa">
@csrf<div class="field"><label for="code">{{ $t('Código do autenticador') }}</label><input id="code" name="code" required maxlength="32" autocomplete="one-time-code"></div><label class="field"><input type="checkbox" name="recovery" value="1">{{ $t('Estou usando um código de recuperação de uso único.') }}</label><button class="full">{{ $t('Entrar') }} →</button></form><a class="quiet mt" href="/login">← {{ $t('Voltar ao login') }}</a></section>
@elseif($screen==='recovery')
<section class="auth"><span class="eyebrow">{{ $t('Recuperação protegida') }}</span><h1>{{ $t('Recuperar acesso') }}</h1><p class="quiet">{{ $t('Use um código de recuperação que você guardou. A troca revoga todas as sessões e invalida os códigos antigos.') }}</p><form method="post" action="/recuperar">
@csrf<div class="field"><label for="crm-field-a11y-5">{{ $t('Login fictício') }}</label><input id="crm-field-a11y-5" name="login" required maxlength="52"></div><div class="field"><label for="crm-field-a11y-6">{{ $t('Código de recuperação') }}</label><input id="crm-field-a11y-6" name="code" required maxlength="24" autocomplete="off"></div><div class="field"><label for="crm-field-a11y-7">{{ $t('Nova senha') }}</label><input id="crm-field-a11y-7" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></div><button class="full">{{ $t('Confirmar') }} →</button></form></section>
@elseif($screen==='workspace')
@php
    $total=count($data['leads']);$done=count(array_filter($data['tasks'],fn($task)=>$task->state==='done'));
    $todo=count($data['tasks'])-$done; $days=max(0,(int)ceil(($data['trial']->expires_at-time())/86400));
@endphp
<div class="shell"><nav class="sidebar" aria-label="{{ $t('Menu do CRM') }}">
@foreach($sections as $key=>$label)<a href="/painel?section={{ $key }}" aria-current="{{ $section===$key?'true':'false' }}">{{ $label }}</a>
@endforeach</nav><div class="stack"><div class="workspacehead"><div><span class="eyebrow">{{ $data['account']->label }}</span><h1>{{ $sections[$section] }}</h1><p class="quiet">{{ $say('Seu processo, organizado. Dados fictícios salvos para seus testes.','Your workflow, organized. Fictional data is saved for your tests.','Tu proceso, organizado. Los datos ficticios se guardan para tus pruebas.') }}</p></div><span class="tag">{{ sprintf($say('Trial · %d dias restantes','Trial · %d days remaining','Prueba · %d días restantes'),$days) }}</span></div>
@if($section==='overview')
<div class="metrics"><div class="card metric"><span>{{ $t('Leads registrados') }}</span><strong>{{ $total }}</strong><span>{{ $t('de 20 no trial') }}</span></div><div class="card metric"><span>{{ $t('Tarefas abertas') }}</span><strong>{{ $todo }}</strong><span>{{ $t('próximos passos') }}</span></div><div class="card metric"><span>{{ $t('Tarefas concluídas') }}</span><strong>{{ $done }}</strong><span>{{ $t('histórico preservado') }}</span></div><div class="card metric"><span>{{ $t('Oportunidades ganhas') }}</span><strong>{{ count(array_filter($data['leads'],fn($lead)=>$lead->stage==='won')) }}</strong><span>{{ $t('neste ambiente fictício') }}</span></div></div><div class="panel"><h2>{{ $t('O próximo movimento começa aqui.') }}</h2><p class="quiet">{{ $t('Cadastre um lead, organize a conversa no Kanban e acompanhe a próxima ação.') }}</p><div class="actions"><a class="btn" href="/painel?section=leads">{{ $t('Criar lead') }} →</a><a class="btn secondary" href="/painel?section=kanban">{{ $t('Kanban') }} →</a></div></div>
@elseif($section==='leads')
<div class="panel"><h2>{{ $t('Criar lead') }}</h2><form method="post" action="/leads">
@csrf<div class="grid2"><div class="field"><label for="crm-field-a11y-8">{{ $t('Título') }}</label><input id="crm-field-a11y-8" name="title" required maxlength="64" pattern="LEAD_TESTE_[A-Z0-9_]{1,40}" value="LEAD_TESTE_{{ $seed }}"></div><div class="field"><label for="crm-field-a11y-9">{{ $t('Valor em centavos') }}</label><input id="crm-field-a11y-9" name="value_cents" type="number" min="0" max="100000000" required value="100000"></div></div><div class="field"><label for="crm-field-a11y-10">{{ $t('Funil') }}</label><select id="crm-field-a11y-10" name="funnel">
@foreach($data['funnels'] as $funnel)<option value="{{ $funnel->id }}">{{ $funnel->title }}</option>
@endforeach</select></div><button>{{ $t('Criar lead') }} →</button></form></div><div class="panel tablewrap"><table><thead><tr><th>{{ $t('Título') }}</th><th>{{ $t('Etapa') }}</th><th>{{ $t('Valor') }}</th><th>{{ $t('Editar') }}</th></tr></thead><tbody>
@forelse($data['leads'] as $lead)<tr data-lead-id="{{ $lead->id }}"><td>{{ $lead->title }}</td><td>{{ $stages[$lead->stage] }}</td><td>{{ $money($lead->value_cents) }}</td><td><form method="post" action="/leads/{{ $lead->id }}" class="inline">
@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><input name="title" value="{{ $lead->title }}" required maxlength="64" aria-label="{{ $t('Editar título do lead') }}"><button class="secondary">{{ $t('Salvar') }}</button></form></td></tr>
@empty<tr><td colspan="4" class="empty">{{ $t('Seu primeiro lead aparece aqui.') }}</td></tr>
@endforelse</tbody></table></div><div class="panel"><h3>{{ $t('CSV fictício') }}</h3><p class="quiet">{{ $t('Importação atômica, até 20 KB. Aceita o arquivo exportado com') }} <code>title,stage,value_cents</code> {{ $t('ou o formato simples') }} <code>title,value_cents</code>{{ $t('. Identificadores começam com LEAD_TESTE_. As etapas históricas são preservadas; a importação não dispara tarefas automáticas.') }}</p><form method="post" action="/importar" enctype="multipart/form-data" class="inline">
@csrf<input type="file" name="csv" accept=".csv,text/csv" required aria-label="{{ $t('Arquivo CSV fictício') }}"><button class="secondary">{{ $t('Importar CSV') }}</button><a class="btn secondary" href="/exportar">{{ $t('Exportar CSV') }}</a></form></div>
@elseif($section==='funnels')
<div class="panel"><h2>{{ $t('Funis comerciais · :count de 2',['count'=>count($data['funnels'])]) }}</h2>
@if($data['account']->team_role==='owner')<form method="post" action="/funis" class="inline">
@csrf<input name="title" value="FUNIL_TESTE_{{ $seed }}" required pattern="FUNIL_TESTE_[A-Z0-9_]{1,40}" maxlength="64" aria-label="{{ $t('Título do funil') }}"><button>{{ $t('Criar funil') }} →</button></form>
@endif</div><div class="grid2">
@foreach($data['funnels'] as $funnel)<article class="card" data-funnel-id="{{ $funnel->id }}"><span class="tag">{{ $t('Funil persistido') }}</span><h3 class="mt">{{ $funnel->title }}</h3><p class="quiet">{{ $t(':count oportunidades',['count'=>count(array_filter($data['leads'],fn($lead)=>$lead->funnel_id===$funnel->id))]) }}</p><a class="btn secondary" href="/painel?section=kanban&amp;funnel={{ $funnel->id }}">{{ $t('Abrir Kanban') }} →</a></article>
@endforeach</div>
@elseif($section==='kanban')
@php
    $visibleLeads=request('funnel')?array_values(array_filter($data['leads'],fn($lead)=>$lead->funnel_id===request('funnel'))):$data['leads'];
@endphp
<p class="scrollhint" data-scroll-hint="kanban">{{ $t('Deslize horizontalmente para ver todas as etapas.') }}</p><div class="kanban" role="region" tabindex="0" aria-label="{{ $t('Kanban') }}">
@foreach($stages as $stage=>$label)<section class="lane"><h3>{{ $label }} <span>{{ count(array_filter($visibleLeads,fn($lead)=>$lead->stage===$stage)) }}</span></h3>
@foreach($visibleLeads as $lead)
@if($lead->stage===$stage)<article class="opportunity" data-lead-id="{{ $lead->id }}"><strong>{{ $lead->title }}</strong><p>{{ $money($lead->value_cents) }}</p>
@if(!in_array($stage,['won','lost']))<form method="post" action="/leads/{{ $lead->id }}">
@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><input type="hidden" name="stage" value="{{ ['new'=>'qualified','qualified'=>'proposal','proposal'=>'won'][$stage] }}"><button>{{ $t('Avançar') }} →</button></form><form method="post" action="/leads/{{ $lead->id }}" class="mt">
@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><input type="hidden" name="stage" value="lost"><button class="secondary">{{ $t('Marcar perdido') }}</button></form>
@endif</article>
@endif
@endforeach</section>
@endforeach</div><p class="quiet">{{ $t('Cada movimento é persistido, versionado e registrado na auditoria. Alterações concorrentes exigem recarregar.') }}</p>
@elseif($section==='tasks')
<div class="panel"><h2>{{ $t('Criar tarefa') }}</h2><form method="post" action="/tarefas">
@csrf<div class="grid2"><div class="field"><label for="crm-field-a11y-11">{{ $t('Lead') }}</label><select id="crm-field-a11y-11" name="lead" required>
@foreach($data['leads'] as $lead)<option value="{{ $lead->id }}">{{ $lead->title }}</option>
@endforeach</select></div><div class="field"><label for="crm-field-a11y-12">{{ $t('Título') }}</label><input id="crm-field-a11y-12" name="title" value="TASK_TESTE_{{ $seed }}" required pattern="TASK_TESTE_[A-Z0-9_]{1,40}" maxlength="64"></div><div class="field"><label for="crm-field-a11y-13">{{ $t('Vencimento') }}</label><input id="crm-field-a11y-13" type="date" name="due" value="{{ gmdate('Y-m-d',time()+86400) }}" required></div></div><button>{{ $t('Criar tarefa') }} →</button></form></div><div class="panel tablewrap"><table><thead><tr><th>{{ $t('Título') }}</th><th>{{ $t('Vencimento') }}</th><th>{{ $t('Estado') }}</th><th>{{ $t('Ação') }}</th></tr></thead><tbody>
@forelse($data['tasks'] as $task)<tr data-task-id="{{ $task->id }}"><td>{{ $task->title }}</td><td>{{ $task->due_on }}</td><td data-task-state="{{ $task->state }}">{{ $displayLabel('task_state',$task->state) }}</td><td>
@if($task->state==='todo')<form method="post" action="/tarefas/{{ $task->id }}">
@csrf<input type="hidden" name="version" value="{{ $task->version }}"><button class="secondary smallbutton">{{ $t('Concluir') }}</button></form>
@endif</td></tr>
@empty<tr><td colspan="4" class="empty">{{ $t('Nenhuma tarefa registrada.') }}</td></tr>
@endforelse</tbody></table></div>
@elseif($section==='reports')
@php
    $wonCount=count(array_filter($data['leads'],fn($lead)=>$lead->stage==='won'));
    $winRate=$total>0 ? round($wonCount*100/$total,1) : 0.0;
    $reportSay=fn($pt,$en,$es)=>$language==='en'?$en:($language==='es'?$es:$pt);
@endphp
<section class="panel" data-report-methodology="imagine">
<span class="tag">{{ $reportSay('Metodologia Imagine','The Imagine Method','Metodología Imagine') }}</span>
<h2 class="mt">{{ $reportSay('Visitas → Leads → Clientes','Visits → Leads → Customers','Visitas → Leads → Clientes') }}</h2>
<p class="quiet">{{ $reportSay(
'Indicadores calculados somente a partir de oportunidades fictícias da empresa autenticada. Não há rastreamento de visitas neste CRM.',
'Metrics are calculated only from fictional opportunities of the authenticated company. This CRM does not track visits.',
'Indicadores calculados solo a partir de oportunidades ficticias de la empresa autenticada. Este CRM no rastrea visitas.'
) }}</p>
<div class="metrics">
<article class="card metric" data-report-stage="visits" data-report-visits="unavailable">
<span class="quiet">{{ $reportSay('Visitas','Visits','Visitas') }}</span>
<strong>—</strong><p class="quiet">{{ $reportSay('Não medidas neste CRM','Not measured in this CRM','No medidas en este CRM') }}</p>
</article>
<article class="card metric" data-report-stage="leads" data-report-leads="{{ $total }}">
<span class="quiet">Leads</span><strong>{{ $total }}</strong>
<p class="quiet">{{ $reportSay('Oportunidades fictícias da empresa','Fictional company opportunities','Oportunidades ficticias de la empresa') }}</p>
</article>
<article class="card metric" data-report-stage="customers" data-report-clients="{{ $wonCount }}">
<span class="quiet">{{ $reportSay('Clientes','Customers','Clientes') }}</span>
<strong>{{ $wonCount }}</strong>
<p class="quiet">{{ $reportSay('Negócios marcados como ganhos','Deals marked as won','Negocios marcados como ganados') }}</p>
</article>
<article class="card metric" data-report-stage="conversion" data-report-conversion="{{ $winRate }}">
<span class="quiet">{{ $reportSay('Ganhos por lead','Won per lead','Ganados por lead') }}</span>
<strong>{{ number_format($winRate,1,$language==='en'?'.':',',$language==='en'?',':'.') }}%</strong>
<p class="quiet">{{ $reportSay('Ganhos ÷ total de leads fictícios','Won ÷ all fictional leads','Ganados ÷ leads ficticios totales') }}</p>
</article>
</div>
<p class="quiet">{{ $reportSay(
'A contagem de clientes nesta demonstração representa oportunidades em etapa Ganho, não contratos, pagamentos ou clientes reais.',
'Customer count in this demo represents opportunities in the Won stage, not actual contracts, payments or customers.',
'El número de clientes en esta demostración representa oportunidades en etapa Ganado, no contratos, pagos ni clientes reales.'
) }}</p>
</section>
<section class="panel" data-report-funnel-breakdown="imagine">
<h2 id="crm-report-funnel-breakdown-title">{{ $reportSay('Resultados por funil','Results by pipeline','Resultados por embudo') }}</h2>
<p class="quiet">{{ $reportSay(
'Compare leads e negócios ganhos em cada funil da sua empresa. São dados fictícios; não há integração com tráfego ou faturamento real.',
'Compare leads and won deals in each pipeline of your company. These are fictional data; no real traffic or revenue is connected.',
'Compara leads y negocios ganados por embudo de tu empresa. Son datos ficticios; no existe integración con tráfico ni facturación real.'
) }}</p>
<div class="tablewrap" role="region" tabindex="0" aria-labelledby="crm-report-funnel-breakdown-title"><table>
<thead><tr>
<th>{{ $reportSay('Funil','Pipeline','Embudo') }}</th>
<th>Leads</th>
<th>{{ $reportSay('Ganhos','Won','Ganados') }}</th>
<th>{{ $reportSay('Conversão','Conversion','Conversión') }}</th>
</tr></thead><tbody>
@foreach($data['funnels'] as $funnel)
@php
    $funnelLeads=array_values(array_filter($data['leads'],fn($lead)=>$lead->funnel_id===$funnel->id));
    $funnelTotal=count($funnelLeads);
    $funnelWon=count(array_filter($funnelLeads,fn($lead)=>$lead->stage==='won'));
    $funnelRate=$funnelTotal>0?round(100*$funnelWon/$funnelTotal,1):0.0;
@endphp
<tr data-report-funnel-id="{{ $funnel->id }}" data-report-funnel-leads="{{ $funnelTotal }}" data-report-funnel-won="{{ $funnelWon }}" data-report-funnel-rate="{{ $funnelRate }}">
<td>{{ $funnel->title }}</td><td>{{ $funnelTotal }}</td><td>{{ $funnelWon }}</td>
<td>{{ number_format($funnelRate,1,$language==='en'?'.':',',$language==='en'?',':'.') }}%</td>
</tr>
@endforeach
</tbody></table></div>
<p class="quiet">{{ $reportSay(
'As taxas representam ganhos sobre oportunidades daquele funil. Um funil sem leads apresenta 0,0%, sem divisão por zero.',
'Rates represent won opportunities divided by leads in the same pipeline. Empty pipelines show 0.0%, with no division by zero.',
'Las tasas representan ganados entre leads de cada embudo. Un embudo vacío muestra 0,0%, sin división por cero.'
) }}</p>
</section>
<div class="panel"><h2>{{ $t('Seu funil em números.') }}</h2>
@foreach($stages as $stage=>$label)
@php
    $count=count(array_filter($data['leads'],fn($lead)=>$lead->stage===$stage));
@endphp<div class="reportrow"><div><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="bar"><span style="width:{{ $total?round($count/$total*100):0 }}%"></span></div></div>
@endforeach<div class="notice mt">{{ $t('Valor total das oportunidades:') }} <strong>{{ $money(array_sum(array_map(fn($lead)=>$lead->value_cents,$data['leads']))) }}</strong>. {{ $t('Indicadores calculados dos registros persistidos desta empresa.') }}</div><a class="btn secondary" href="/exportar">{{ $t('Exportar dados') }}</a></div>
@elseif($section==='team')
<div class="panel"><h2>{{ $t('Equipe e franquias') }}</h2><p class="quiet">{{ $t('Usuários da empresa: :count de 2.',['count'=>count($data['team'])]) }}</p><div class="tablewrap"><table><thead><tr><th>{{ $t('Identidade fictícia') }}</th><th>{{ $t('Perfil') }}</th><th>{{ $t('Estado') }}</th></tr></thead><tbody>
@foreach($data['team'] as $member)<tr><td>{{ substr($member->identity_id,0,8) }}…</td><td><span data-role="{{ $member->role }}">{{ $displayLabel('role',$member->role) }}</span> / <span data-team-role="{{ $member->team_role }}">{{ $displayLabel('team_role',$member->team_role) }}</span></td><td data-account-status="{{ $member->status }}">{{ $displayLabel('account_status',$member->status) }}</td></tr>
@endforeach</tbody></table></div>
@if($data['account']->team_role==='owner')<form method="post" action="/equipe/convidar" class="mt">
@csrf<button>{{ $t('Convidar usuário fictício') }} →</button></form><p class="quiet mt">{{ $t('Convites pendentes também reservam uma vaga. O ingresso exige OTP, MFA e convite válido de uso único.') }}</p>
@endif<p class="quiet mt">{{ $t('Cada usuário tem login independente. Nenhum perfil de cliente recebe acesso à administração global.') }}</p></div>
@elseif(in_array($section,['automation','settings']))
@php
    $automationAtLimit=$data['automation']->version>=20;
    $automationSay=$say;
    $automationNames=[
        'followup_qualified'=>$say('Lead qualificado','Qualified lead','Lead calificado'),
        'followup_proposal'=>$say('Proposta em andamento','Proposal in progress','Propuesta en curso'),
    ];
    $automationDescriptions=[
        'followup_qualified'=>$say('Ao qualificar um lead, criar uma tarefa interna para o próximo dia.','When a lead is qualified, create an internal task due the next day.','Al calificar un lead, crear una tarea interna para el día siguiente.'),
        'followup_proposal'=>$say('Ao avançar para proposta, criar uma tarefa interna para o próximo dia.','When a lead moves to proposal, create an internal task due the next day.','Al avanzar a propuesta, crear una tarea interna para el día siguiente.'),
    ];
    $automationStates=[
        'pending'=>$say('Agendada','Scheduled','Programada'),
        'completed'=>$say('Concluída','Completed','Completada'),
        'cancelled'=>$say('Cancelada','Cancelled','Cancelada'),
        'failed'=>$say('Falhou','Failed','Fallida'),
    ];
    $automationDelays=[
        0=>$say('Imediatamente','Immediately','Inmediatamente'),
        1=>$say('Após 1 segundo · teste','After 1 second · test','Después de 1 segundo · prueba'),
        60=>$say('Após 1 minuto','After 1 minute','Después de 1 minuto'),
        3600=>$say('Após 1 hora','After 1 hour','Después de 1 hora'),
        86400=>$say('Após 1 dia','After 1 day','Después de 1 día'),
    ];
    if (!array_key_exists($data['automation']->delaySeconds,$automationDelays)) {
        $automationDelays[$data['automation']->delaySeconds]=sprintf($say('Após %d segundos','After %d seconds','Después de %d segundos'),$data['automation']->delaySeconds);
    }
@endphp
<section class="stack" data-automation-locale="{{ $language }}">
<div class="panel"><span class="eyebrow">{{ $say('Central de automações · CRM','Automation center · CRM','Centro de automatizaciones · CRM') }}</span>
<h2>{{ $say('Seu próximo atendimento começa aqui.','Your next follow-up starts here.','Tu próximo seguimiento empieza aquí.') }}</h2>
<p>{{ $say('Escolha ações internas para acompanhar as oportunidades da sua empresa. O CRM aplica as regras; nenhuma inteligência artificial paga é acionada.','Choose internal actions to follow up on your company’s opportunities. The CRM applies the rules without using paid AI services.','Elige acciones internas para dar seguimiento a las oportunidades de tu empresa. El CRM aplica las reglas sin utilizar servicios de IA de pago.') }}</p>
<div class="metrics"><div class="card metric"><span>{{ $say('Regras ativas','Active rules','Reglas activas') }}</span><strong>{{ count($data['rules']) }} / 2</strong></div><div class="card metric"><span>{{ $say('Versão atual','Current version','Versión actual') }}</span><strong>{{ $data['automation']->version }}</strong></div><div class="card metric"><span>{{ $say('Chamadas de IA','AI calls','Llamadas de IA') }}</span><strong>0</strong></div></div>
@if($data['account']->team_role==='owner')<form method="post" action="/preferencias">
@if($automationAtLimit)
<p class="notice mt" id="automation-revision-limit" data-automation-revision-limit="20">{{ $automationSay(
'O limite de 20 revisões de automação foi atingido. As regras e o prazo ficam preservados; você pode continuar alterando o idioma.',
'The 20-revision automation limit has been reached. Rules and timing are preserved; you can still change the language.',
'Se alcanzó el límite de 20 revisiones de automatización. Se conservan las reglas y el plazo; puedes seguir cambiando el idioma.'
) }}</p>
<input type="hidden" name="delay_seconds" value="{{ $data['automation']->delaySeconds }}">
@foreach($data['rules'] as $selectedRule)<input type="hidden" name="rules[]" value="{{ $selectedRule }}">@endforeach
@endif
@csrf<input type="hidden" name="automation_version" value="{{ $data['automation']->version }}"><div class="field"><label for="automation-language">{{ $t('Idioma') }}</label><select id="automation-language" name="language">
@foreach(['pt'=>'Português','en'=>'English','es'=>'Español'] as $lang=>$name)<option value="{{ $lang }}" {{ ($data['preferences']->language??'pt')===$lang?'selected':'' }}>{{ $name }}</option>
@endforeach</select></div>
<div class="field"><label for="automation-delay">{{ $say('Quando executar a regra','When to run the rule','Cuándo ejecutar la regla') }}</label><select id="automation-delay" name="delay_seconds" {{ $automationAtLimit?'disabled aria-describedby=automation-revision-limit':'' }}>@foreach($automationDelays as $seconds=>$label)<option value="{{ $seconds }}" {{ $data['automation']->delaySeconds===$seconds?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<fieldset {{ $automationAtLimit?'disabled aria-describedby=automation-revision-limit':'' }}><legend>{{ $say('Modelos de acompanhamento','Follow-up templates','Plantillas de seguimiento') }}</legend>
@foreach($automationNames as $rule=>$name)<div class="card mt"><label><input type="checkbox" name="rules[]" value="{{ $rule }}" {{ in_array($rule,$data['rules'],true)?'checked':'' }}> <strong>{{ $name }}</strong></label><p>{{ $automationDescriptions[$rule] }}</p><span class="tag">{{ in_array($rule,$data['rules'],true)?$say('Ativa','Active','Activa'):$say('Pausada','Paused','Pausada') }}</span></div>
@endforeach</fieldset>
<p class="quiet mt">{{ $say('Desmarcar pausa novas execuções; tarefas já criadas permanecem no seu CRM. Os modelos não enviam e-mail ou WhatsApp. Editar ou pausar cancela execuções pendentes da configuração anterior. As versões impedem alterações por abas desatualizadas.','Uncheck a rule to pause new runs. Existing tasks stay in your CRM. These templates do not send email or WhatsApp messages. Changing or pausing a rule cancels pending runs from the previous configuration. Version checks prevent changes from outdated tabs.','Desmarca una regla para pausar nuevas ejecuciones. Las tareas ya creadas permanecen en tu CRM. Estas plantillas no envían correos ni mensajes de WhatsApp. Cambiar o pausar una regla cancela las ejecuciones pendientes de la configuración anterior. Las versiones evitan cambios desde pestañas desactualizadas.') }}</p>
<button type="submit" class="mt">{{ $automationAtLimit?$say('Salvar idioma','Save language','Guardar idioma'):$say('Salvar nova versão →','Save new version →','Guardar nueva versión →') }}</button></form>
@else<div class="notice">{{ $say('Seu perfil pode consultar as automações. Somente o responsável da empresa pode alterar as regras.','Your role can view automations. Only the company owner can change the rules.','Tu perfil puede consultar las automatizaciones. Solo el responsable de la empresa puede cambiar las reglas.') }}</div>
@endif</div>
<div class="panel"><h3>{{ $say('Histórico das configurações','Configuration history','Historial de configuración') }}</h3><p class="quiet">{{ $say('Até 20 revisões nesta candidata de homologação. Sem editor de workflows ou acesso ao n8n.','Up to 20 revisions in this private test version. No workflow editor or n8n access.','Hasta 20 revisiones en esta versión de prueba privada. Sin editor de flujos ni acceso a n8n.') }}</p>
<div class="tablewrap"><table><thead><tr><th>{{ $say('Versão','Version','Versión') }}</th><th>{{ $say('Quando (UTC)','When (UTC)','Cuándo (UTC)') }}</th><th>{{ $say('Modelos ativos','Active templates','Plantillas activas') }}</th></tr></thead><tbody>
@forelse(array_reverse($data['automation']->history) as $revision)<tr><td>{{ $revision['version'] }}</td><td>{{ gmdate('d/m/Y H:i',$revision['at']) }}</td><td>{{ count($revision['rules']) }} / 2</td></tr>
@empty<tr><td colspan="3" class="empty">{{ $say('Nenhuma revisão criada. As configurações anteriores foram preservadas.','No revisions yet. Previous settings have been preserved.','Todavía no hay revisiones. Se ha conservado la configuración anterior.') }}</td></tr>
@endforelse</tbody></table></div></div>
<div class="panel automation-runs"><h3>{{ $say('Execuções do motor nativo','Native automation runs','Ejecuciones de automatización nativa') }}</h3><p class="quiet">{{ $say('O processamento exige sua sessão e permissões atuais. Executa somente suas próprias tarefas agendadas. Sem worker operacional ou comunicação externa.','Processing requires your current session and permissions. It runs only your own scheduled tasks. There is no background worker or external communication.','El procesamiento requiere tu sesión y permisos actuales. Ejecuta solo tus propias tareas programadas. No hay ejecución en segundo plano ni comunicación externa.') }}</p>
<form method="post" action="/automacoes/processar">@csrf<button>{{ $say('Processar minhas execuções pendentes →','Process my pending runs →','Procesar mis ejecuciones pendientes →') }}</button></form>
<div class="tablewrap"><table><thead><tr><th>{{ $say('Modelo','Template','Plantilla') }}</th><th>{{ $say('Estado','Status','Estado') }}</th><th>{{ $say('Agendada (UTC)','Scheduled (UTC)','Programada (UTC)') }}</th><th>{{ $say('Tentativas','Attempts','Intentos') }}</th><th>{{ $say('Ação','Action','Acción') }}</th></tr></thead><tbody>
@forelse($data['automationRuns'] as $run)<tr data-automation-id="{{ $run->id }}"><td>{{ $automationNames[$run->template]??$say('Modelo indisponível','Template unavailable','Plantilla no disponible') }}</td><td data-automation-state="{{ $run->state }}">{{ $automationStates[$run->state]??$say('Indisponível','Unavailable','No disponible') }}</td><td>{{ gmdate('d/m H:i:s',$run->due_at) }}</td><td>{{ $run->attempts }}</td><td>@if($run->state==='pending' && $data['account']->team_role==='owner')<form method="post" action="/automacoes/{{ $run->id }}/cancelar">@csrf<button class="secondary smallbutton">{{ $say('Cancelar execução','Cancel run','Cancelar ejecución') }}</button></form>@endif</td></tr>
@empty<tr><td colspan="5" class="empty">{{ $say('Nenhuma execução registrada.','No runs recorded yet.','Todavía no hay ejecuciones registradas.') }}</td></tr>
@endforelse</tbody></table></div></div>
<div class="panel"><h3>{{ $say('Execuções e limites','Runs and limits','Ejecuciones y límites') }}</h3><p>{{ sprintf($say('Leads: %d/20 · Usuários: %d/2 · Automações: %d/2.','Leads: %d/20 · Users: %d/2 · Automations: %d/2.','Leads: %d/20 · Usuarios: %d/2 · Automatizaciones: %d/2.'),$total,count($data['team']),count($data['rules'])) }}</p>
<p class="quiet">{{ $say('Consulte as tarefas geradas no CRM e os eventos de auditoria em Relatórios. Esta tela usa somente o banco fictício de homologação.','View generated tasks in the CRM and audit events in Reports. This screen uses only the private test database.','Consulta las tareas generadas en el CRM y los eventos de auditoría en Informes. Esta pantalla usa solo la base de datos ficticia de pruebas.') }}</p><a class="btn secondary" href="/painel?section=tasks">{{ $say('Ver tarefas','View tasks','Ver tareas') }}</a> <a class="btn secondary" href="/painel?section=reports">{{ $say('Ver auditoria','View audit log','Ver auditoría') }}</a></div>
</section>
@elseif($section==='billing')
<div class="notice">{{ $t('Contratação e pagamento simulados. Nenhuma cobrança, assinatura financeira ou direito de cliente real é criado. Aprovar uma simulação não reinicia o trial.') }}</div><div class="panel"><h2>{{ $t('Plano e pedidos') }}</h2><form method="post" action="/pedidos">
@csrf<input type="hidden" name="key" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><div class="grid2"><div class="field"><label for="crm-field-a11y-15">{{ $t('Plano') }}</label><select id="crm-field-a11y-15" name="plan"><option value="basic">{{ $t('Básico · R$ 69/mês') }}</option><option value="essential" {{ ($data['account']->desired_plan??'basic')==='essential'?'selected':'' }}>{{ $t('Essencial · R$ 89/mês') }}</option><option value="professional" {{ ($data['account']->desired_plan??'basic')==='professional'?'selected':'' }}>{{ $t('Profissional · sob proposta') }}</option><option value="enterprise" {{ ($data['account']->desired_plan??'basic')==='enterprise'?'selected':'' }}>{{ $t('Empresarial · sob proposta') }}</option></select></div><div class="field"><label for="crm-field-a11y-16">{{ $t('Pagamento fictício') }}</label><select id="crm-field-a11y-16" name="method"><option value="pix">{{ $t('Pix simulado') }}</option><option value="card">{{ $t('Cartão simulado · sem dados de cartão') }}</option><option value="boleto">{{ $t('Boleto simulado') }}</option></select></div></div><button>{{ $t('Criar pedido simulado') }} →</button></form></div><div class="panel tablewrap"><table><thead><tr><th>{{ $t('Pedido') }}</th><th>{{ $t('Plano') }}</th><th>{{ $t('Estado simulado') }}</th><th>{{ $t('Ação de teste') }}</th></tr></thead><tbody>
@forelse($data['orders'] as $order)<tr data-order-id="{{ $order->id }}"><td>{{ substr($order->id,0,8) }}…</td><td data-plan="{{ $order->plan }}">{{ $displayLabel('plan',$order->plan) }}</td><td data-order-state="{{ $order->state }}">{{ $displayLabel('order_state',$order->state) }}</td><td>
@if($order->state==='pending')<div class="actions">
@foreach(['approved'=>'Aprovar','refused'=>'Recusar','cancelled'=>'Cancelar'] as $state=>$label)<form method="post" action="/pedidos/{{ $order->id }}/simular">
@csrf<input type="hidden" name="version" value="{{ $order->version }}"><input type="hidden" name="state" value="{{ $state }}"><button class="secondary smallbutton">{{ $t($label) }}</button></form>
@endforeach</div>
@endif</td></tr>
@empty<tr><td colspan="4" class="empty">{{ $t('Você ainda não criou um pedido simulado.') }}</td></tr>
@endforelse</tbody></table></div>
@endif
@if(in_array($section,['overview','reports']))<div class="panel tablewrap"><h3>{{ $t('Atividade recente') }}</h3><table><thead><tr><th>{{ $t('Quando (UTC)') }}</th><th>{{ $t('Ação') }}</th><th>{{ $t('Registro') }}</th></tr></thead><tbody>
@forelse($data['events'] as $event)<tr><td>{{ gmdate('d/m H:i:s',$event->occurred_at) }}</td><td data-activity-action="{{ $event->action }}">{{ $displayLabel('activity',$event->action) }}</td><td>{{ substr($event->object_id,0,8) }}…</td></tr>
@empty<tr><td colspan="3" class="empty">{{ $t('As operações aparecerão aqui.') }}</td></tr>
@endforelse</tbody></table></div>
@endif
</div></div>
@elseif($screen==='admin')
<section class="stack" data-admin-locale="{{ $language }}"><div class="workspacehead"><div><span class="eyebrow">{{ $t('Imagine CRM · Administração') }}</span><h1>{{ $t('Uma visão clara da operação.') }}</h1><p class="quiet">{{ $t('Contas, trials, pedidos fictícios e decisões administrativas auditadas.') }}</p></div><span class="tag">{{ $t('Administrador · MFA recente') }}</span></div><div class="metrics"><div class="card metric"><span>{{ $t('Contas fictícias') }}</span><strong>{{ count($data['accounts']) }}</strong></div><div class="card metric"><span>{{ $t('Ativas') }}</span><strong>{{ count(array_filter($data['accounts'],fn($a)=>$a->status==='active')) }}</strong></div><div class="card metric"><span>{{ $t('Suspensas') }}</span><strong>{{ count(array_filter($data['accounts'],fn($a)=>$a->status==='suspended')) }}</strong></div><div class="card metric"><span>{{ $t('Pedidos simulados') }}</span><strong>{{ count($data['orders']) }}</strong></div></div><p class="scrollhint" data-scroll-hint="admin">{{ $t('Deslize para ver as colunas e ações da tabela.') }}</p><div class="panel tablewrap" role="region" tabindex="0" aria-label="{{ $t('Contas e acesso') }}"><h2>{{ $t('Contas e acesso') }}</h2><table><thead><tr><th>{{ $t('Empresa fictícia') }}</th><th>{{ $t('Trial') }}</th><th>{{ $t('Estado') }}</th><th>{{ $t('Ação') }}</th></tr></thead><tbody>
@forelse($data['accounts'] as $account)<tr data-account-id="{{ $account->identity_id }}"><td>{{ $account->label }}<div class="quiet" data-team-role="{{ $account->team_role }}">{{ $displayLabel('team_role',$account->team_role) }}</div></td><td>{{ isset($data['trials'][$account->organization_id])?gmdate('d/m/Y',$data['trials'][$account->organization_id]->expires_at):'—' }}</td><td data-account-status="{{ $account->status }}">{{ $displayLabel('account_status',$account->status) }}</td><td><form method="post" action="/admin/contas/{{ $account->identity_id }}">
@csrf<input type="hidden" name="status" value="{{ $account->status==='active'?'suspended':'active' }}"><button class="{{ $account->status==='active'?'danger':'secondary' }} smallbutton">{{ $t($account->status==='active'?'Suspender':'Reativar') }}</button></form></td></tr>
@empty<tr><td colspan="4" class="empty">{{ $t('Nenhuma conta de cliente cadastrada.') }}</td></tr>
@endforelse</tbody></table></div><div class="notice">{{ $t('Suspender revoga as sessões existentes. Reativar exige novo login e MFA. Esta administração vê metadados operacionais fictícios; não abre leads e tarefas de outra empresa.') }}</div><div class="panel tablewrap"><h2>{{ $t('Pedidos simulados') }}</h2><table><thead><tr><th>{{ $t('Pedido') }}</th><th>{{ $t('Plano') }}</th><th>{{ $t('Método fictício') }}</th><th>{{ $t('Estado') }}</th></tr></thead><tbody>
@foreach($data['orders'] as $order)<tr><td>{{ substr($order->id,0,8) }}…</td><td data-plan="{{ $order->plan }}">{{ $displayLabel('plan',$order->plan) }}</td><td data-payment-method="{{ $order->payment_method }}">{{ $displayLabel('payment_method',$order->payment_method) }}</td><td data-order-state="{{ $order->state }}">{{ $displayLabel('order_state',$order->state) }}</td></tr>
@endforeach</tbody></table></div><div class="panel tablewrap"><h2>{{ $t('Auditoria administrativa') }}</h2><table><thead><tr><th>{{ $t('Quando (UTC)') }}</th><th>{{ $t('Ação') }}</th><th>{{ $t('Conta') }}</th></tr></thead><tbody>
@foreach($data['events'] as $event)<tr><td>{{ gmdate('d/m H:i:s',$event->occurred_at) }}</td><td data-activity-action="{{ $event->action }}">{{ $displayLabel('activity',$event->action) }}</td><td>{{ substr($event->subject_id,0,8) }}…</td></tr>
@endforeach</tbody></table></div></section>
@elseif($screen==='error')
<section class="auth"><span class="eyebrow">Imagine CRM</span><h1>{{ $t($data['status']===503?'Serviço temporariamente indisponível.':'Não foi possível concluir esta operação.') }}</h1><p>{{ $t(match($data['status']) {403=>'Seu perfil não tem permissão ou o trial foi encerrado.',404=>'O recurso não está disponível para esta conta.',409=>'O registro mudou ou uma franquia foi atingida. Recarregue e revise os dados.',422=>'Confira os dados e a transição solicitada.',default=>'Tente novamente ou entre no seu CRM.'}) }}</p><div class="actions"><a class="btn" href="/painel">{{ $t('Voltar ao CRM') }}</a><a class="btn secondary" href="/login">{{ $t('Entrar') }}</a></div><p class="quiet mt">{{ $t('Código') }} {{ $data['status'] }} · {{ $t('Nenhuma informação técnica ou credencial é exibida.') }}</p></section>
@endif
</main><footer class="footer"><span>{{ $say('Imagine Comunicação Digital · Tecnologia, estratégia e relacionamento.','Imagine Comunicação Digital · Technology, strategy and relationships.','Imagine Comunicação Digital · Tecnología, estrategia y relaciones.') }}</span><span>{{ $say('Homologação privada · Sem dados de clientes reais · Sem cobranças','Private test environment · No real customer data · No charges','Entorno de pruebas privado · Sin datos de clientes reales · Sin cobros') }}</span></footer></div></body></html>
