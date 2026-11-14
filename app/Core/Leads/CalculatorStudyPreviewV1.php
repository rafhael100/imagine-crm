<?php
declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Calculator report content generator, transport-neutral.
 *
 * Builds reviewable HTML + text from approved per-channel budget inputs.
 * No recipients, no SMTP, no lead persistence and no signed consent.
 * It is deliberately not registered as an HTTP route or queue worker.
 *
 * A report request is separate from marketing consent; viewing/generating a
 * report NEVER authorizes a marketing message. This class has no side effects.
 */
final class CalculatorStudyPreviewV1
{
    private const FLOOR = [
        'chatgpt_ads' => 120000,
        'google_ads' => 100000,
        'meta_ads' => 70000,
    ];
    private const RECOMMENDED = [
        'chatgpt_ads' => 180000,
        'google_ads' => 150000,
        'meta_ads' => 100000,
    ];
    private const LABEL = [
        'pt-BR' => [
            'title'=>'Seu planejamento de anúncios',
            'intro'=>'Simulação informativa da Imagine Performance AI',
            'channel'=>'Canal','floor'=>'Mínimo comercial','media'=>'Verba de mídia sugerida',
            'management'=>'Gestão Imagine estimada (20%)',
            'total'=>'Total informativo (mídia + gestão)',
            'note'=>'A mídia é paga diretamente a cada plataforma. A gestão real é calculada sobre a mídia efetivamente investida, com conciliação mensal. Este estudo não garante leads, vendas ou retorno.',
            'no'=>'Nenhuma contratação, cobrança ou campanha foi iniciada por este estudo.',
        ],
        'en-US'=>[
            'title'=>'Your advertising budget plan','intro'=>'Imagine Performance AI informational estimate',
            'channel'=>'Channel','floor'=>'Commercial minimum','media'=>'Suggested media spend',
            'management'=>'Estimated Imagine management (20%)',
            'total'=>'Illustrative total (media + management)',
            'note'=>'Media spend is paid directly to each platform. Actual management is based on verified media spending and monthly reconciliation. This estimate does not guarantee leads, sales or returns.',
            'no'=>'This report does not initiate a contract, charge or campaign.',
        ],
        'es-ES'=>[
            'title'=>'Tu planificación publicitaria','intro'=>'Estimación informativa de Imagine Performance AI',
            'channel'=>'Canal','floor'=>'Mínimo comercial','media'=>'Inversión recomendada en medios',
            'management'=>'Gestión Imagine estimada (20%)',
            'total'=>'Total orientativo (publicidad + gestión)',
            'note'=>'Los medios se pagan directamente a las plataformas. La gestión real se calcula sobre la inversión publicitaria comprobada, con conciliación mensual. Esta estimación no garantiza leads, ventas ni resultados.',
            'no'=>'Este estudio no inicia contratos, cargos ni campañas.',
        ],
    ];

    /**
     * @param list<array{channel:string,media_cents:int}> $rows
     * @return array<string,mixed>
     */
    public static function render(string $locale, array $rows): array
    {
        if (!isset(self::LABEL[$locale]) || !array_is_list($rows) ||
            count($rows) < 1 || count($rows) > 3) {
            throw new InvalidArgumentException('Invalid study locale or channels.');
        }
        $seen = [];
        $mediaTotal = 0;
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row) || array_keys($row) !== ['channel','media_cents'] ||
                !is_string($row['channel']) || !isset(self::FLOOR[$row['channel']]) ||
                isset($seen[$row['channel']]) || !is_int($row['media_cents']) ||
                $row['media_cents'] < self::FLOOR[$row['channel']] ||
                $row['media_cents'] > 100000000) {
                throw new InvalidArgumentException('Invalid channel budget or scope.');
            }
            $seen[$row['channel']] = true;
            $mediaTotal += $row['media_cents'];
            $clean[] = $row;
        }
        usort($clean,static fn(array $a,array $b)=>strcmp($a['channel'],$b['channel']));
        $fee=intdiv($mediaTotal*20+50,100);
        $language=self::LABEL[$locale];
        $money=static function(int $cents)use($locale):string {
            $english=$locale==='en-US';
            return ($english?'R$ '.number_format($cents/100,2,'.',','):
                'R$ '.number_format($cents/100,2,',','.'));
        };
        $html='<h1>'.self::escape($language['title']).'</h1>';
        $html.='<p>'.self::escape($language['intro']).'</p>';
        $html.='<table aria-label="'.self::escape($language['title']).'"><thead><tr><th>'.
            self::escape($language['channel']).'</th><th>'.self::escape($language['floor']).
            '</th><th>'.self::escape($language['media']).'</th></tr></thead><tbody>';
        $text=$language['title']."\n".$language['intro']."\n\n";
        foreach($clean as $row){
            $channel=$row['channel'];
            $floor=$money(self::FLOOR[$channel]);
            $media=$money($row['media_cents']);
            $display=self::displayName($channel);
            $html.='<tr><td>'.self::escape($display).'</td><td>'.self::escape($floor).
                '</td><td>'.self::escape($media).'</td></tr>';
            $text.=$display.' — '.$language['floor'].': '.$floor.
                ' · '.$language['media'].': '.$media."\n";
        }
        $html.='</tbody></table>';
        $html.='<p><strong>'.self::escape($language['media']).': '.
            self::escape($money($mediaTotal)).'</strong></p>';
        $html.='<p>'.self::escape($language['management']).': '.
            self::escape($money($fee)).'</p>';
        $html.='<p><strong>'.self::escape($language['total']).': '.
            self::escape($money($mediaTotal+$fee)).'</strong></p>';
        $html.='<p>'.self::escape($language['note']).'</p>';
        $html.='<p>'.self::escape($language['no']).'</p>';
        $text.="\n".$language['media'].': '.$money($mediaTotal).
            "\n".$language['management'].': '.$money($fee).
            "\n".$language['total'].': '.$money($mediaTotal+$fee).
            "\n\n".$language['note']."\n".$language['no']."\n";
        return [
            'schema'=>'imagine.calculator.study-preview.v1',
            'locale'=>$locale,
            'subject'=>$language['title'],
            'html'=>'<!doctype html><html lang="'.substr($locale,0,2).'"><head>'.
                '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'.
                '<title>'.self::escape($language['title']).'</title></head><body>'.
                '<main style="max-width:700px;margin:0 auto;font:16px system-ui,sans-serif;color:#24201d;border-top:6px solid #F9941D;padding:20px;">'.
                $html.'</main></body></html>',
            'text'=>$text,
            'media_cents'=>$mediaTotal,
            'management_preview_cents'=>$fee,
            'informational_total_cents'=>$mediaTotal+$fee,
            'not_a_contract'=>true,
            'marketing_consent_proven'=>false,
            'recipient_present'=>false,
            'delivery_authorized'=>false,
            'can_send'=>false,
            'can_bill'=>false,
        ];
    }

    private static function displayName(string $channel): string
    {
        return match($channel) {
            'chatgpt_ads'=>'ChatGPT Ads',
            'google_ads'=>'Google Ads',
            'meta_ads'=>'Meta Ads (Instagram e Facebook)',
        };
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false);
    }
}
