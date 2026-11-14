<?php

declare(strict_types=1);

namespace App\Core\Commercial;

use App\Core\Plans\CommercialPlanReferenceCatalog;

/**
 * CRM-STG-MKT-01: strictly read-only, non-contractual private preview.
 *
 * No signup, lead capture, session, identity, payment, order, trial or
 * entitlement is created. Do not reuse this as an authorized checkout.
 */
final class StagingMarketingPreview
{
    private const COPY = [
        'pt' => [
            'skip' => 'Ir para o conteúdo',
            'title' => 'Imagine CRM | Transforme oportunidades em resultados',
            'eyebrow' => 'Imagine CRM • Prévia privada',
            'headline' => 'Organize seus contatos. Avance suas vendas.',
            'summary' => 'Uma visão clara de oportunidades, atividades e relacionamento para equipes que querem crescer com organização.',
            'plan_title' => 'Planos para diferentes momentos do seu negócio',
            'plan_intro' => 'Valores mensais de referência, sujeitos à homologação comercial. Nenhum plano está disponível para contratação nesta prévia.',
            'plans_link' => 'Conhecer os planos',
            'demo_link' => 'Explorar demonstração',
            'focus_one' => 'Funis e oportunidades',
            'focus_two' => 'Tarefas e acompanhamento',
            'focus_three' => 'Automação com controle',
            'focus_four' => 'Visão por empresa',
            'monthly' => 'por mês • referência',
            'seats' => 'usuário(s) de referência',
            'storage' => 'armazenamento ilustrativo',
            'preview' => 'Prévia funcional restrita à homologação. Cadastro, teste grátis, pagamentos e acesso de clientes ainda não estão disponíveis.',
            'foot' => 'Imagine • Tecnologia e relacionamento para negócios',
            'status' => 'Apresentação • Sem contratação',
        ],
        'en' => [
            'skip' => 'Skip to content',
            'title' => 'Imagine CRM | Turn opportunities into results',
            'eyebrow' => 'Imagine CRM • Private preview',
            'headline' => 'Organize contacts. Move deals forward.',
            'summary' => 'A clearer view of opportunities, follow-ups and customer relationships for teams focused on sustainable growth.',
            'plan_title' => 'Plans for every stage of your business',
            'plan_intro' => 'Illustrative monthly reference prices, subject to commercial approval. No plan is available to purchase in this preview.',
            'plans_link' => 'Explore plans',
            'demo_link' => 'Explore demo',
            'focus_one' => 'Sales pipelines',
            'focus_two' => 'Tasks and follow-ups',
            'focus_three' => 'Controlled automation',
            'focus_four' => 'Company-level visibility',
            'monthly' => 'per month • reference',
            'seats' => 'reference users',
            'storage' => 'illustrative storage',
            'preview' => 'Functional preview for private validation only. Sign-up, free trial, payments and customer access are not yet available.',
            'foot' => 'Imagine • Technology and relationships for businesses',
            'status' => 'Preview • No checkout',
        ],
        'es' => [
            'skip' => 'Saltar al contenido',
            'title' => 'Imagine CRM | Convierte oportunidades en resultados',
            'eyebrow' => 'Imagine CRM • Vista previa privada',
            'headline' => 'Organiza contactos. Impulsa tus ventas.',
            'summary' => 'Una visión clara de oportunidades, tareas y relaciones comerciales para equipos que quieren crecer con organización.',
            'plan_title' => 'Planes para cada etapa de tu negocio',
            'plan_intro' => 'Precios mensuales ilustrativos, sujetos a aprobación comercial. No se puede contratar ningún plan en esta vista previa.',
            'plans_link' => 'Ver los planes',
            'demo_link' => 'Explorar demostración',
            'focus_one' => 'Embudos y oportunidades',
            'focus_two' => 'Tareas y seguimiento',
            'focus_three' => 'Automatización controlada',
            'focus_four' => 'Visión por empresa',
            'monthly' => 'por mes • referencia',
            'seats' => 'usuarios de referencia',
            'storage' => 'almacenamiento ilustrativo',
            'preview' => 'Vista previa funcional y privada. El registro, la prueba gratuita, los pagos y el acceso de clientes aún no están disponibles.',
            'foot' => 'Imagine • Tecnología y relaciones para empresas',
            'status' => 'Presentación • Sin contratación',
        ],
    ];

    /** @return array<string,mixed> */
    public static function presentation(mixed $requestedLanguage): array
    {
        // Strict whitelist: query parameters cannot inject arbitrary locale,
        // pricing, feature flags, plan names or markup into a public page.
        $lang = is_string($requestedLanguage) && array_key_exists($requestedLanguage, self::COPY)
            ? $requestedLanguage : 'pt';

        $localizedNames = [
            'pt' => ['basico' => 'Básico', 'essencial' => 'Essencial',
                'profissional' => 'Profissional', 'empresarial' => 'Empresarial'],
            'en' => ['basico' => 'Basic', 'essencial' => 'Essential',
                'profissional' => 'Professional', 'empresarial' => 'Enterprise'],
            'es' => ['basico' => 'Básico', 'essencial' => 'Esencial',
                'profissional' => 'Profesional', 'empresarial' => 'Empresarial'],
        ];

        $cards = [];
        foreach (CommercialPlanReferenceCatalog::plans() as $plan) {
            // Source of truth stays in the server-owned, unapproved reference
            // catalog; neither a query parameter nor translation may alter it.
            if (($plan['price_homologated'] ?? true) !== false
                || ($plan['can_checkout'] ?? true) !== false
                || !is_int($plan['monthly_cents'])) {
                continue;
            }

            $cents = $plan['monthly_cents'];
            $cards[] = [
                'code' => $plan['code'],
                'name' => $localizedNames[$lang][$plan['code']] ?? $plan['name'],
                'display_price' => 'R$ '.number_format(intdiv($cents, 100), 0, ',', '.')
                    .','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT),
                'seats' => $plan['seats_reference'],
                'storage' => $plan['storage_label'],
                'price_homologated' => false,
                'can_checkout' => false,
            ];
        }

        return [
            'lang' => $lang,
            'copy' => self::COPY[$lang],
            'cards' => $cards,
            'can_signup' => false,
            'can_trial' => false,
            'can_checkout' => false,
            'private_preview' => true,
        ];
    }
}
