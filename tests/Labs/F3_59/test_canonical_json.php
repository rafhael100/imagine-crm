<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/Core/Leads/SignedServiceHandoffBoundary.php';
use App\Core\Leads\SignedServiceHandoffBoundary;
$frame=[
 'protocol'=>'imagine.lead-intake','schema_version'=>1,
 'organization_reference'=>'11111111-1111-4111-8111-111111111111',
 'binding_reference'=>'22222222-2222-4222-8222-222222222222',
 'submission_reference'=>'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
 'site_reference'=>'44444444-4444-4444-8444-444444444444',
 'form_reference'=>'55555555-5555-4555-8555-555555555555',
 'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$gate=new SignedServiceHandoffBoundary();
$raw=json_encode($frame,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if($gate->decodeCanonicalJson($raw)!==$frame)
 throw new RuntimeException('F3-59 canonical frame rejected');
$duplicate='{"protocol":"imagine.lead-intake",'.substr($raw,1);
$escaped=str_replace('"protocol"','"pro\\u0074ocol"',$raw);
$reordered=json_encode(array_reverse($frame,true),JSON_THROW_ON_ERROR);
$extra=substr($raw,0,-1).',"email":"test@example.invalid"}';
$variants=[
 'duplicate_key'=>$duplicate,
 'unicode_escaped_key'=>$escaped,
 'reordered_keys'=>$reordered,
 'leading_whitespace'=>' '.$raw,
 'trailing_newline'=>$raw."\n",
 'extra_contact_field'=>$extra,
 'noncanonical_version'=>str_replace('"schema_version":1','"schema_version":1.0',$raw),
 'oversized'=>str_repeat(' ' ,2049),
 'invalid_json'=>'{"protocol":',
 'array_json'=>'[]',
 'empty'=>'',
];
foreach($variants as $name=>$candidate){
 if($gate->decodeCanonicalJson($candidate)!==null)
  throw new RuntimeException('F3-59 accepted unsafe variant: '.$name);
}
echo 'F3-59 PASS: canonical eight-field JSON and '.count($variants)." malformed/ambiguous cases.\n";
