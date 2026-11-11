<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OfflineKanbanFunnelTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    public function test_fiction_only_four_stage_board_with_eight_opportunities(): void
    {
        $html = self::html();
        self::assertSame(4, substr_count($html, '<section class="lane"'));
        self::assertSame(8, preg_match_all(
            "/Object\\.freeze\\(\\{id:'F[0-9]{2}'/",
            $html,
        ));
        self::assertStringContainsString('Funil de vendas', $html);
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        foreach ([
            'Entrada', 'Em contato', 'Qualificação', 'Proposta',
            'Oportunidade fictícia 08', 'Ações simuladas nesta aba',
            'Desfazer ação', 'Reiniciar simulação',
            'data-priority="all"', 'data-priority="high"', 'data-priority="normal"',
            'id="simulation-status"', 'aria-live="polite"',
            'id="count-visible"', 'id="sum-visible"',
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
    }

    public function test_interaction_is_explicitly_in_memory_without_service_effects(): void
    {
        $html = self::html();
        foreach ([
            'history.push(', "history.pop()",
            "opportunities=FIXTURES.map(",
            "item.stage=next;",
            "target.replaceChildren();",
            "element.textContent=String(text);",
            "button.setAttribute('aria-pressed',",
            "button.disabled=item.stage+step",
            "byId('board').addEventListener('click'",
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringContainsString("frame-src 'none'", $html);
        foreach ([
            '<form', '<input', '<textarea', '<iframe', '<img', '<link',
            'XMLHttpRequest', 'WebSocket', 'EventSource', 'localStorage',
            'sessionStorage', 'fetch(', 'navigator.sendBeacon', 'window.open(',
            'document.cookie', 'indexedDB', '.innerHTML', '.outerHTML',
            'insertAdjacentHTML', 'document.write', '<script src=',
            '@import', 'url(', 'download=', 'onload=', 'onclick=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        preg_match_all('/\bhref="([^"]+)"/', $html, $links);
        self::assertSame(['#conteudo', '../s01-prot-01/index.html'], $links[1]);
    }

    public function test_in_memory_creation_is_bounded_reversible_and_without_user_input(): void
    {
        $html = self::html();
        foreach ([
            'id="add-fiction"',
            'Adicionar oportunidade fictícia',
            'Ações simuladas nesta aba',
            "history.push({kind:'add',id:item.id});",
            "history.push({kind:'move',id:item.id,from:item.stage});",
            "opportunities.length>=12",
            "opportunities=opportunities.filter(",
            "nextGenerated=9;",
            "priority='all';",
            "render(item.id);",
            "render('undo');",
            'Limite de doze oportunidades fictícias atingido',
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        foreach (['<form', '<input', '<textarea', 'localStorage', 'fetch('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_every_stage_exposes_live_fictional_value_totals(): void
    {
        $html = self::html();
        for ($stage = 0; $stage < 4; ++$stage) {
            self::assertSame(1, substr_count($html, 'id="lane-value-'.$stage.'"'));
        }
        self::assertSame(4, substr_count($html, 'Valor fictício nesta etapa: R$ 0'));
        self::assertStringContainsString("put('lane-value-'+index", $html);
        self::assertStringContainsString('items.reduce(function(total,item)', $html);
        self::assertStringContainsString('font-variant-numeric:tabular-nums', $html);
        self::assertStringNotContainsString('window.localStorage', $html);
    }

    public function test_keyboard_sort_and_six_item_activity_log_are_local_only(): void
    {
        $html = self::html();
        foreach ([
            'data-order="default" aria-pressed="true"',
            'data-order="score" aria-pressed="false"',
            'id="activity-list"',
            'Atividade simulada nesta aba',
            "if(order==='score')items.sort(",
            "b.score-a.score||a.id.localeCompare(b.id)",
            "focus==='sort'?",
            'function remember(message){activity.unshift(message);if(activity.length>6)activity.length=6;}',
            "remember(item.label+' movida de '",
            "remember(item.label+' criada na etapa Entrada.')",
            "order='default';",
            "activity=[];",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }

        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('<form', $html);
    }

    public function test_reversible_fictional_followups_have_no_real_customer_side_effects(): void
    {
        $html = self::html();
        self::assertSame(8, preg_match_all(
            "/Object\\.freeze\\(\\{id:'F[0-9]{2}'.*?followup:'(?:pending|done)'\\}\\)/",
            $html,
        ));
        self::assertSame(2, substr_count($html, ",followup:'done'"));
        foreach ([
            'id="count-pending"',
            'Acompanhamentos pendentes visíveis',
            'data-followup-filter="all" aria-pressed="true"',
            'data-followup-filter="pending" aria-pressed="false"',
            'data-followup-filter="done" aria-pressed="false"',
            "history.push({kind:'followup',id:item.id,from:item.followup});",
            "item.followup=item.followup==='done'?'pending':'done';",
            "item.followup=previous.from;",
            "followupFilter='all';",
            "focus.startsWith('followup:')",
            "const followButton=event.target.closest('button[data-followup]');",
            "render('followup:'+item.id);",
            "followup:'pending'",
            "item.followup==='pending'",
            'Acompanhamento fictício pendente',
            'Concluir acompanhamento fictício',
        ] as $assertion) {
            self::assertStringContainsString($assertion, $html);
        }
        foreach ([
            '<form', '<input', '<textarea', 'fetch(', 'XMLHttpRequest',
            'localStorage', 'sessionStorage', 'Notification(', 'navigator.sendBeacon',
            'indexedDB', 'setTimeout(', 'onClick=', '.innerHTML', 'document.cookie',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
    }

    public function test_bulk_followup_action_is_visible_only_atomic_reversible_and_deny_only(): void
    {
        $html = self::html();
        foreach ([
            'id="complete-visible-followups"',
            'Concluir pendentes visíveis (simulação)',
            "const pendingVisible=subset.filter(",
            "byId('complete-visible-followups').disabled=pendingVisible.length===0;",
            "history.push({kind:'batch-followup',states:selected.map(",
            "selected.forEach(function(item){item.followup='done';});",
            "previous.kind==='batch-followup'",
            "if(match)match.followup=state.from;",
            "render('batch');",
            "focus==='batch'?",
            "item.followup==='pending';",
            'Ação reversível e local.',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach ([
            '<form', '<input', '<textarea', 'fetch(', 'navigator.sendBeacon',
            'XMLHttpRequest', 'localStorage', 'sessionStorage', 'indexedDB',
            'window.open(', 'Notification(', '.innerHTML',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_pending_queue_is_derived_from_the_same_filtered_state_and_never_persists(): void
    {
        $html = self::html();
        foreach ([
            'id="followup-queue"',
            'Fila de acompanhamentos fictícios pendentes',
            'class="queue-wrap"',
            'class="queue-table"',
            "const waiting=subset.filter(function(item){return item.followup==='pending';})",
            "b.score-a.score||a.id.localeCompare(b.id)",
            "button.dataset.queueCard=item.id;",
            "button.setAttribute('aria-label','Localizar '+item.label",
            "byId('followup-queue').addEventListener('click'",
            "target.scrollIntoView({block:'nearest'});",
            "target.focus();",
            "queue.replaceChildren();",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        foreach (['<form', '<input', '<textarea', 'fetch(', 'localStorage',
            'sessionStorage', 'XMLHttpRequest', 'indexedDB', '.innerHTML'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_stage_focus_restricts_visible_cards_queue_and_bulk_operations(): void
    {
        $html = self::html();
        foreach ([
            'data-stage-filter="all" aria-pressed="true"',
            'data-stage-filter="0" aria-pressed="false"',
            'data-stage-filter="1" aria-pressed="false"',
            'data-stage-filter="2" aria-pressed="false"',
            'data-stage-filter="3" aria-pressed="false"',
            "let stageFilter='all';",
            "item.stage===stageFilter",
            "target.closest('.lane').hidden=(stageFilter!=='all'&&stageFilter!==index);",
            "button.dataset.stageFilter===String(stageFilter)",
            "stageFilter=button.dataset.stageFilter==='all'?'all':Number(button.dataset.stageFilter);",
            "render('stage');",
            "focus==='stage'?",
            "else if(stageFilter!=='all')document.querySelector(",
            "stageFilter='all';",
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }

        // The *same* stage guard must apply to the visible subset AND bulk
        // action; otherwise hidden-stage cards could be modified.
        self::assertGreaterThanOrEqual(2, substr_count(
            $html, "(stageFilter==='all'||item.stage===stageFilter)",
        ));
        self::assertStringContainsString("connect-src 'none'", $html);
        foreach (['<form', '<input', '<textarea', 'fetch(', 'localStorage'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_bulk_stage_advance_only_uses_visible_eligible_cards_and_one_undo(): void
    {
        $html = self::html();
        foreach ([
            'id="advance-visible-stage"',
            'Avançar etapas visíveis (simulação)',
            "const movableVisible=subset.filter(function(item){return item.stage<STAGES.length-1;});",
            "byId('advance-visible-stage').disabled=movableVisible.length===0;",
            "focus==='batch-move'?",
            "history.push({kind:'batch-move',states:selected.map(",
            "return {id:item.id,from:item.stage};",
            "selected.forEach(function(item){item.stage+=direction;});",
            "previous.kind==='batch-move'",
            "if(match)match.stage=state.from;",
            "render(direction===1?'batch-move':'batch-return');",
            "item.stage+direction>=0&&item.stage+direction<STAGES.length",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }

        // Both visual subset and bulk action must apply exactly the same
        // priority, follow-up and stage filters. An invisible stage cannot
        // be affected by this action.
        foreach ([
            "(priority==='all'||item.priority===priority)",
            "(followupFilter==='all'||item.followup===followupFilter)",
            "(stageFilter==='all'||item.stage===stageFilter)",
        ] as $filter) {
            self::assertGreaterThanOrEqual(3, substr_count($html, $filter));
        }

        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach (['<form', '<input', '<textarea', 'fetch(', 'localStorage',
            'sessionStorage', 'XMLHttpRequest', 'indexedDB', '.innerHTML'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_backward_stage_batch_shares_filters_and_atomic_undo_with_forward_action(): void
    {
        $html = self::html();
        foreach ([
            'id="return-visible-stage"',
            'Retornar etapas visíveis (simulação)',
            "const returnableVisible=subset.filter(function(item){return item.stage>0;});",
            "byId('return-visible-stage').disabled=returnableVisible.length===0;",
            "byId('return-visible-stage').setAttribute('aria-label'",
            "focus==='batch-return'?",
            "function moveVisibleStage(direction){",
            "if(direction!==1&&direction!==-1)return;",
            "item.stage+direction>=0&&item.stage+direction<STAGES.length",
            "history.push({kind:'batch-move',states:selected.map(",
            "selected.forEach(function(item){item.stage+=direction;});",
            "render(direction===1?'batch-move':'batch-return');",
            "moveVisibleStage(-1);",
            "moveVisibleStage(1);",
            "previous.kind==='batch-move'",
            "if(match)match.stage=state.from;",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            "(priority==='all'||item.priority===priority)",
            "(followupFilter==='all'||item.followup===followupFilter)",
            "(stageFilter==='all'||item.stage===stageFilter)",
        ] as $guard) {
            self::assertGreaterThanOrEqual(3, substr_count($html, $guard));
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach (['<form', '<input', '<textarea', 'fetch(', 'localStorage',
            'sessionStorage', 'XMLHttpRequest', 'indexedDB', '.innerHTML'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_accessible_actions_and_mobile_layout_are_present(): void
    {
        $html = self::html();
        self::assertStringContainsString('<main id="conteudo">', $html);
        self::assertStringContainsString('role="group"', $html);
        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('aria-atomic="true"', $html);
        self::assertStringContainsString("button.setAttribute('aria-label'", $html);
        self::assertStringContainsString('button:focus-visible', $html);
        self::assertStringContainsString('@media(max-width:520px)', $html);
        self::assertStringContainsString('grid-template-columns:minmax(0,1fr)', $html);
        self::assertStringContainsString('prefers-reduced-motion:reduce', $html);
        self::assertSame(1, substr_count($html, '</body>'));
    }
}
