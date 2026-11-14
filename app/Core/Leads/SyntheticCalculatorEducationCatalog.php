<?php
declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * PERF-CALC-NURTURE-COPY-P0: localized, static content candidates, not emails.
 * This file contains no recipient, CRM access, mail transport, token, lead,
 * personalization, auto scheduling, or consent authorization.
 */
final class SyntheticCalculatorEducationCatalog
{
    private const DAY_OFFSETS = [0,3,7,14];
    private const ITEMS = [
        'pt-BR' => [
            ['kind'=>'welcome_and_budget_guide','subject'=>'Seu planejamento de anúncios começa pelo orçamento','body'=>'O investimento mínimo permite começar. O recomendado é uma referência de planejamento, não uma promessa de resultados. Conheça os objetivos e acompanhe os custos por canal.'],
            ['kind'=>'compare_channels','subject'=>'Meta, Google e ChatGPT Ads: como comparar','body'=>'Meta pode apoiar descoberta e relacionamento. Google atende pesquisas por produtos e serviços. ChatGPT Ads é um canal em expansão. A escolha depende do público, do objetivo e da mensuração disponível.'],
            ['kind'=>'campaign_planning_checklist','subject'=>'Antes de anunciar: organize sua campanha','body'=>'Defina a meta, a página de destino, a forma de medir leads e vendas, os responsáveis pelas aprovações e o limite de mídia. A mídia é paga diretamente às plataformas; a gestão da Imagine é separada.'],
            ['kind'=>'optional_consultation_invitation','subject'=>'Quer revisar seu investimento com a Imagine?','body'=>'Uma conversa pode ajudar a ajustar os canais e a distribuição da verba à sua realidade. Não há garantia de resultados. A proposta comercial depende de avaliação e aprovação antes de qualquer campanha.'],
        ],
        'en-US' => [
            ['kind'=>'welcome_and_budget_guide','subject'=>'Start planning your ad budget','body'=>'A minimum budget is a starting threshold. A recommended budget is a planning reference, not a promise of results. Define goals and monitor each channel.'],
            ['kind'=>'compare_channels','subject'=>'Comparing Meta, Google and ChatGPT Ads','body'=>'Meta can support discovery and relationships. Google can reach active searches. ChatGPT Ads is an emerging channel. The right mix depends on your audience, objective and measurement.'],
            ['kind'=>'campaign_planning_checklist','subject'=>'Your advertising planning checklist','body'=>'Set a goal, landing page, conversion measurement, approval owners and a media limit. Media is paid directly to platforms; Imagine management is separate.'],
            ['kind'=>'optional_consultation_invitation','subject'=>'Would you like to review your budget with Imagine?','body'=>'A conversation can help align your channel mix and budget with your business. Outcomes are not guaranteed, and every campaign requires approval.'],
        ],
        'es-ES' => [
            ['kind'=>'welcome_and_budget_guide','subject'=>'Empieza a planificar tu presupuesto publicitario','body'=>'El presupuesto mínimo es un punto de partida. El recomendado es una referencia de planificación, no una promesa de resultados. Define objetivos y mide cada canal.'],
            ['kind'=>'compare_channels','subject'=>'Comparación entre Meta, Google y ChatGPT Ads','body'=>'Meta puede favorecer el descubrimiento y las relaciones. Google puede responder a búsquedas activas. ChatGPT Ads es un canal emergente. La elección depende del público, los objetivos y la medición.'],
            ['kind'=>'campaign_planning_checklist','subject'=>'Lista de preparación para tus campañas','body'=>'Define el objetivo, la página de destino, la medición de conversiones, las aprobaciones y el límite de inversión. La publicidad se paga a las plataformas; la gestión de Imagine es independiente.'],
            ['kind'=>'optional_consultation_invitation','subject'=>'¿Quieres revisar tu presupuesto con Imagine?','body'=>'Una conversación puede ayudar a ajustar los canales y la inversión a tu negocio. No se garantizan resultados y cada campaña requiere aprobación.'],
        ],
    ];

    /** @return array<string,mixed> */
    public static function preview(string $locale, int $dayOffset): array
    {
        if (!array_key_exists($locale,self::ITEMS) ||
            !in_array($dayOffset,self::DAY_OFFSETS,true)) {
            throw new InvalidArgumentException('Unsupported language or nurture day.');
        }
        $index=array_search($dayOffset,self::DAY_OFFSETS,true);
        $item=self::ITEMS[$locale][$index];
        return [
            'schema'=>'synthetic.calculator.education-copy.v1',
            'locale'=>$locale,
            'day_offset'=>$dayOffset,
            'kind'=>$item['kind'],
            'subject'=>$item['subject'],
            'body'=>$item['body'],
            'preview_only'=>true,
            'recipient_present'=>false,
            'marketing_permission_proven'=>false,
            'delivery_authorized'=>false,
            'mail_transport_enabled'=>false,
            'can_schedule'=>false,
            'can_send'=>false,
        ];
    }
}
