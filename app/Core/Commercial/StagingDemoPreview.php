<?php

declare(strict_types=1);

namespace App\Core\Commercial;

/**
 * CRM-STG-DEMO-01: static, fictional, read-only navigation model.
 *
 * No database reads, no customers/contacts, no business authorization,
 * no forms, no changes, no notifications, no payment or trial. The model
 * cannot grant permissions even if embedded in a real Laravel route.
 */
final class StagingDemoPreview
{
    private const TEXT = [
        'pt' => [
            'title' => 'Imagine CRM | Demonstração privada',
            'eyebrow' => 'DEMONSTRAÇÃO FICTÍCIA • APENAS VISUALIZAÇÃO',
            'heading' => 'Visão geral do seu processo comercial',
            'intro' => 'Conheça a organização de um funil e de atividades com dados inteiramente fictícios.',
            'back' => 'Voltar ao site do CRM',
            'live_demo' => 'Dados salvos no PostgreSQL (fictícios)',
            'live_source' => 'Dados fictícios carregados do PostgreSQL16 de teste',
            'static_source' => 'Exemplo visual com dados fixos',
            'pipeline' => 'Funil de oportunidades',
            'tasks' => 'Tarefas de acompanhamento',
            'opportunities' => 'Oportunidades de exemplo',
            'pending' => 'Tarefas de exemplo',
            'actions' => 'Ações comerciais habilitadas',
            'new' => 'Novos',
            'qualified' => 'Qualificados',
            'proposal' => 'Proposta',
            'won' => 'Concluídos',
            'board_note' => 'Os cartões são apenas uma ilustração. Nenhum contato ou negócio real é exibido.',
            'task_note' => 'As tarefas abaixo não podem ser editadas, notificadas ou concluídas nesta prévia.',
            'no_access' => '0 — contratação e acesso ainda bloqueados',
            'footer' => 'Imagine CRM • Ambiente demonstrativo privado',
        ],
        'en' => [
            'title' => 'Imagine CRM | Private demo',
            'eyebrow' => 'FICTIONAL DEMO • VIEW ONLY',
            'heading' => 'An overview of your sales workflow',
            'intro' => 'Explore a visual sales pipeline and follow-ups using entirely fictional data.',
            'back' => 'Back to CRM website',
            'live_demo' => 'View stored PostgreSQL data (fictional)',
            'live_source' => 'Fictional data loaded from the test PostgreSQL16 database',
            'static_source' => 'Visual example with fixed data',
            'pipeline' => 'Opportunity pipeline',
            'tasks' => 'Follow-up tasks',
            'opportunities' => 'Example opportunities',
            'pending' => 'Example tasks',
            'actions' => 'Enabled business actions',
            'new' => 'New',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'won' => 'Completed',
            'board_note' => 'These cards are illustrations only. No real contacts or deals are shown.',
            'task_note' => 'These tasks cannot be edited, notified or completed in this preview.',
            'no_access' => '0 — checkout and access remain disabled',
            'footer' => 'Imagine CRM • Private demonstration environment',
        ],
        'es' => [
            'title' => 'Imagine CRM | Demostración privada',
            'eyebrow' => 'DEMOSTRACIÓN FICTICIA • SOLO LECTURA',
            'heading' => 'Una visión general de tu proceso comercial',
            'intro' => 'Explora un embudo de ventas y tareas con datos totalmente ficticios.',
            'back' => 'Volver al sitio del CRM',
            'live_demo' => 'Ver datos guardados en PostgreSQL (ficticios)',
            'live_source' => 'Datos ficticios cargados del PostgreSQL16 de prueba',
            'static_source' => 'Ejemplo visual con datos fijos',
            'pipeline' => 'Embudo de oportunidades',
            'tasks' => 'Tareas de seguimiento',
            'opportunities' => 'Oportunidades de ejemplo',
            'pending' => 'Tareas de ejemplo',
            'actions' => 'Acciones comerciales habilitadas',
            'new' => 'Nuevos',
            'qualified' => 'Calificados',
            'proposal' => 'Propuesta',
            'won' => 'Finalizados',
            'board_note' => 'Las tarjetas son ilustrativas. No se muestran contactos ni negocios reales.',
            'task_note' => 'Estas tareas no se pueden editar, notificar ni completar en esta vista.',
            'no_access' => '0 — contratación y acceso siguen bloqueados',
            'footer' => 'Imagine CRM • Entorno privado de demostración',
        ],
    ];

    /** @return array<string,mixed> */
    public static function presentation(mixed $requestedLanguage): array
    {
        $lang = is_string($requestedLanguage) && isset(self::TEXT[$requestedLanguage])
            ? $requestedLanguage : 'pt';

        // There are deliberately no provider IDs, emails, phone numbers,
        // CNPJs, browser-supplied tenant IDs or persistent DB records here.
        $pipeline = [
            'new' => [
                ['reference' => 'DEMO_001', 'label' => 'EXEMPLO_ALFA', 'detail' => 'REGISTRO_FICTICIO_A'],
                ['reference' => 'DEMO_002', 'label' => 'EXEMPLO_BETA', 'detail' => 'REGISTRO_FICTICIO_B'],
            ],
            'qualified' => [
                ['reference' => 'DEMO_003', 'label' => 'EXEMPLO_GAMA', 'detail' => 'REGISTRO_FICTICIO_C'],
            ],
            'proposal' => [
                ['reference' => 'DEMO_004', 'label' => 'EXEMPLO_DELTA', 'detail' => 'REGISTRO_FICTICIO_D'],
                ['reference' => 'DEMO_005', 'label' => 'EXEMPLO_EPSILON', 'detail' => 'REGISTRO_FICTICIO_E'],
            ],
            'won' => [
                ['reference' => 'DEMO_006', 'label' => 'EXEMPLO_ZETA', 'detail' => 'REGISTRO_FICTICIO_F'],
            ],
        ];
        $tasks = [
            ['reference' => 'TASK_DEMO_001', 'subject' => 'FOLLOW_UP_DEMO_A', 'day' => '08/12'],
            ['reference' => 'TASK_DEMO_002', 'subject' => 'FOLLOW_UP_DEMO_B', 'day' => '10/12'],
            ['reference' => 'TASK_DEMO_003', 'subject' => 'FOLLOW_UP_DEMO_C', 'day' => '12/12'],
        ];
        return [
            'lang' => $lang,
            'copy' => self::TEXT[$lang],
            'pipeline' => $pipeline,
            'tasks' => $tasks,
            'opportunity_count' => array_sum(array_map('count', $pipeline)),
            'task_count' => count($tasks),
            'is_persisted' => false,
            'enable_persisted_demo' => false,
            'demo_source' => 'inline_fixture',
            'can_edit' => false,
            'can_contact' => false,
            'can_notify' => false,
            'can_signup' => false,
            'can_checkout' => false,
            'can_activate' => false,
        ];
    }
}
