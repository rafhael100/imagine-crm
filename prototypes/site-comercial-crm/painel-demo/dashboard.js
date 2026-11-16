(() => {
  'use strict';
  // Review-only synthetic data. No remote calls, tokens, cookies or persistence.
  const VIEWS = Object.freeze({
    cliente: [
      ['visao-geral','Visão geral','▦','Acompanhe a operação comercial da empresa.','Nova atividade'],
      ['leads','Leads e contatos','◎','Consulte as oportunidades fictícias e seus estágios.','Novo lead'],
      ['kanban','Funil Kanban','☷','Visualize negócios em cada etapa do funil.','Nova oportunidade'],
      ['automacoes','Automações','◇','Veja exemplos de rotinas que poderão ser configuradas.','Criar automação'],
      ['relatorios','Relatórios','▤','Acompanhe indicadores demonstrativos.','Exportar relatório'],
      ['configuracoes','Configurações','⚙','Revise parâmetros fictícios do ambiente.','Salvar alterações']
    ],
    administrador: [
      ['visao-geral','Visão geral','▦','Acompanhe os indicadores gerais da administração Imagine.','Nova atividade'],
      ['empresas','Empresas','▣','Revise organizações fictícias e seus estados.','Nova empresa'],
      ['assinaturas','Assinaturas','◈','Veja planos e estados de cobrança simulados.','Gerenciar planos'],
      ['aprovacoes','Aprovações','◉','Acompanhe solicitações administrativas de exemplo.','Revisar solicitação'],
      ['auditoria','Auditoria','◷','Veja registros administrativos exclusivamente fictícios.','Exportar trilha'],
      ['configuracoes','Configurações','⚙','Consulte opções da administração Imagine.','Alterar configuração']
    ]
  });
  const deals = Object.freeze([
    ['Loja Exemplo', 'Contato inicial', 1400, 'Novo'],
    ['Oficina Horizonte', 'Site e CRM', 3500, 'Novo'],
    ['Mercado Modelo', 'Gestão comercial', 2100, 'Em contato'],
    ['Estúdio Aurora', 'Captação de leads', 4800, 'Em contato'],
    ['Papelaria Teste', 'Automação', 1700, 'Proposta'],
    ['Empresa Alpha', 'Plano CRM', 2950, 'Proposta'],
    ['Distribuidora Beta', 'Serviço mensal', 5400, 'Concluído']
  ]);
  const orgs = Object.freeze([
    ['Empresa Modelo Ltda.','Básico','Em avaliação'],
    ['Comércio Exemplo','Essencial','Ativo fictício'],
    ['Serviços Alpha','Profissional','Em revisão'],
    ['Grupo Demonstrativo','Empresarial','Ativo fictício']
  ]);
  const one = (selector) => document.querySelector(selector);
  const all = (selector) => Array.from(document.querySelectorAll(selector));
  const element = (tag, cls, text) => { const x=document.createElement(tag); if(cls)x.className=cls; if(text!==undefined)x.textContent=text; return x; };
  const brl = (n) => new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL',maximumFractionDigits:0}).format(n);
  let role = null;
  let active = null;
  let previousFocus = null;

  function status(value) { return '<span class="mini-status '+(value.includes('revisão')||value.includes('avaliação')?'amber':value.includes('fictício')?'':'gray')+'">'+value+'</span>'; }
  function stat(title,value,foot){return '<article class="stat"><span class="stat-top">'+title+'</span><strong class="stat-value">'+value+'</strong><span class="stat-foot">'+foot+'</span></article>';}
  function panel(title,body,detail='Exclusivamente ilustrativo'){return '<section class="panel"><div class="panel-title"><h2>'+title+'</h2><small>'+detail+'</small></div>'+body+'</section>';}
  function rows(headers,items) {
    return '<div class="panel table-scroll"><table class="data-table"><thead><tr>'+headers.map(h=>'<th scope="col">'+h+'</th>').join('')+'</tr></thead><tbody>'+items.map(row=>'<tr>'+row.map(cell=>'<td>'+cell+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>';
  }
  function charts() {
    return '<div class="bars">'+[['Prospecção','72%',72,''],['Contato','55%',55,'dark'],['Proposta','38%',38,''],['Conclusão','21%',21,'gray']].map(item=>
      '<div class="barrow"><div class="barlabels"><span>'+item[0]+'</span><b>'+item[1]+'</b></div><div class="track"><div class="fill '+item[3]+' w'+item[2]+'"></div></div></div>').join('')+'</div>';
  }
  function overviewClient(){
    const activities=[['L','Lead fictício adicionado','Há 2 horas — demonstração'],['P','Proposta de exemplo revisada','Ontem — demonstração'],['A','Automação simulada','Há 3 dias — demonstração']];
    return '<div class="stats">'+stat('Leads no funil','24','Número inventado')+stat('Oportunidades abertas','12','Dados de exemplo')+stat('Em propostas','05','Não corresponde a clientes')+stat('Conversão ilustrativa','21%','Sem vínculo comercial')+'</div>'+
    '<div class="view-grid">'+panel('Distribuição do funil',charts(),'Percentuais ilustrativos')+
    panel('Atividades recentes','<div class="activity">'+activities.map(([letter,title,sub])=>'<div class="activity-item"><span class="dot">'+letter+'</span><div><strong>'+title+'</strong><small>'+sub+'</small></div></div>').join('')+'</div>')+'</div>';
  }
  function overviewAdmin(){
    return '<div class="overview-note">O painel administrativo é apenas uma representação visual. Não existe acesso a empresas, assinaturas ou clientes verdadeiros.</div>'+
    '<div class="stats">'+stat('Empresas fictícias','04','Apenas exemplos')+stat('Planos em estudo','04','Preços provisórios')+stat('Solicitações de exemplo','03','Nenhuma fila real')+stat('Revisões simuladas','07','Sem evento registrado')+'</div>'+
    '<div class="view-grid">'+panel('Tipos de plano',charts(),'Contagem fictícia')+
    panel('Resumo de acompanhamento','<div class="activity">'+[['E','Empresa exemplo em revisão'],['S','Assinatura fictícia simulada'],['A','Aprovação de exemplo pendente']].map(x=>'<div class="activity-item"><span class="dot">'+x[0]+'</span><div><strong>'+x[1]+'</strong><small>Nenhuma alteração de contrato realizada</small></div></div>').join('')+'</div>')+'</div>';
  }
  function kanban(){
    const stages=['Novo','Em contato','Proposta','Concluído'];
    return '<div class="toolbar"><p>Arraste apenas com os olhos: os cartões não podem ser movidos ou gravados nesta prévia.</p><button type="button" data-action="Filtrar funil">Filtrar etapa</button></div>'+
    '<div class="board" role="region" tabindex="0" aria-label="Funil Kanban demonstrativo, deslocamento horizontal em telas pequenas">'+stages.map((stage,index)=>{
      const d=deals.filter(x=>x[3]===stage);
      return '<section class="lane" aria-label="'+stage+'"><div class="lane-head"><strong>'+stage+'</strong><span class="count">'+d.length+'</span></div>'+d.map(x=>'<article class="deal"><strong>'+x[0]+'</strong><p>'+x[1]+'</p><footer><span>DEMO</span><b>'+brl(x[2])+'</b></footer></article>').join('')+'</section>';
    }).join('')+'</div>';
  }
  function clientLeads(){
    return '<div class="toolbar"><p>Sete oportunidades inventadas, sem números de telefone ou e-mails reais.</p><button type="button" data-action="Importar leads">Importar CSV</button></div>'+
     rows(['Empresa fictícia','Interesse','Valor ilustrativo','Etapa'],deals.map(x=>[x[0],x[1],brl(x[2]),status(x[3])]));
  }
  function automations(){
    return '<div class="overview-note">Automações são exemplos visuais. Nenhuma mensagem, tarefa ou webhook será enviado.</div>'+
     rows(['Fluxo demonstrativo','Gatilho fictício','Status'],[
       ['Acompanhamento de lead','Lead sem contato',status('Em revisão')],
       ['Lembrete de proposta','Proposta em aberto',status('Desligado')],
       ['Registro de atendimento','Contato concluído',status('Desligado')]
     ]);
  }
  function reports(){
    return '<div class="stats">'+stat('Leads fictícios','24','Dados simulados')+stat('Propostas fictícias','05','Sem transação')+stat('Negócios exemplo','07','Apenas apresentação')+stat('Valor ilustrativo',brl(23850),'Não é receita real')+'</div>'+
     panel('Tendência de etapas',charts(),'Sem integração com banco');
  }
  function clientSettings(){
    return '<div class="setting-rows">'+[
      ['Empresa','Empresa Exemplo Ltda.','Demonstração'],
      ['Plano','Essencial · referência R$ 89/mês','Provisório'],
      ['Usuários','2 usuários na referência comercial','Sem contas'],
      ['Verificação de segurança','Autenticação e MFA aguardam homologação','Indisponível'],
      ['Exportação e backup','Previstos na especificação','Não configurado']
    ].map(x=>'<div class="setting"><div><strong>'+x[0]+'</strong><small>'+x[1]+'</small></div>'+status(x[2])+'</div>').join('')+'</div>';
  }
  function adminCompanies() {
    return rows(['Organização fictícia','Plano de referência','Estado ilustrativo'],orgs.map(x=>[x[0],x[1],status(x[2])]));
  }
  function adminSubscriptions(){
    return '<div class="overview-note">Nenhuma assinatura, cartão, Pix, boleto, fatura ou nota fiscal foi criada.</div>'+
    rows(['Organização fictícia','Preço de planejamento','Condição'],[
      ['Empresa Modelo Ltda.',brl(69)+'/mês',status('Não contratada')],
      ['Comércio Exemplo',brl(89)+'/mês',status('Prévia apenas')],
      ['Serviços Alpha',brl(149)+'/mês',status('Em revisão')],
      ['Grupo Demonstrativo',brl(299)+'/mês',status('Em revisão')]
    ]);
  }
  function adminApprovals(){
    return rows(['Solicitação fictícia','Categoria','Estado'],[
      ['Aprovação de plano','Planos',status('Em avaliação')],
      ['Revisão de acessos','Segurança',status('Em revisão')],
      ['Validar etapa de cadastro','Homologação',status('Em revisão')]
    ]);
  }
  function adminAudit(){
    return '<div class="overview-note">Eventos demonstrativos não representam trilha de auditoria real.</div>'+
    rows(['Evento ilustrativo','Origem','Resultado'],[
      ['Abertura do painel fictício','Protótipo',status('Não persistido')],
      ['Consulta a empresas de exemplo','Protótipo',status('Não persistido')],
      ['Simulação de pedido de alteração','Protótipo',status('Não executado')]
    ]);
  }
  function adminSettings(){
    return '<div class="setting-rows">'+[
      ['Proteção de dados','LGPD, retenção e registros sujeitos a gate formal','Em revisão'],
      ['Financeiro','Gateway e emissão fiscal não configurados','Indisponível'],
      ['Autenticação','MFA e credenciais reais ainda não habilitados','Indisponível'],
      ['Infraestrutura','VPS e HTTPS precisam homologação externa','Em revisão']
    ].map(x=>'<div class="setting"><div><strong>'+x[0]+'</strong><small>'+x[1]+'</small></div>'+status(x[2])+'</div>').join('')+'</div>';
  }
  function contentFor(name) {
    if(role==='cliente'){
      return ({'visao-geral':overviewClient,'leads':clientLeads,'kanban':kanban,
        'automacoes':automations,'relatorios':reports,'configuracoes':clientSettings})[name]?.() ?? '';
    }
    return ({'visao-geral':overviewAdmin,'empresas':adminCompanies,'assinaturas':adminSubscriptions,
      'aprovacoes':adminApprovals,'auditoria':adminAudit,'configuracoes':adminSettings})[name]?.() ?? '';
  }
  function setView(name) {
    const item=VIEWS[role]?.find(x=>x[0]===name);
    if(!item)return;
    active=name;
    one('#section-title').textContent=item[1];
    one('#crumb').textContent=item[1];
    one('#section-desc').textContent=item[3];
    one('#section-kicker').textContent=role==='cliente'?'PAINEL DO CLIENTE':'ADMINISTRAÇÃO IMAGINE';
    one('#section-cta').textContent=item[4]+' +';
    one('#section-cta').dataset.action=item[4];
    one('#view').innerHTML=contentFor(name);
    all('.nav-link').forEach(btn=>{const selected=btn.dataset.view===name;btn.classList.toggle('active',selected);btn.setAttribute('aria-current',selected?'page':'false')});
    one('#sidebar').classList.remove('open');
    one('#menu-toggle').setAttribute('aria-expanded','false');
    one('#menu-toggle').setAttribute('aria-label','Abrir menu');
    one('#content').focus({preventScroll:true});
  }
  function enter(selected) {
    if(!Object.prototype.hasOwnProperty.call(VIEWS,selected))return;
    role=selected;
    one('#entry').hidden=true;
    one('#workspace').hidden=false;
    one('#tenant-title').textContent=role==='cliente'?'Empresa Exemplo Ltda.':'Administração Imagine';
    one('#tenant-subtitle').textContent=role==='cliente'?'Ambiente fictício':'Ambiente administrativo fictício';
    const nav=one('#sidebar-nav');nav.replaceChildren();
    for(const [key,label,emoji] of VIEWS[role]){
      const btn=element('button','nav-link');
      btn.type='button';btn.dataset.view=key;
      const ico=element('span','nav-ico',emoji);ico.setAttribute('aria-hidden','true');
      btn.append(ico,document.createTextNode(label));nav.append(btn);
    }
    setView('visao-geral');
  }
  function exitDemo() {
    role=null;active=null;
    one('#workspace').hidden=true;
    one('#entry').hidden=false;
    one('#sidebar').classList.remove('open');
    const old=one('#menu-toggle');old.setAttribute('aria-expanded','false');old.setAttribute('aria-label','Abrir menu');
    one('[data-enter="cliente"]').focus();
  }
  function showNotice(source) {
    previousFocus=source;
    const action=source.dataset.action || 'esta função';
    one('#modal-text').textContent='Você selecionou “'+action+'”. O ambiente é apenas uma demonstração visual. Nenhum usuário, oportunidade, assinatura, mensagem ou dado real será alterado.';
    one('#modal').hidden=false;
    one('#modal-x').focus();
  }
  function closeNotice() {
    one('#modal').hidden=true;
    previousFocus?.focus();
    previousFocus=null;
  }
  document.addEventListener('click',(e)=>{
    const btn=e.target.closest('button');
    if(!btn)return;
    if(btn.dataset.enter){enter(btn.dataset.enter);return}
    if(btn.dataset.view){setView(btn.dataset.view);return}
    if(btn.hasAttribute('data-exit')){exitDemo();return}
    if(btn.id==='menu-toggle'){
      const open=one('#sidebar').classList.toggle('open');
      btn.setAttribute('aria-expanded',String(open));
      btn.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');
      return;
    }
    if(btn.id==='modal-x'||btn.id==='modal-done'){closeNotice();return}
    if(btn.dataset.action){showNotice(btn);return}
  });
  document.addEventListener('keydown',(e)=>{
    if(one('#modal').hidden){
      if(e.key==='Escape' && one('#sidebar').classList.contains('open')){
        one('#sidebar').classList.remove('open');
        one('#menu-toggle').setAttribute('aria-expanded','false');
        one('#menu-toggle').focus();
      }
      return;
    }
    if(e.key==='Escape'){e.preventDefault();closeNotice();return}
    if(e.key==='Tab'){
      const first=one('#modal-x'),last=one('#modal-done');
      if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}
      else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
    }
  });
  one('#modal').addEventListener('click',(e)=>{if(e.target===one('#modal'))closeNotice()});
})();