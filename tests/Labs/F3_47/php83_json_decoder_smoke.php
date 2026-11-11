<?php

declare(strict_types=1);

// F3-47 standalone PHP 8.3+ smoke. Laravel 13's composer.lock currently
// resolves to Symfony packages needing PHP 8.4, so this pure Core parser is
// checked independently under 8.3 without altering pinned dependencies.
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadEnvelopeV1Assessment.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadEnvelopeV1Inspector.php';
require_once __DIR__.'/../../../app/Core/Leads/PortalTechnicalEnvelopeDecoder.php';

use App\Core\Leads\PortalTechnicalEnvelopeDecoder;

$frame = [
    'protocol'=>'imagine.lead-intake',
    'schema_version'=>1,
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'submission_reference'=>'33333333-3333-4333-8333-333333333333',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$json = json_encode($frame,JSON_THROW_ON_ERROR);
$decoder = new PortalTechnicalEnvelopeDecoder();
if ($decoder->decode($json)!==$frame) {
    throw new RuntimeException('F3-47 PHP83 failed to decode technical envelope');
}
$bad = [
    str_replace('"protocol"', '"\u0070rotocol":"wrong","protocol"', $json),
    $json.str_repeat(' ', 2049),
    str_replace('"schema_version":1','"schema_version":true',$json),
    json_encode([...$frame,'email'=>'no-personal-data'],JSON_THROW_ON_ERROR),
    str_replace('"protocol"', '"\uD800"', $json),
];
foreach ($bad as $index=>$raw) {
    try {
        $decoder->decode($raw);
        throw new RuntimeException('F3-47 PHP83 accepted unsafe case '.$index);
    } catch (InvalidArgumentException $e) {
        if (!str_starts_with($e->getMessage(),'TECHNICAL_ENVELOPE_')) {
            throw new RuntimeException('F3-47 PHP83 unsafe diagnostic');
        }
    }
}
echo "F3-47 PHP 8.3 standalone PASS: exact schema, escaped duplicate key, PII, invalid UTF-8 and 2KB cap.\n";
