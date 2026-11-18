@php
    $monitor=$data['executionMonitor'];
    $selected=$monitor['selected'];
    $executionStates=[
        'UNOBSERVED'=>$say('Sem retorno registrado','No response recorded','Sin respuesta registrada'),
        'NOT_STAGED'=>$say('Aguardando preparação','Awaiting preparation','Esperando preparación'),
        'NOT_STARTED'=>$say('Aguardando início','Awaiting start','Esperando inicio'),
        'STARTED'=>$say('Início registrado','Start recorded','Inicio registrado'),
        'RESULT'=>$say('Resultado recebido','Result received','Resultado recibido'),
        'ACKED'=>$say('Confirmação registrada','Confirmation recorded','Confirmación registrada'),
        'UNKNOWN'=>$say('Resultado incerto','Uncertain result','Resultado incierto'),
    ];
    $executionDescriptions=[
        'UNOBSERVED'=>$say('O CRM ainda não tem um retorno de execução para este evento.','The CRM has not recorded an execution response for this event.','El CRM aún no ha registrado una respuesta de ejecución para este evento.'),
        'NOT_STAGED'=>$say('O evento foi recebido e aguarda a preparação da tarefa.','The event was received and is awaiting task preparation.','El evento se recibió y espera la preparación de la tarea.'),
        'NOT_STARTED'=>$say('A tarefa foi preparada. Ainda não há registro de início.','The task is prepared. No start has been recorded yet.','La tarea está preparada. Todavía no se ha registrado el inicio.'),
        'STARTED'=>$say('A intenção de iniciar foi registrada. O resultado ainda não foi confirmado.','The intent to start was recorded. The result is not yet confirmed.','Se registró la intención de iniciar. El resultado aún no está confirmado.'),
        'RESULT'=>$say('O preparo técnico foi registrado e aguarda a confirmação final.','Technical preparation was recorded and awaits final confirmation.','Se registró la preparación técnica y se espera la confirmación final.'),
        'ACKED'=>$say('O preparo técnico e sua confirmação foram registrados.','Technical preparation and its confirmation were recorded.','Se registraron la preparación técnica y su confirmación.'),
        'UNKNOWN'=>$say('O resultado desta tentativa não pôde ser confirmado.','The result of this attempt could not be confirmed.','No se pudo confirmar el resultado de este intento.'),
    ];
    $executionQueues=[
        'NONE'=>$say('Sem tarefa','No task','Sin tarea'),
        'PENDING'=>$say('Na fila','Queued','En cola'),
        'LEASED'=>$say('Reservada para execução','Reserved for execution','Reservada para ejecución'),
        'ACKED'=>$say('Confirmada','Confirmed','Confirmada'),
        'DEAD'=>$say('Fila encerrada','Queue closed','Cola cerrada'),
    ];
    $executionUrl=fn($event=null)=>'/painel?'.http_build_query(array_filter(['section'=>'n8n','event'=>$event,'lang'=>$language],fn($v)=>$v!==null));
    $executionTime=fn($at)=>gmdate($language==='en'?'Y-m-d H:i:s':'d/m/Y H:i:s',$at);
    $executionDatetime=fn($at)=>gmdate('Y-m-d\TH:i:s\Z',$at);
    $executionConfirmed=0;$executionAttention=0;
    foreach($monitor['events'] as $entry) {
        $state=$entry['observation']['execution_state']??'UNOBSERVED';
        if($state==='ACKED') $executionConfirmed++;
        if($state==='UNKNOWN' || ($entry['observation']['queue_state']??null)==='DEAD') $executionAttention++;
    }
