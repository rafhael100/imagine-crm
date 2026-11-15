
@php
    $en = ['Início'=>'Home','Entrar'=>'Sign in','Sair'=>'Sign out','Voltar ao site'=>'Back to website','Criar meu teste'=>'Start my trial',
    'Visão geral'=>'Overview','Leads'=>'Leads','Funis'=>'Funnels','Kanban'=>'Pipeline','Tarefas'=>'Tasks','Relatórios'=>'Reports','Equipe'=>'Team',
    'Automações'=>'Automations','Preferências'=>'Settings','Plano e pedidos'=>'Plan and orders','Administração'=>'Administration',
    'Login fictício'=>'Test username','Senha'=>'Password','Continuar'=>'Continue','Confirmar'=>'Confirm','Cadastrar'=>'Create account',
    'Empresa fictícia'=>'Test company','CNPJ de laboratório'=>'Test CNPJ','Código de verificação'=>'Verification code',
    'Código do autenticador'=>'Authenticator code','Código de recuperação'=>'Recovery code','Nova senha'=>'New password',
    'Recuperar acesso'=>'Recover access','Salvar'=>'Save','Criar lead'=>'Create lead','Criar tarefa'=>'Create task',
    'Novo'=>'New','Qualificado'=>'Qualified','Proposta'=>'Proposal','Ganho'=>'Won','Perdido'=>'Lost','Concluir'=>'Complete',
    'Título'=>'Title','Etapa'=>'Stage','Valor em centavos'=>'Amount in BRL cents','Vencimento'=>'Due date',
    'Criar pedido simulado'=>'Create simulated order','Aprovar'=>'Approve','Recusar'=>'Decline','Cancelar'=>'Cancel',
    'Suspender'=>'Suspend','Reativar'=>'Resume','Idioma'=>'Language','Atividade recente'=>'Recent activity'];
    $es = ['Início'=>'Inicio','Entrar'=>'Ingresar','Sair'=>'Salir','Voltar ao site'=>'Volver al sitio','Criar meu teste'=>'Iniciar prueba',
    'Visão geral'=>'Resumen','Leads'=>'Leads','Funis'=>'Embudos','Kanban'=>'Embudo','Tarefas'=>'Tareas','Relatórios'=>'Informes','Equipe'=>'Equipo',
    'Automações'=>'Automatizaciones','Preferências'=>'Preferencias','Plano e pedidos'=>'Plan y pedidos','Administração'=>'Administración',
    'Login fictício'=>'Usuario ficticio','Senha'=>'Contraseña','Continuar'=>'Continuar','Confirmar'=>'Confirmar','Cadastrar'=>'Registrar',
    'Empresa fictícia'=>'Empresa ficticia','CNPJ de laboratório'=>'CNPJ de laboratorio','Código de verificação'=>'Código de verificación',
    'Código do autenticador'=>'Código del autenticador','Código de recuperação'=>'Código de recuperación','Nova senha'=>'Nueva contraseña',
    'Recuperar acesso'=>'Recuperar acceso','Salvar'=>'Guardar','Criar lead'=>'Crear lead','Criar tarefa'=>'Crear tarea',
    'Novo'=>'Nuevo','Qualificado'=>'Calificado','Proposta'=>'Propuesta','Ganho'=>'Ganado','Perdido'=>'Perdido','Concluir'=>'Completar',
    'Título'=>'Título','Etapa'=>'Etapa','Valor em centavos'=>'Importe en centavos BRL','Vencimento'=>'Vencimiento',
    'Criar pedido simulado'=>'Crear pedido simulado','Aprovar'=>'Aprobar','Recusar'=>'Rechazar','Cancelar'=>'Cancelar',
    'Suspender'=>'Suspender','Reativar'=>'Reactivar','Idioma'=>'Idioma','Atividade recente'=>'Actividad reciente'];
    $t = fn($text) => $language==='en' ? ($en[$text]??$text) : ($language==='es' ? ($es[$text]??$text) : $text);
    $money=fn($cents)=>'R$ '.number_format($cents/100,2,',','.');
    $stages=['new'=>$t('Novo'),'qualified'=>$t('Qualificado'),'proposal'=>$t('Proposta'),'won'=>$t('Ganho'),'lost'=>$t('Perdido')];
    $sections=['overview'=>$t('Visão geral'),'leads'=>$t('Leads'),'funnels'=>$t('Funis'),'kanban'=>$t('Kanban'),'tasks'=>$t('Tarefas'),
        'reports'=>$t('Relatórios'),'team'=>$t('Equipe'),'automation'=>$t('Automações'),'settings'=>$t('Preferências'),'billing'=>$t('Plano e pedidos')];
    $section=request('section','overview'); if(!array_key_exists($section,$sections)) $section='overview';
    $seed=strtoupper(substr(bin2hex(random_bytes(4)),0,6));
