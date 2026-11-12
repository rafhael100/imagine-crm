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

    private const APPROVED_SEARCH = '<input type="search" id="fiction-search" maxlength="32" autocomplete="off" spellcheck="false" placeholder="F01 ou oportunidade">';

    private static function withoutApprovedSearchInput(string $html): string
    {
        // One short text search is intentionally permitted. All other inputs
        // and every form, upload, network and persistence path remain denied.
        self::assertSame(1, substr_count($html, self::APPROVED_SEARCH));

        return str_replace(self::APPROVED_SEARCH, '', $html);
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
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
            "const number=[9,10,11,12].find(function(candidate){",
            "priority='all';",
            "render(item.id);",
            "render('undo');",
            'Limite de doze oportunidades fictícias atingido',
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        foreach (['<form', '<input', '<textarea', 'localStorage', 'fetch('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
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
            'data-order="value" aria-pressed="false"',
            'Maior valor fictício',
            "if(order==='value')items.sort(",
            "b.value-a.value||a.id.localeCompare(b.id)",
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
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
            "byId('complete-visible-followups').disabled=eligibleVisible(pendingVisible).length===0;",
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_bulk_stage_advance_only_uses_visible_eligible_cards_and_one_undo(): void
    {
        $html = self::html();
        foreach ([
            'id="advance-visible-stage"',
            'Avançar etapas visíveis (simulação)',
            "const movableVisible=subset.filter(function(item){return item.stage<STAGES.length-1;});",
            "byId('advance-visible-stage').disabled=eligibleVisible(movableVisible).length===0;",
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_backward_stage_batch_shares_filters_and_atomic_undo_with_forward_action(): void
    {
        $html = self::html();
        foreach ([
            'id="return-visible-stage"',
            'Retornar etapas visíveis (simulação)',
            "const returnableVisible=subset.filter(function(item){return item.stage>0;});",
            "byId('return-visible-stage').disabled=eligibleVisible(returnableVisible).length===0;",
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
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_csv_preview_reads_only_visible_fictional_metadata_and_never_exports_contacts(): void
    {
        $html = self::html();
        foreach ([
            'id="toggle-csv-demo" aria-expanded="false" aria-controls="csv-preview"',
            'id="csv-preview" class="csv-demo" hidden',
            'Prévia CSV dos cartões fictícios visíveis',
            "function csvFromVisible(items){",
            "const totalValue=items.reduce(function(sum,item){return sum+item.value;},0);",
            "const valid=items.length<=12&&Number.isSafeInteger(totalValue)&&items.every(function(item){",
            "/^F(0[1-9]|1[0-2])$/.test(item.id)",
            "Number.isInteger(item.stage)&&item.stage>=0&&item.stage<STAGES.length",
            "['high','normal'].includes(item.priority)",
            "['pending','done'].includes(item.followup)",
            "Number.isSafeInteger(item.value)&&item.value>=0",
            "Number.isInteger(item.score)&&item.score>=0&&item.score<=100",
            "if(!valid)return 'Prévia fictícia indisponível: dados técnicos inválidos.';",
            "id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao",
            'const displayOrder=[];',
            'displayOrder.push(...items);',
            "if(!byId('csv-preview').hidden)byId('csv-preview').textContent=csvFromVisible(displayOrder);",
            "focus==='csv'?byId('toggle-csv-demo')",
            "preview.hidden=!preview.hidden;",
            "render('csv');",
            "this.setAttribute('aria-expanded',String(!preview.hidden));",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            '<form', '<input', '<textarea', 'fetch(', 'XMLHttpRequest', 'WebSocket',
            'localStorage', 'sessionStorage', 'Blob(', 'createObjectURL',
            'navigator.clipboard', 'download=', '.innerHTML',
            'document.write', 'navigator.sendBeacon', 'window.open(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }

    public function test_generated_technical_ids_reuse_vacant_F09_to_F12_slots_after_undo(): void
    {
        $html = self::html();
        foreach ([
            "const number=[9,10,11,12].find(function(candidate){",
            "return !opportunities.some(function(entry){",
            "return entry.id==='F'+String(candidate).padStart(2,'0');",
            "if(number===undefined)return;",
            "const reference=String(number).padStart(2,'0');",
            "id:'F'+reference,",
            "opportunities.length>=12",
            "opportunities=opportunities.filter(function(entry){return entry.id!==item.id;});",
            "opportunities=FIXTURES.map(function(item){return Object.assign({},item);});",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        self::assertStringNotContainsString('nextGenerated', $html);
        self::assertStringNotContainsString('F13', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('fetch(', $html);
    }

    public function test_undo_history_is_bounded_for_long_fictional_sessions(): void
    {
        $html = self::html();
        foreach ([
            'const MAX_UNDO_ACTIONS=64;',
            'function trimUndoHistory(){',
            'if(history.length>MAX_UNDO_ACTIONS)history.splice(0,history.length-MAX_UNDO_ACTIONS);',
            'Seis ações são exibidas; até 64 podem ser desfeitas.',
            "history.pop()",
            "history=[];",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        self::assertSame(6, substr_count($html, 'history.push('));
        self::assertSame(7, substr_count($html, 'trimUndoHistory();'));
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('sessionStorage', $html);
        self::assertStringNotContainsString('fetch(', $html);
    }

    public function test_redo_is_bounded_in_memory_and_invalidated_by_every_new_mutation(): void
    {
        $html = self::html();
        foreach ([
            'id="redo-change" disabled>Refazer ação',
            "let redoStack=[];",
            'function recordRedo(entry){',
            'if(redoStack.length>MAX_UNDO_ACTIONS)redoStack.splice(0,redoStack.length-MAX_UNDO_ACTIONS);',
            "byId('redo-change').disabled=redoStack.length===0;",
            "byId('redo-change').addEventListener('click'",
            "recordRedo({kind:'batch-move',undo:previous,states:previous.states.map(",
            "recordRedo({kind:'batch-followup',undo:previous,states:previous.states.map(",
            "kind:previous.kind,undo:previous,",
            "history[history.length]=forward.undo;",
            "if(forward.kind==='move')item.stage=forward.after;",
            "if(forward.kind==='add'){",
            "opportunities.push(Object.assign({},forward.after));",
            "focus==='redo'?",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        self::assertSame(8, substr_count($html, 'redoStack=[];'));
        self::assertSame(6, substr_count($html, 'history.push('));
        self::assertSame(7, substr_count($html, 'trimUndoHistory();'));
        self::assertStringContainsString("connect-src 'none'", $html);
        foreach (['localStorage', 'sessionStorage', 'fetch(', 'navigator.sendBeacon',
            'WebSocket', '<form', 'indexedDB', '.innerHTML'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_global_shortcuts_exclude_form_fields_composition_and_modifier_collisions(): void
    {
        $html = self::html();
        foreach ([
            'Atalhos locais: Ctrl/Cmd+Z para desfazer;',
            "document.addEventListener('keydown',function(event){",
            'event.defaultPrevented||event.isComposing||event.repeat||event.altKey',
            'event.ctrlKey===event.metaKey',
            "active.isContentEditable||['INPUT','TEXTAREA','SELECT'].includes(active.tagName)",
            "const undo=key==='z'&&!event.shiftKey;",
            "const redo=(key==='z'&&event.shiftKey)||(key==='y'&&!event.shiftKey);",
            "const action=byId(undo?'undo-change':'redo-change');",
            "if(action.disabled)return;",
            'event.preventDefault();',
            'action.click();',
        ] as $code) {
            self::assertStringContainsString($code, $html);
        }
        self::assertSame(1, substr_count($html, "document.addEventListener('keydown',function(event){"));
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('fetch(', $html);
    }

    public function test_explicit_batch_selection_never_includes_hidden_or_unselected_cards(): void
    {
        $html = self::html();
        foreach ([
            'id="count-selected" aria-live="polite" aria-atomic="true"',
            'id="select-visible"',
            'id="clear-selection" disabled',
            'let selectedIds=new Set();',
            "selectButton.dataset.select=item.id;",
            "selectButton.setAttribute('aria-pressed',String(selectedIds.has(item.id)))",
            "const selectedVisible=subset.filter(function(item){return selectedIds.has(item.id);});",
            "selectedIds.size===0?items:items.filter(function(item){return selectedIds.has(item.id);});",
            "&& (selectedIds.size===0||selectedIds.has(item.id))",
            "selectedIds=new Set(Array.from(document.querySelectorAll('#board [data-card-id]')",
            "selectedIds.clear();",
            "focus.startsWith('select:')",
            "selectedIds.delete(item.id);",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        self::assertSame(2, substr_count($html, '&& (selectedIds.size===0||selectedIds.has(item.id))'));
        self::assertSame(1, substr_count($html, "selectedIds=new Set();"));
        self::assertStringContainsString("connect-src 'none'", $html);
        foreach (['localStorage', 'sessionStorage', 'fetch(', 'navigator.sendBeacon',
            'WebSocket', '<form', '.innerHTML', 'indexedDB'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_dragged_cards_keep_in_memory_undo_and_keyboard_movement_alternative(): void
    {
        $html = self::html();
        self::assertSame(4, substr_count($html, 'data-drop-stage="'));
        foreach ([
            'card.draggable=true;',
            "card.setAttribute('aria-description'",
            "byId('board').addEventListener('dragstart'",
            "byId('board').addEventListener('dragover'",
            "byId('board').addEventListener('dragleave'",
            "byId('board').addEventListener('drop'",
            "byId('board').addEventListener('dragend'",
            "event.target.closest('button')",
            "event.dataTransfer.setData('text/plain',dragId);",
            "if(!id||!lane||lane.hidden)return;",
            "if(!item||!Number.isInteger(next)||next<0||next>=STAGES.length||next===item.stage)return;",
            "moveToStage(item,target);",
            "moveToStage(item,item.stage+(button.dataset.move==='next'?1:-1));",
            "history.push({kind:'move',id:item.id,from:item.stage});",
            "item.stage=next;",
            "if(event.dataTransfer)event.dataTransfer.dropEffect='move';",
            "Arraste cartões fictícios entre as quatro etapas",
            "botões Etapa anterior/Próxima etapa",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        self::assertSame(1, substr_count($html, "byId('board').addEventListener('drop'"));
        self::assertSame(6, substr_count($html, 'history.push('));
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach (['<form', 'fetch(', 'localStorage', 'sessionStorage',
            'navigator.sendBeacon', '.innerHTML', 'download='] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_dragging_selected_card_only_moves_visible_selected_group_and_reuses_undo(): void
    {
        $html = self::html();
        foreach ([
            'Ao arrastar um cartão selecionado, apenas os selecionados visíveis',
            "if(selectedIds.has(id)){",
            "const visible=new Set(Array.from(document.querySelectorAll('#board [data-card-id]')",
            "selectedIds.has(entry.id)&&visible.has(entry.id)&&entry.stage!==target;",
            "history.push({kind:'batch-move',states:group.map(function(entry){",
            "group.forEach(function(entry){entry.stage=target;});",
            "remember('Arrastar em grupo: '+group.length+' oportunidades fictícias visíveis.');",
            "render('batch-move');",
            "moveToStage(item,target);",
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        self::assertSame(6, substr_count($html, 'history.push('));
        self::assertSame(7, substr_count($html, 'trimUndoHistory();'));
        self::assertStringContainsString("connect-src 'none'", $html);
        foreach (['<form', 'fetch(', 'localStorage', 'sessionStorage', '.innerHTML'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }

    public function test_impact_preview_tracks_visible_selection_without_authorization_or_persistence(): void
    {
        $html = self::html();
        self::assertSame(4, substr_count($html, 'data-impact-stage="'));
        foreach ([
            'id="impact-result" role="status" aria-live="polite" aria-atomic="true"',
            'Prévia de impacto do lote fictício (sem executar)',
            'Esta prévia não valida campos ou permissões reais.',
            'let impactStage=null;',
            'const scope=selectedIds.size===0?subset:subset.filter(',
            'const movable=scope.filter(function(item){return item.stage!==impactStage;});',
            'const hiddenSelected=selectedIds.size===0?0:selectedIds.size-scope.length;',
            'Sem autorização ou gravação.',
            'impactStage=Number(raw);',
            'impactStage=null;',
        ] as $needle) {
            self::assertStringContainsString($needle, $html);
        }
        foreach (['fetch(', 'localStorage', 'sessionStorage', 'indexedDB',
            'navigator.sendBeacon', 'XMLHttpRequest', 'download=', '.innerHTML'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
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

    public function test_local_search_is_bounded_accessible_and_scopes_all_bulk_actions(): void
    {
        $html = self::html();
        self::assertSame(1, substr_count($html, self::APPROVED_SEARCH));
        self::assertStringContainsString('role="search" aria-label="Busca local em oportunidades fictícias"', $html);
        self::assertStringContainsString('<label for="fiction-search">', $html);
        self::assertStringContainsString("let searchText='';", $html);
        self::assertStringContainsString("function matchesSearch(item){", $html);
        self::assertStringContainsString("item.id.toLowerCase().includes(searchText)", $html);
        self::assertStringContainsString("item.label.toLocaleLowerCase('pt-BR').includes(searchText)", $html);
        self::assertStringContainsString("searchText=this.value.trim().toLocaleLowerCase('pt-BR');", $html);
        self::assertStringContainsString("byId('fiction-search').addEventListener('input'", $html);
        self::assertSame(3, substr_count($html, '&& matchesSearch(item)'));
        self::assertSame(2, substr_count($html, "byId('fiction-search').value='';"));
        self::assertStringContainsString('input[type=search]:focus-visible', $html);
        self::assertStringContainsString('id="count-visible" aria-live="polite" aria-atomic="true"', $html);
        self::assertStringContainsString("this.value=this.value.slice(0,32);", $html);
        self::assertStringContainsString("byId('fiction-search').addEventListener('keydown'", $html);
        self::assertStringContainsString("event.key!=='Escape'", $html);
        self::assertStringContainsString("describe('Busca local limpa;", $html);
        foreach (['<form', '<textarea', 'fetch(', 'localStorage', 'sessionStorage',
            'document.cookie', 'XMLHttpRequest', 'navigator.sendBeacon', '.innerHTML',
            'download=', 'createObjectURL', 'new URL('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, self::withoutApprovedSearchInput($html));
        }
    }
}