@endphp
<section class="execution-monitor stack" data-execution-monitor="{{ $language }}" aria-label="{{ $say('Acompanhamento das execuções n8n','n8n execution monitoring','Seguimiento de ejecuciones n8n') }}">
    <div class="execution-toolbar">
        <div>
            <h2>{{ $say('Cada retorno, no seu contexto.','Every response, in context.','Cada respuesta, en su contexto.') }}</h2>
            <p class="quiet">{{ $say('Acompanhe o último estado registrado e o histórico dos eventos da sua empresa.','View the last recorded status and the history of your company’s events.','Consulta el último estado registrado y el historial de los eventos de tu empresa.') }}</p>
        </div>
        <a class="btn secondary smallbutton" data-execution-refresh href="{{ $executionUrl($selected['event_id']??null) }}">{{ $say('Recarregar registros','Reload records','Recargar registros') }}</a>
    </div>

    @if($selected===null)
        <div class="execution-metrics" aria-label="{{ $say('Resumo dos eventos listados','Summary of listed events','Resumen de los eventos listados') }}">
            <div class="execution-metric"><span>{{ $say('Eventos listados','Listed events','Eventos listados') }}</span><strong>{{ count($monitor['events']) }}</strong><small>{{ sprintf($say('de %d registrados','of %d recorded','de %d registrados'),$monitor['total_events']) }}</small></div>
            <div class="execution-metric"><span>{{ $say('Com confirmação','With confirmation','Con confirmación') }}</span><strong>{{ $executionConfirmed }}</strong><small>{{ $say('nesta lista','in this list','en esta lista') }}</small></div>
            <div class="execution-metric"><span>{{ $say('Em acompanhamento','Being monitored','En seguimiento') }}</span><strong>{{ count($monitor['events'])-$executionConfirmed-$executionAttention }}</strong><small>{{ $say('nesta lista','in this list','en esta lista') }}</small></div>
            <div class="execution-metric attention"><span>{{ $say('Precisam de análise','Need review','Necesitan revisión') }}</span><strong>{{ $executionAttention }}</strong><small>{{ $say('nesta lista','in this list','en esta lista') }}</small></div>
        </div>
        <div class="execution-subtitle">
            <h3>{{ $say('Eventos recentes','Recent events','Eventos recientes') }}</h3>
            <p class="quiet">{{ $say('Até 50 eventos · Mais recentes primeiro','Up to 50 events · Newest first','Hasta 50 eventos · Más recientes primero') }}</p>
        </div>
        @if($monitor['events']===[])
            <div class="execution-empty" data-execution-empty>
                <h3>{{ $say('Seu acompanhamento começa aqui.','Your monitoring starts here.','Tu seguimiento empieza aquí.') }}</h3>
                <p>{{ $say('Os eventos encaminhados para integração aparecerão nesta área, junto com os retornos registrados pelo CRM.','Events submitted for integration will appear here with the responses recorded by the CRM.','Los eventos enviados a integración aparecerán aquí con las respuestas registradas por el CRM.') }}</p>
            </div>
        @else
            <div class="execution-list">
                @foreach($monitor['events'] as $entry)
                    @php $observation=$entry['observation'];$state=$observation['execution_state']??'UNOBSERVED'; @endphp
                    <article class="execution-card" data-execution-event="{{ $entry['event_id'] }}">
                        <div><span class="execution-reference">{{ $say('Evento','Event','Evento') }} · {{ strtoupper(substr($entry['event_id'],0,8)) }}</span><h3>{{ $entry['lead_title'] }}</h3></div>
                        <div><span class="execution-badge" data-execution-state="{{ $state }}">{{ $executionStates[$state] }}</span></div>
                        <p class="quiet">{{ $executionDescriptions[$state] }}</p>
                        <dl class="execution-facts">
                            <div><dt>{{ $say('Último retorno (UTC)','Last response (UTC)','Última respuesta (UTC)') }}</dt><dd>@if($observation)<time datetime="{{ $executionDatetime($observation['observed_at']) }}">{{ $executionTime($observation['observed_at']) }}</time>@else<span data-execution-unobserved>{{ $say('Ainda não registrado','Not recorded yet','Aún sin registrar') }}</span>@endif</dd></div>
                            <div><dt>{{ $say('Entrega do evento','Event delivery','Entrega del evento') }}</dt><dd>{{ $entry['delivery_state']==='accepted'?$say('Recebimento confirmado','Receipt confirmed','Recepción confirmada'):$say('Confirmação pendente','Confirmation pending','Confirmación pendiente') }}</dd></div>
                        </dl>
                        <div class="execution-card-foot"><a data-execution-open="{{ $entry['event_id'] }}" href="{{ $executionUrl($entry['event_id']) }}">{{ $say('Ver histórico','View history','Ver historial') }} <span aria-hidden="true">→</span></a><span>{{ sprintf($say('%d registros','%d records','%d registros'),$entry['history_count']) }}</span></div>
                    </article>
                @endforeach
            </div>
        @endif
    @else
        @php $observation=$selected['observation'];$state=$observation['execution_state']??'UNOBSERVED'; @endphp
        <a class="execution-back" href="{{ $executionUrl() }}">← {{ $say('Voltar aos eventos','Back to events','Volver a los eventos') }}</a>
        <article class="panel execution-detail" data-execution-selected="{{ $selected['event_id'] }}">
            <div class="execution-card-top"><div><span class="execution-reference">{{ $say('Último estado registrado','Last recorded status','Último estado registrado') }}</span><h2 class="mt">{{ $selected['lead_title'] }}</h2></div><span class="execution-badge" data-execution-state="{{ $state }}">{{ $executionStates[$state] }}</span></div>
            <p>{{ $executionDescriptions[$state] }}</p>
            <dl class="execution-facts">
                <div><dt>{{ $say('Entrega do evento','Event delivery','Entrega del evento') }}</dt><dd>{{ $selected['delivery_state']==='accepted'?$say('Recebimento confirmado','Receipt confirmed','Recepción confirmada'):$say('Confirmação pendente','Confirmation pending','Confirmación pendiente') }}</dd></div>
                <div><dt>{{ $say('Último retorno (UTC)','Last response (UTC)','Última respuesta (UTC)') }}</dt><dd>@if($observation)<time datetime="{{ $executionDatetime($observation['observed_at']) }}">{{ $executionTime($observation['observed_at']) }}</time>@else<span data-execution-unobserved>{{ $say('Ainda não registrado','Not recorded yet','Aún sin registrar') }}</span>@endif</dd></div>
                <div><dt>{{ $say('Tentativa registrada','Recorded attempt','Intento registrado') }}</dt><dd>{{ $observation?$observation['attempt']:'—' }}</dd></div>
                <div><dt>{{ $say('Estado da fila','Queue status','Estado de la cola') }}</dt><dd>{{ $observation?$executionQueues[$observation['queue_state']]:'—' }}</dd></div>
                <div><dt>{{ $say('Resultado técnico','Technical result','Resultado técnico') }}</dt><dd>{{ ($observation['result_code']??'NONE')==='PREPARED'?$say('Preparo registrado','Preparation recorded','Preparación registrada'):$say('Ainda não confirmado','Not confirmed yet','Aún sin confirmar') }}</dd></div>
                <div><dt>{{ $say('Registros no histórico','History records','Registros en el historial') }}</dt><dd>{{ $selected['history_count'] }}</dd></div>
            </dl>
            @if($state==='UNKNOWN' || ($observation['queue_state']??null)==='DEAD')
                <div class="execution-attention" role="note"><strong>{{ $say('Esta tentativa precisa de análise.','This attempt needs review.','Este intento necesita revisión.') }}</strong><p>{{ $state==='UNKNOWN'?$say('O resultado ficou incerto. Uma nova execução depende da análise da tentativa anterior.','The result is uncertain. A new execution requires a review of the previous attempt.','El resultado es incierto. Una nueva ejecución requiere revisar el intento anterior.'):$say('A fila desta tentativa foi encerrada. Consulte o último retorno antes de decidir o próximo passo.','The queue for this attempt was closed. Review the last response before deciding the next step.','La cola de este intento se cerró. Revisa la última respuesta antes de decidir el siguiente paso.') }}</p></div>
            @endif
        </article>
        <section class="panel" aria-labelledby="execution-history-title">
            <div class="execution-subtitle"><h3 id="execution-history-title">{{ $say('Histórico de retornos','Response history','Historial de respuestas') }}</h3><p class="quiet">{{ sprintf($say('%d de %d registros','%d of %d records','%d de %d registros'),count($selected['history']),$selected['history_count']) }}</p></div>
            @if($selected['history']===[])
                <p class="execution-note mt" data-execution-unobserved>{{ $executionDescriptions['UNOBSERVED'] }}</p>
            @else
                <ol class="execution-timeline" data-execution-history>
                    @foreach($selected['history'] as $item)
                        <li data-execution-sequence="{{ $item['sequence'] }}"><div class="execution-timeline-head"><span class="execution-badge" data-execution-state="{{ $item['execution_state'] }}">{{ $executionStates[$item['execution_state']] }}</span><time datetime="{{ $executionDatetime($item['observed_at']) }}">{{ $executionTime($item['observed_at']) }} UTC</time></div><p>{{ $executionDescriptions[$item['execution_state']] }}</p><small>{{ sprintf($say('Registro %d · Tentativa %d','Record %d · Attempt %d','Registro %d · Intento %d'),$item['sequence'],$item['attempt']) }} · {{ $executionQueues[$item['queue_state']] }}</small></li>
                    @endforeach
                </ol>
                <p class="execution-caption mt">{{ $say('Até 20 registros, do mais recente para o mais antigo.','Up to 20 records, from newest to oldest.','Hasta 20 registros, del más reciente al más antiguo.') }}</p>
            @endif
        </section>
        <div class="panel"><h3>{{ $say('Referência do evento','Event reference','Referencia del evento') }}</h3><p class="execution-full-reference">{{ $selected['event_id'] }}</p><p class="execution-caption">{{ $say('Use esta referência para localizar o mesmo evento ao revisar o acompanhamento.','Use this reference to identify the same event when reviewing its progress.','Usa esta referencia para localizar el mismo evento al revisar su seguimiento.') }}</p></div>
    @endif
    <p class="execution-note">{{ $say('Esta área mostra os retornos já salvos no CRM. O preparo técnico confirma uma etapa da integração; o andamento comercial do lead continua no CRM. Horários em UTC.','This area shows responses already saved in the CRM. Technical preparation confirms an integration step; the lead’s commercial progress remains in the CRM. Times are in UTC.','Esta área muestra respuestas ya guardadas en el CRM. La preparación técnica confirma una etapa de integración; el avance comercial del lead continúa en el CRM. Horarios en UTC.') }}</p>
</section>