@endphp
<!doctype html><html lang="{{ $language==='pt'?'pt-BR':$language }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Imagine CRM · Homologação privada</title>
<style>
:root{--orange:#f9941d;--ink:#272727;--muted:#6b6b70;--bg:#f7f7f8;--line:#e6e2dc;--soft:#fff4e5}*{box-sizing:border-box}body{margin:0;color:var(--ink);background:var(--bg);font:15px/1.55 system-ui,-apple-system,Segoe UI,sans-serif}a{color:inherit;text-decoration:none}button,input,select{font:inherit}button,a.btn{cursor:pointer}a:hover{text-decoration:underline}button,.btn{border:1px solid #dc7d13;border-radius:10px;background:var(--orange);color:#212121;padding:12px 18px;font-weight:750;display:inline-flex;align-items:center;justify-content:center;min-height:46px;gap:8px}button.secondary,.btn.secondary{background:#fff;border-color:var(--line)}button.danger{background:#fff2ef;border-color:#e4b7ad;color:#7f2d20}.quiet{font-size:13px;color:var(--muted)}.environment{background:#292929;color:#fff;text-align:center;padding:7px 16px;font-size:11px;letter-spacing:.09em}.wrap{max-width:1440px;margin:auto;padding:0 32px}.top{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:24px 0;border-bottom:1px solid var(--line)}.brand{width:178px;height:auto;display:block}.toplinks{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.languages{display:flex;gap:5px}.languages a{padding:5px 8px;border-radius:7px;font-size:12px;font-weight:750;border:1px solid var(--line)}.languages a[aria-current=true]{background:var(--soft);border-color:var(--orange)}main{padding:40px 0 60px}.hero{display:grid;grid-template-columns:1.2fr 1fr;align-items:center;gap:60px;padding:60px 0 70px}.eyebrow{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.16em;color:#a35b0b}h1{font-size:clamp(32px,4.3vw,64px);line-height:1.06;letter-spacing:-.05em;margin:18px 0 24px}h1 em{font-style:normal;color:#b4650c}h2{font-size:26px;line-height:1.2;letter-spacing:-.035em;margin:0 0 18px}h3{font-size:17px;margin:0 0 10px}p{margin:0 0 18px}.hero p{font-size:18px;color:var(--muted);max-width:650px}.actions{display:flex;gap:12px;flex-wrap:wrap}.panel,.card,.auth{background:#fff;border:1px solid var(--line);border-radius:20px;padding:26px;box-shadow:0 8px 30px #27272704}.preview{transform:rotate(-2deg);padding:28px;background:linear-gradient(135deg,#fff,#fff5e8);box-shadow:0 25px 65px #27272712}.minirow{display:flex;gap:12px;margin:18px 0}.minibar{height:100px;width:25%;background:#f9d9ad;border-radius:10px;padding:12px;font-size:12px}.minibar:nth-child(2){background:#ffbf6c}.minibar:nth-child(3){background:#f9941d}.minibar:nth-child(4){background:#ece7df}.plans,.metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.planprice{font-size:32px;font-weight:850;letter-spacing:-.04em;margin:24px 0 12px}.tag{display:inline-block;background:var(--soft);color:#8d4e08;border-radius:7px;font-size:11px;font-weight:800;padding:5px 9px}.auth{max-width:540px;margin:20px auto;padding:36px}.auth.wide{max-width:760px}.auth h1{font-size:34px}.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}input,select{display:block;width:100%;padding:12px 13px;border:1px solid #d8d4ce;border-radius:9px;background:#fff;color:var(--ink);min-height:46px}input[type=checkbox]{display:inline-block;width:auto;min-height:0;margin-right:8px}input:focus,select:focus,button:focus,a:focus{outline:3px solid #f9941d66;outline-offset:3px}.field{margin-bottom:16px}.notice{border:1px solid #efca99;background:#fff7ec;padding:14px 18px;border-radius:12px;margin-bottom:22px;font-size:13px}.notice.error{border-color:#e6b8b0;background:#fff4f1;color:#832a20}.secret{padding:14px;background:#f7f7f8;border:1px dashed #c8c0b8;border-radius:9px;word-break:break-all;font:16px/1.7 ui-monospace,monospace}.shell{display:grid;grid-template-columns:220px minmax(0,1fr);gap:28px}.sidebar{position:sticky;top:20px;align-self:start;padding:18px;background:#fff;border:1px solid var(--line);border-radius:18px}.sidebar a{display:block;padding:11px 13px;border-radius:9px;color:#5b5b60;font-size:14px;margin:4px 0}.sidebar a[aria-current=true]{color:#8d4e08;background:var(--soft);font-weight:800}.workspacehead{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:24px}.workspacehead h1{font-size:36px;margin:5px 0 12px}.metric strong{font-size:33px;display:block;letter-spacing:-.04em}.metric span{font-size:12px;color:var(--muted)}.stack{display:grid;gap:22px}.tablewrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}td,th{text-align:left;padding:14px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}.inline{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.inline input,.inline select{width:auto;max-width:240px}.inline button{font-size:12px;padding:8px 12px;min-height:40px}.kanban{display:grid;grid-template-columns:repeat(5,minmax(180px,1fr));gap:14px;overflow:auto;padding-bottom:15px}.lane{background:#eeeae4;padding:15px;border-radius:14px;min-height:280px}.lane h3{font-size:13px;display:flex;justify-content:space-between}.opportunity{background:white;border:1px solid #ded8cf;border-radius:12px;padding:14px;margin-top:12px}.opportunity strong{font-size:12px;word-break:break-word}.opportunity button{font-size:11px;min-height:34px;padding:6px 10px;width:100%}.opportunity p{font-size:12px;margin:8px 0}.bar{height:10px;background:#eee7de;border-radius:8px;overflow:hidden}.bar span{height:100%;display:block;background:var(--orange)}.reportrow{margin:16px 0}.reportrow div{display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px}.empty{padding:35px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:12px}.footer{font-size:11px;color:#7a7a80;padding:22px 0 30px;border-top:1px solid var(--line);display:flex;justify-content:space-between;gap:15px}.smallbutton{padding:8px 12px;font-size:12px;min-height:38px}ul.features{padding-left:18px;color:var(--muted);font-size:14px}code{font-size:12px}form{margin:0}.full{width:100%}.mb{margin-bottom:22px}.mt{margin-top:22px}.break{overflow-wrap:anywhere}@media(max-width:1100px){.plans,.metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.hero{gap:25px}.shell{grid-template-columns:180px minmax(0,1fr)}}@media(max-width:760px){.wrap{padding:0 16px}.top{padding:18px 0;gap:14px;flex-wrap:wrap}.brand{width:145px}.toplinks{gap:8px}.toplinks .btn{font-size:12px;padding:9px 12px}.hero{grid-template-columns:1fr;gap:30px;padding:32px 0 42px}.hero h1{font-size:42px}.hero p{font-size:16px}.preview{transform:none}.shell{grid-template-columns:1fr;gap:18px}.sidebar{position:static;display:flex;gap:5px;overflow:auto;white-space:nowrap;padding:8px}.sidebar a{margin:0;padding:9px 12px;font-size:12px}.auth{padding:24px;margin:0}.grid2{grid-template-columns:1fr}.panel,.card{padding:20px}.workspacehead h1{font-size:29px}.workspacehead{flex-direction:column}.metric strong{font-size:27px}.kanban{grid-template-columns:repeat(5,210px)}.footer{flex-direction:column}.plans{grid-template-columns:1fr 1fr}.planprice{font-size:25px}.plans .card{padding:17px}main{padding:24px 0 40px}.inline input,.inline select{max-width:100%;width:100%}.inline{align-items:stretch}.inline button{width:100%}.metrics{gap:10px}}
.stack,.sidebar{min-width:0}.workspacehead .eyebrow{overflow-wrap:anywhere;word-break:break-word}@media(max-width:760px){.shell{grid-template-columns:minmax(0,1fr)}}
</style></head><body>
<div class="environment">HOMOLOGAÇÃO PRIVADA · DADOS FICTÍCIOS · NENHUMA COBRANÇA REAL</div><div class="wrap">
<header class="top"><a href="/" aria-label="Imagine CRM"><img class="brand" src="/assets/logo-imagine.png" alt="Imagine"></a><div class="toplinks"><a href="https://comercial-dev.imaginecomunica.com.br/" class="quiet">← {{ $t('Voltar ao site') }}</a><div class="languages" aria-label="Idioma"><a href="?lang=pt" aria-current="{{ $language==='pt'?'true':'false' }}">PT-BR</a><a href="?lang=en" aria-current="{{ $language==='en'?'true':'false' }}">EN</a><a href="?lang=es" aria-current="{{ $language==='es'?'true':'false' }}">ES</a></div>
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
<section class="auth wide"><span class="eyebrow">14 dias para explorar</span><h1>{{ $t('Criar meu teste') }}</h1><p class="quiet">Use exclusivamente a identidade fictícia abaixo. O CNPJ é um identificador de laboratório, sem consulta ou comprovação perante a Receita.</p><form method="post" action="/cadastro">
@csrf<input type="hidden" name="plan" value="{{ session('crm.private.plan','basic') }}"><div class="grid2"><div class="field"><label for="login">{{ $t('Login fictício') }}</label><input id="login" name="login" value="{{ $login }}" pattern="lab-[a-z0-9-]{3,48}" required maxlength="52"></div><div class="field"><label for="password">{{ $t('Senha') }}</label><input id="password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required><small class="quiet">De 12 a 72 caracteres.</small></div><div class="field"><label for="company">{{ $t('Empresa fictícia') }}</label><input id="company" name="company" value="{{ old('company','EMPRESA_TESTE_'.$seed) }}" pattern="EMPRESA_TESTE_[A-Z0-9_]{1,40}" required maxlength="64"></div><div class="field"><label for="cnpj">{{ $t('CNPJ de laboratório') }}</label><select id="cnpj" name="cnpj">
@foreach($data['documents'] as $doc)<option {{ old('cnpj',session('crm.private.suggested_document'))===$doc?'selected':'' }}>{{ $doc }}</option>
@endforeach</select></div><div class="field"><label for="email">E-mail de laboratório</label><input id="email" name="email" type="email" value="{{ old('email',$login.'@example.test') }}" required><small class="quiet">O login seguido de @example.test.</small></div><div class="field"><label for="phone">Telefone fictício</label><input id="phone" name="phone" value="+5500000000000" readonly required></div></div><label class="field"><input type="checkbox" name="terms" value="simulated" required>Entendo que estes são termos simulados de teste, sem contratação real.</label><button class="full">{{ $t('Continuar') }} →</button></form><p class="quiet mt">Trial: 2 usuários · 20 leads · 2 automações · 2 funis · 20 MB · créditos IA 10 + 10. Módulos de anexos e IA externa permanecem identificados como indisponíveis.</p></section>
@elseif($screen==='invitation')
<section class="auth wide"><span class="eyebrow">Equipe · Convite privado</span><h1>Mais uma pessoa, o mesmo processo.</h1><p>Entregue este código ao usuário fictício. Ele deve abrir <strong>/convite</strong> e colar o código no formulário. Nenhuma mensagem foi enviada.</p><div class="secret mb" data-test="invitation-code">{{ $data['invitation'] }}</div><p class="quiet">Válido por 24 horas · uso único · conta adicional limitada ao mesmo tenant · perfil membro.</p><a class="btn" href="/painel?section=team">Voltar à equipe</a></section>
@elseif($screen==='invitation-signup')
@php
    $login=old('login','lab-equipe-'.strtolower($seed));
@endphp
<section class="auth"><span class="eyebrow">Equipe · Acesso por convite</span><h1>Seu acesso à equipe.</h1><form method="post" action="/convite">
@csrf<div class="field"><label>Código do convite privado</label><input name="invitation" required maxlength="250" autocomplete="off"></div><div class="field"><label>{{ $t('Login fictício') }}</label><input name="login" value="{{ $login }}" required pattern="lab-[a-z0-9-]{3,48}" maxlength="52"></div><div class="field"><label>E-mail de laboratório</label><input name="email" type="email" value="{{ $login.'@example.test' }}" required></div><div class="field"><label>{{ $t('Senha') }}</label><input name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></div><label class="field"><input name="terms" type="checkbox" value="simulated" required>Entendo que este é um cadastro fictício, sem contratação real.</label><button class="full">{{ $t('Continuar') }} →</button></form></section>
@elseif($screen==='otp')
<section class="auth"><span class="eyebrow">Verificação de laboratório</span><h1>{{ $t('Código de verificação') }}</h1><div class="notice">Nenhum e-mail ou SMS foi enviado. A entrega exclusiva de teste ocorre nesta sessão privada.</div><div class="secret mb" data-test="otp-code">{{ $data['code'] }}</div><p class="quiet">Válido por 15 minutos · até 5 tentativas · uso único.</p><form method="post" action="/cadastro/verificar">
@csrf<div class="field"><label for="code">{{ $t('Código de verificação') }}</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"></div><button class="full">{{ $t('Confirmar') }} →</button></form><form method="post" action="/cadastro/reenviar" class="mt">
@csrf<button class="secondary full">Reenviar código de laboratório</button></form><p class="quiet mt"><a href="/cadastro">Refazer cadastro</a> · até três emissões por contato em 15 minutos.</p></section>
@elseif($screen==='setup')
<section class="auth"><span class="eyebrow">Proteja o seu acesso</span><h1>Configure o MFA</h1><p class="quiet">Adicione esta chave em um autenticador TOTP. Ela só fica disponível enquanto o cadastro está pendente. O login exige senha e código do autenticador.</p><div class="secret mb" data-test="setup-secret">{{ $data['secret'] }}</div><p class="quiet break">Conta: {{ $data['login'] }} · Imagine CRM · TOTP 6 dígitos / 30 segundos</p><form method="post" action="/cadastro/concluir">
@csrf<div class="field"><label for="code">{{ $t('Código do autenticador') }}</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"></div><button class="full">{{ $t('Cadastrar') }} →</button></form></section>
@elseif(in_array($screen,['registered','recovered']))
<section class="auth wide"><span class="eyebrow">Seu acesso</span><h1>{{ $screen==='registered'?'Trial fictício iniciado.':'Senha atualizada.' }}</h1><p>Guarde os códigos de recuperação em local seguro. Cada um funciona uma única vez. Eles não serão exibidos novamente.</p><div class="secret mb" data-test="recovery-codes">
@foreach($data['recovery_codes'] as $code)<div>{{ $code }}</div>
@endforeach</div>
@if(isset($data['login']))<p>Login: <strong>{{ $data['login'] }}</strong></p>
@endif<p class="quiet">Após configurar o MFA, aguarde o próximo código de 30 segundos antes de entrar. Nenhuma cobrança foi criada.</p><a class="btn" href="/login">{{ $t('Entrar') }} →</a></section>
@elseif($screen==='login')
<section class="auth"><span class="eyebrow">Seu espaço Imagine</span><h1>Bem-vindo ao CRM.</h1><p class="quiet">Entre para acompanhar seu processo comercial.</p><form method="post" action="/login">
@csrf<div class="field"><label for="login">{{ $t('Login fictício') }}</label><input id="login" name="login" required maxlength="160" autocomplete="username" value="{{ old('login') }}"></div><div class="field"><label for="password">{{ $t('Senha') }}</label><input id="password" type="password" name="password" required maxlength="72" autocomplete="current-password"></div><button class="full">{{ $t('Continuar') }} →</button></form><div class="actions mt"><a class="quiet" href="/recuperar">{{ $t('Recuperar acesso') }}</a><a class="quiet" href="/cadastro">{{ $t('Criar meu teste') }}</a></div></section>
@elseif($screen==='mfa')
<section class="auth"><span class="eyebrow">Verificação em duas etapas</span><h1>{{ $t('Código do autenticador') }}</h1><form method="post" action="/mfa">
@csrf<div class="field"><label for="code">{{ $t('Código do autenticador') }}</label><input id="code" name="code" required maxlength="32" autocomplete="one-time-code"></div><label class="field"><input type="checkbox" name="recovery" value="1">Estou usando um código de recuperação de uso único.</label><button class="full">{{ $t('Entrar') }} →</button></form><a class="quiet mt" href="/login">← Voltar ao login</a></section>
@elseif($screen==='recovery')
<section class="auth"><span class="eyebrow">Recuperação protegida</span><h1>{{ $t('Recuperar acesso') }}</h1><p class="quiet">Use um código de recuperação que você guardou. A troca revoga todas as sessões e invalida os códigos antigos.</p><form method="post" action="/recuperar">
@csrf<div class="field"><label>{{ $t('Login fictício') }}</label><input name="login" required maxlength="52"></div><div class="field"><label>{{ $t('Código de recuperação') }}</label><input name="code" required maxlength="24" autocomplete="off"></div><div class="field"><label>{{ $t('Nova senha') }}</label><input name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></div><button class="full">{{ $t('Confirmar') }} →</button></form></section>
@elseif($screen==='workspace')
@php
    $total=count($data['leads']);$done=count(array_filter($data['tasks'],fn($task)=>$task->state==='done'));
    $todo=count($data['tasks'])-$done; $days=max(0,(int)ceil(($data['trial']->expires_at-time())/86400));
@endphp
<div class="shell"><nav class="sidebar" aria-label="Menu do CRM">
@foreach($sections as $key=>$label)<a href="/painel?section={{ $key }}" aria-current="{{ $section===$key?'true':'false' }}">{{ $label }}</a>
@endforeach</nav><div class="stack"><div class="workspacehead"><div><span class="eyebrow">{{ $data['account']->label }}</span><h1>{{ $sections[$section] }}</h1><p class="quiet">Seu processo, organizado. Dados fictícios persistidos no PostgreSQL.</p></div><span class="tag">Trial · {{ $days }} dias restantes</span></div>
@if($section==='overview')
<div class="metrics"><div class="card metric"><span>Leads registrados</span><strong>{{ $total }}</strong><span>de 20 no trial</span></div><div class="card metric"><span>Tarefas abertas</span><strong>{{ $todo }}</strong><span>próximos passos</span></div><div class="card metric"><span>Tarefas concluídas</span><strong>{{ $done }}</strong><span>histórico preservado</span></div><div class="card metric"><span>Oportunidades ganhas</span><strong>{{ count(array_filter($data['leads'],fn($lead)=>$lead->stage==='won')) }}</strong><span>neste ambiente fictício</span></div></div><div class="panel"><h2>O próximo movimento começa aqui.</h2><p class="quiet">Cadastre um lead, organize a conversa no Kanban e acompanhe a próxima ação.</p><div class="actions"><a class="btn" href="/painel?section=leads">{{ $t('Criar lead') }} →</a><a class="btn secondary" href="/painel?section=kanban">{{ $t('Kanban') }} →</a></div></div>
@elseif($section==='leads')
<div class="panel"><h2>{{ $t('Criar lead') }}</h2><form method="post" action="/leads">
@csrf<div class="grid2"><div class="field"><label>{{ $t('Título') }}</label><input name="title" required maxlength="64" pattern="LEAD_TESTE_[A-Z0-9_]{1,40}" value="LEAD_TESTE_{{ $seed }}"></div><div class="field"><label>{{ $t('Valor em centavos') }}</label><input name="value_cents" type="number" min="0" max="100000000" required value="100000"></div></div><div class="field"><label>Funil</label><select name="funnel">
@foreach($data['funnels'] as $funnel)<option value="{{ $funnel->id }}">{{ $funnel->title }}</option>
@endforeach</select></div><button>{{ $t('Criar lead') }} →</button></form></div><div class="panel tablewrap"><table><thead><tr><th>{{ $t('Título') }}</th><th>{{ $t('Etapa') }}</th><th>Valor</th><th>Editar</th></tr></thead><tbody>
@forelse($data['leads'] as $lead)<tr data-lead-id="{{ $lead->id }}"><td>{{ $lead->title }}</td><td>{{ $stages[$lead->stage] }}</td><td>{{ $money($lead->value_cents) }}</td><td><form method="post" action="/leads/{{ $lead->id }}" class="inline">
@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><input name="title" value="{{ $lead->title }}" required maxlength="64"><button class="secondary">{{ $t('Salvar') }}</button></form></td></tr>
@empty<tr><td colspan="4" class="empty">Seu primeiro lead aparece aqui.</td></tr>
@endforelse</tbody></table></div><div class="panel"><h3>CSV fictício</h3><p class="quiet">Importação atômica, até 20 KB. Aceita o arquivo exportado com <code>title,stage,value_cents</code> ou o formato simples <code>title,value_cents</code>. Identificadores começam com LEAD_TESTE_. As etapas históricas são preservadas; a importação não dispara tarefas automáticas.</p><form method="post" action="/importar" enctype="multipart/form-data" class="inline">
@csrf<input type="file" name="csv" accept=".csv,text/csv" required><button class="secondary">Importar CSV</button><a class="btn secondary" href="/exportar">Exportar CSV</a></form></div>
@elseif($section==='funnels')
<div class="panel"><h2>Funis comerciais · {{ count($data['funnels']) }} de 2</h2>
@if($data['account']->team_role==='owner')<form method="post" action="/funis" class="inline">
@csrf<input name="title" value="FUNIL_TESTE_{{ $seed }}" required pattern="FUNIL_TESTE_[A-Z0-9_]{1,40}" maxlength="64"><button>Criar funil →</button></form>
@endif</div><div class="grid2">
@foreach($data['funnels'] as $funnel)<article class="card" data-funnel-id="{{ $funnel->id }}"><span class="tag">Funil persistido</span><h3 class="mt">{{ $funnel->title }}</h3><p class="quiet">{{ count(array_filter($data['leads'],fn($lead)=>$lead->funnel_id===$funnel->id)) }} oportunidades</p><a class="btn secondary" href="/painel?section=kanban&amp;funnel={{ $funnel->id }}">Abrir Kanban →</a></article>
@endforeach</div>
@elseif($section==='kanban')
@php
    $visibleLeads=request('funnel')?array_values(array_filter($data['leads'],fn($lead)=>$lead->funnel_id===request('funnel'))):$data['leads'];
@endphp
<div class="kanban">
@foreach($stages as $stage=>$label)<section class="lane"><h3>{{ $label }} <span>{{ count(array_filter($visibleLeads,fn($lead)=>$lead->stage===$stage)) }}</span></h3>
@foreach($visibleLeads as $lead)
@if($lead->stage===$stage)<article class="opportunity" data-lead-id="{{ $lead->id }}"><strong>{{ $lead->title }}</strong><p>{{ $money($lead->value_cents) }}</p>
@if(!in_array($stage,['won','lost']))<form method="post" action="/leads/{{ $lead->id }}">
@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><input type="hidden" name="stage" value="{{ ['new'=>'qualified','qualified'=>'proposal','proposal'=>'won'][$stage] }}"><button>Avançar →</button></form><form method="post" action="/leads/{{ $lead->id }}" class="mt">
@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><input type="hidden" name="stage" value="lost"><button class="secondary">Marcar perdido</button></form>
@endif</article>
@endif
@endforeach</section>
@endforeach</div><p class="quiet">Cada movimento é persistido, versionado e registrado na auditoria. Alterações concorrentes exigem recarregar.</p>
@elseif($section==='tasks')
<div class="panel"><h2>{{ $t('Criar tarefa') }}</h2><form method="post" action="/tarefas">
@csrf<div class="grid2"><div class="field"><label>Lead</label><select name="lead" required>
@foreach($data['leads'] as $lead)<option value="{{ $lead->id }}">{{ $lead->title }}</option>
@endforeach</select></div><div class="field"><label>{{ $t('Título') }}</label><input name="title" value="TASK_TESTE_{{ $seed }}" required pattern="TASK_TESTE_[A-Z0-9_]{1,40}" maxlength="64"></div><div class="field"><label>{{ $t('Vencimento') }}</label><input type="date" name="due" value="{{ gmdate('Y-m-d',time()+86400) }}" required></div></div><button>{{ $t('Criar tarefa') }} →</button></form></div><div class="panel tablewrap"><table><thead><tr><th>{{ $t('Título') }}</th><th>{{ $t('Vencimento') }}</th><th>Estado</th><th>Ação</th></tr></thead><tbody>
@forelse($data['tasks'] as $task)<tr data-task-id="{{ $task->id }}"><td>{{ $task->title }}</td><td>{{ $task->due_on }}</td><td>{{ $task->state }}</td><td>
@if($task->state==='todo')<form method="post" action="/tarefas/{{ $task->id }}">
@csrf<input type="hidden" name="version" value="{{ $task->version }}"><button class="secondary smallbutton">{{ $t('Concluir') }}</button></form>
@endif</td></tr>
@empty<tr><td colspan="4" class="empty">Nenhuma tarefa registrada.</td></tr>
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
<h2>{{ $reportSay('Resultados por funil','Results by pipeline','Resultados por embudo') }}</h2>
<p class="quiet">{{ $reportSay(
'Compare leads e negócios ganhos em cada funil da sua empresa. São dados fictícios; não há integração com tráfego ou faturamento real.',
'Compare leads and won deals in each pipeline of your company. These are fictional data; no real traffic or revenue is connected.',
'Compara leads y negocios ganados por embudo de tu empresa. Son datos ficticios; no existe integración con tráfico ni facturación real.'
) }}</p>
<div class="tablewrap"><table>
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
<div class="panel"><h2>Seu funil em números.</h2>
@foreach($stages as $stage=>$label)
@php
    $count=count(array_filter($data['leads'],fn($lead)=>$lead->stage===$stage));
@endphp<div class="reportrow"><div><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="bar"><span style="width:{{ $total?round($count/$total*100):0 }}%"></span></div></div>
@endforeach<div class="notice mt">Valor total das oportunidades: <strong>{{ $money(array_sum(array_map(fn($lead)=>$lead->value_cents,$data['leads']))) }}</strong>. Indicadores calculados dos registros persistidos desta empresa.</div><a class="btn secondary" href="/exportar">Exportar dados</a></div>
@elseif($section==='team')
<div class="panel"><h2>Equipe e franquias</h2><p class="quiet">Usuários da empresa: {{ count($data['team']) }} de 2.</p><div class="tablewrap"><table><thead><tr><th>Identidade fictícia</th><th>Perfil</th><th>Estado</th></tr></thead><tbody>
@foreach($data['team'] as $member)<tr><td>{{ substr($member->identity_id,0,8) }}…</td><td>{{ $member->role }} / {{ $member->team_role }}</td><td>{{ $member->status }}</td></tr>
@endforeach</tbody></table></div>
@if($data['account']->team_role==='owner')<form method="post" action="/equipe/convidar" class="mt">
@csrf<button>Convidar usuário fictício →</button></form><p class="quiet mt">Convites pendentes também reservam uma vaga. O ingresso exige OTP, MFA e convite válido de uso único.</p>
@endif<p class="quiet mt">Cada usuário tem login independente. Nenhum perfil de cliente recebe acesso à administração global.</p></div>
@elseif(in_array($section,['automation','settings']))
<div class="panel"><h2>{{ $section==='automation'?'Próximas ações, no tempo certo.':$t('Preferências') }}</h2><form method="post" action="/preferencias">
@csrf<div class="field"><label>{{ $t('Idioma') }}</label><select name="language">
@foreach(['pt'=>'Português','en'=>'English','es'=>'Español'] as $lang=>$name)<option value="{{ $lang }}" {{ ($data['preferences']->language??'pt')===$lang?'selected':'' }}>{{ $name }}</option>
@endforeach</select></div><h3>Automações internas · {{ count($data['rules']) }} de 2</h3><label class="field"><input type="checkbox" name="rules[]" value="followup_qualified" {{ in_array('followup_qualified',$data['rules'],true)?'checked':'' }}>Criar tarefa ao qualificar um lead.</label><label class="field"><input type="checkbox" name="rules[]" value="followup_proposal" {{ in_array('followup_proposal',$data['rules'],true)?'checked':'' }}>Criar tarefa ao avançar para proposta.</label><p class="quiet">As tarefas são criadas de verdade no banco fictício. Não há e-mail, WhatsApp ou outra comunicação externa.</p><button>{{ $t('Salvar') }} →</button></form></div><div class="panel"><h3>Consumo do trial</h3><p>Leads: {{ $total }}/20 · Usuários: {{ count($data['team']) }}/2 · Automações: {{ count($data['rules']) }}/2 · Funis: {{ count($data['funnels']) }}/2</p><p class="quiet">Início: {{ gmdate('d/m/Y H:i',$data['trial']->started_at) }} UTC · Término: {{ gmdate('d/m/Y H:i',$data['trial']->expires_at) }} UTC.</p><div class="notice">Franquias de 20 MB de anexos e IA 10 + 10 são referências do trial. Upload de anexos e execução externa de IA não estão habilitados nesta homologação.</div></div>
@elseif($section==='billing')
<div class="notice">Contratação e pagamento simulados. Nenhuma cobrança, assinatura financeira ou direito de cliente real é criado. Aprovar uma simulação não reinicia o trial.</div><div class="panel"><h2>{{ $t('Plano e pedidos') }}</h2><form method="post" action="/pedidos">
@csrf<input type="hidden" name="key" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><div class="grid2"><div class="field"><label>Plano</label><select name="plan"><option value="basic">Básico · R$ 69/mês</option><option value="essential" {{ ($data['account']->desired_plan??'basic')==='essential'?'selected':'' }}>Essencial · R$ 89/mês</option><option value="professional" {{ ($data['account']->desired_plan??'basic')==='professional'?'selected':'' }}>Profissional · sob proposta</option><option value="enterprise" {{ ($data['account']->desired_plan??'basic')==='enterprise'?'selected':'' }}>Empresarial · sob proposta</option></select></div><div class="field"><label>Pagamento fictício</label><select name="method"><option value="pix">Pix simulado</option><option value="card">Cartão simulado · sem dados de cartão</option><option value="boleto">Boleto simulado</option></select></div></div><button>{{ $t('Criar pedido simulado') }} →</button></form></div><div class="panel tablewrap"><table><thead><tr><th>Pedido</th><th>Plano</th><th>Estado simulado</th><th>Ação de teste</th></tr></thead><tbody>
@forelse($data['orders'] as $order)<tr data-order-id="{{ $order->id }}"><td>{{ substr($order->id,0,8) }}…</td><td>{{ $order->plan }}</td><td>{{ $order->state }}</td><td>
@if($order->state==='pending')<div class="actions">
@foreach(['approved'=>'Aprovar','refused'=>'Recusar','cancelled'=>'Cancelar'] as $state=>$label)<form method="post" action="/pedidos/{{ $order->id }}/simular">
@csrf<input type="hidden" name="version" value="{{ $order->version }}"><input type="hidden" name="state" value="{{ $state }}"><button class="secondary smallbutton">{{ $t($label) }}</button></form>
@endforeach</div>
@endif</td></tr>
@empty<tr><td colspan="4" class="empty">Você ainda não criou um pedido simulado.</td></tr>
@endforelse</tbody></table></div>
@endif
@if(in_array($section,['overview','reports']))<div class="panel tablewrap"><h3>{{ $t('Atividade recente') }}</h3><table><thead><tr><th>Quando (UTC)</th><th>Ação</th><th>Registro</th></tr></thead><tbody>
@forelse($data['events'] as $event)<tr><td>{{ gmdate('d/m H:i:s',$event->occurred_at) }}</td><td>{{ $event->action }}</td><td>{{ substr($event->object_id,0,8) }}…</td></tr>
@empty<tr><td colspan="3" class="empty">As operações aparecerão aqui.</td></tr>
@endforelse</tbody></table></div>
@endif
</div></div>
@elseif($screen==='admin')
<section class="stack"><div class="workspacehead"><div><span class="eyebrow">Imagine CRM · Administração</span><h1>Uma visão clara da operação.</h1><p class="quiet">Contas, trials, pedidos fictícios e decisões administrativas auditadas.</p></div><span class="tag">Administrador · MFA recente</span></div><div class="metrics"><div class="card metric"><span>Contas fictícias</span><strong>{{ count($data['accounts']) }}</strong></div><div class="card metric"><span>Ativas</span><strong>{{ count(array_filter($data['accounts'],fn($a)=>$a->status==='active')) }}</strong></div><div class="card metric"><span>Suspensas</span><strong>{{ count(array_filter($data['accounts'],fn($a)=>$a->status==='suspended')) }}</strong></div><div class="card metric"><span>Pedidos simulados</span><strong>{{ count($data['orders']) }}</strong></div></div><div class="panel tablewrap"><h2>Contas e acesso</h2><table><thead><tr><th>Empresa fictícia</th><th>Trial</th><th>Estado</th><th>Ação</th></tr></thead><tbody>
@forelse($data['accounts'] as $account)<tr data-account-id="{{ $account->identity_id }}"><td>{{ $account->label }}<div class="quiet" data-team-role="{{ $account->team_role }}">{{ $account->team_role==='owner'?'Responsável':'Membro' }}</div></td><td>{{ isset($data['trials'][$account->organization_id])?gmdate('d/m/Y',$data['trials'][$account->organization_id]->expires_at):'—' }}</td><td>{{ $account->status }}</td><td><form method="post" action="/admin/contas/{{ $account->identity_id }}">
@csrf<input type="hidden" name="status" value="{{ $account->status==='active'?'suspended':'active' }}"><button class="{{ $account->status==='active'?'danger':'secondary' }} smallbutton">{{ $t($account->status==='active'?'Suspender':'Reativar') }}</button></form></td></tr>
@empty<tr><td colspan="4" class="empty">Nenhuma conta de cliente cadastrada.</td></tr>
@endforelse</tbody></table></div><div class="notice">Suspender revoga as sessões existentes. Reativar exige novo login e MFA. Esta administração vê metadados operacionais fictícios; não abre leads e tarefas de outra empresa.</div><div class="panel tablewrap"><h2>Pedidos simulados</h2><table><thead><tr><th>Pedido</th><th>Plano</th><th>Método fictício</th><th>Estado</th></tr></thead><tbody>
@foreach($data['orders'] as $order)<tr><td>{{ substr($order->id,0,8) }}…</td><td>{{ $order->plan }}</td><td>{{ $order->payment_method }}</td><td>{{ $order->state }}</td></tr>
@endforeach</tbody></table></div><div class="panel tablewrap"><h2>Auditoria administrativa</h2><table><thead><tr><th>Quando (UTC)</th><th>Ação</th><th>Conta</th></tr></thead><tbody>
@foreach($data['events'] as $event)<tr><td>{{ gmdate('d/m H:i:s',$event->occurred_at) }}</td><td>{{ $event->action }}</td><td>{{ substr($event->subject_id,0,8) }}…</td></tr>
@endforeach</tbody></table></div></section>
@elseif($screen==='error')
<section class="auth"><span class="eyebrow">Imagine CRM</span><h1>{{ $data['status']===503?'Serviço temporariamente indisponível.':'Não foi possível concluir esta operação.' }}</h1><p>{{ match($data['status']) {403=>'Seu perfil não tem permissão ou o trial foi encerrado.',404=>'O recurso não está disponível para esta conta.',409=>'O registro mudou ou uma franquia foi atingida. Recarregue e revise os dados.',422=>'Confira os dados e a transição solicitada.',default=>'Tente novamente ou entre no seu CRM.'} }}</p><div class="actions"><a class="btn" href="/painel">Voltar ao CRM</a><a class="btn secondary" href="/login">{{ $t('Entrar') }}</a></div><p class="quiet mt">Código {{ $data['status'] }} · Nenhuma informação técnica ou credencial é exibida.</p></section>
@endif
</main><footer class="footer"><span>Imagine Comunicação Digital · Tecnologia, estratégia e relacionamento.</span><span>Homologação privada · Sem dados de clientes reais · Sem cobranças</span></footer></div></body></html>
