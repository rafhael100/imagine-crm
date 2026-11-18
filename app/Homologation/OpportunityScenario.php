<?php

declare(strict_types=1);

namespace App\Homologation;

/**
 * Private, anonymous scenario calculator for Imagine's
 * Visits -> Leads -> Customers methodology. No PII, no database,
 * no HTTP outbound calls, no saved user profile or actual forecast.
 */
final class OpportunityScenario
{
    private const MAX_VISITS = 1000000;
    private const MAX_RATE = 100;

    /**
     * @param array<string,mixed> $query
     * @return array{visits:int,lead_rate:int,client_rate:int,leads:int,clients:int,scenario_only:true}
     */
    public static function fromQuery(array $query): array
    {
        $visits=self::boundedInteger($query['visits'] ?? null,1000,self::MAX_VISITS);
        $leadRate=self::boundedInteger($query['lead_rate'] ?? null,4,self::MAX_RATE);
        $clientRate=self::boundedInteger($query['client_rate'] ?? null,15,self::MAX_RATE);

        // Integer rounding, no floating-point drift and no exaggerated
        // counts: clients cannot outnumber leads, nor leads visitors.
        $leads=intdiv($visits*$leadRate+50,100);
        $clients=intdiv($leads*$clientRate+50,100);

        return [
            'visits'=>$visits,
            'lead_rate'=>$leadRate,
            'client_rate'=>$clientRate,
            'leads'=>$leads,
            'clients'=>$clients,
            'scenario_only'=>true,
        ];
    }

    private static function boundedInteger(mixed $raw,int $fallback,int $max): int
    {
        if (!is_string($raw) && !is_int($raw)) return $fallback;
        $value=(string) $raw;
        if (strlen($value)>7
            || preg_match('/\A(?:0|[1-9][0-9]*)\z/D',$value)!==1) {
            return $fallback;
        }
        $number=(int) $value;
        return $number<=$max ? $number : $fallback;
    }
}
