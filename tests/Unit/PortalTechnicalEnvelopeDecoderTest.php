<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\PortalTechnicalEnvelopeDecoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortalTechnicalEnvelopeDecoderTest extends TestCase
{
    private static function frame(): array
    {
        return [
            'protocol'=>'imagine.lead-intake',
            'schema_version'=>1,
            'organization_reference'=>'11111111-1111-4111-8111-111111111111',
            'binding_reference'=>'22222222-2222-4222-8222-222222222222',
            'submission_reference'=>'33333333-3333-4333-8333-333333333333',
            'site_reference'=>'44444444-4444-4444-8444-444444444444',
            'form_reference'=>'55555555-5555-4555-8555-555555555555',
            'destination_reference'=>'66666666-6666-4666-8666-666666666666',
        ];
    }

    private static function json(array $frame): string
    {
        return json_encode($frame,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
    }

    public function test_flat_valid_json_returns_untrusted_exact_eight_fields(): void
    {
        $decoder = new PortalTechnicalEnvelopeDecoder();
        $frame=self::frame();
        foreach ([
            self::json($frame),
            " \n\t".self::json($frame)."\r ",
            self::json(array_reverse($frame,true)),
            str_replace('"protocol"', '"\u0070rotocol"', self::json($frame)),
            str_replace('"imagine.lead-intake"', '"imagine.lead-\u0069ntake"', self::json($frame)),
            str_replace('"11111111', '"\u00311111111', self::json($frame)),
        ] as $raw) {
            self::assertSame($frame,$decoder->decode($raw));
        }
    }

    public function test_rejects_identical_and_unicode_escaped_duplicate_keys(): void
    {
        $raw=self::json(self::frame());
        $prefix='"protocol":"imagine.lead-intake",';
        self::assertStringStartsWith('{'.$prefix,$raw);
        foreach ([
            '{"protocol":"lookalike",'.substr($raw,1),
            '{"\u0070rotocol":"lookalike",'.substr($raw,1),
            str_replace('"protocol":"imagine.lead-intake",',
                '"protocol":"imagine.lead-intake","protocol":"imagine.lead-intake",',$raw),
        ] as $bad) {
            try {
                (new PortalTechnicalEnvelopeDecoder())->decode($bad);
                self::fail('duplicate top-level JSON key was not rejected');
            } catch (InvalidArgumentException $e) {
                self::assertSame('TECHNICAL_ENVELOPE_DUPLICATE_KEY',$e->getMessage());
            }
        }
    }

    public function test_rejects_unknown_contacts_and_nested_payloads(): void
    {
        $frame=self::frame();
        foreach ([
            ['email'=>'synthetic@example.invalid'],
            ['phone'=>'5550100'],
            ['name'=>'synthetic-person'],
            ['message'=>'test'],
            ['consent'=>true],
            ['payload'=>['email'=>'test@example.invalid']],
            ['service_role'=>'admin'],
            ['authorization'=>['allow'=>true]],
        ] as $extra) {
            $bad=self::json([...$frame,...$extra]);
            try {
                (new PortalTechnicalEnvelopeDecoder())->decode($bad);
                self::fail('unexpected metadata was accepted');
            } catch (InvalidArgumentException $e) {
                self::assertContains($e->getMessage(),[
                    'TECHNICAL_ENVELOPE_FIELDS_INVALID',
                    'TECHNICAL_ENVELOPE_STRUCTURE_INVALID',
                    'TECHNICAL_ENVELOPE_VALUE_INVALID'
                ]);
                self::assertStringNotContainsString('example.invalid',$e->getMessage());
            }
        }
    }

    public function test_limits_size_and_disallows_non_flat_objects_arrays_scalars(): void
    {
        $decoder=new PortalTechnicalEnvelopeDecoder();
        $valid=self::json(self::frame());
        foreach ([
            '',
            str_repeat(' ', PortalTechnicalEnvelopeDecoder::MAX_BYTES+1),
            $valid.str_repeat(' ',PortalTechnicalEnvelopeDecoder::MAX_BYTES),
            'true','null','[]','3',
            '{ "test": [] }',
            '{ "test": {} }',
            '{ "test": true }',
            '{ "test": null }',
            '{ "test": [1,2] }',
            '{}',
            '{"protocol":"x"}',
            substr($valid,0,-1).',}',
            $valid.'{"protocol":"x"}',
        ] as $bad) {
            $this->assertRejects($bad);
        }
    }

    public function test_rejects_invalid_encodings_control_bytes_and_number_formats(): void
    {
        $valid=self::json(self::frame());
        foreach ([
            str_replace('"schema_version":1','"schema_version":01',$valid),
            str_replace('"schema_version":1','"schema_version":1.0',$valid),
            str_replace('"schema_version":1','"schema_version":1e0',$valid),
            str_replace('"schema_version":1','"schema_version":"1"',$valid),
            str_replace('"schema_version":1','"schema_version":true',$valid),
            str_replace('"schema_version":1','"schema_version":null',$valid),
            str_replace('"schema_version":1','"schema_version":1 2',$valid),
            str_replace('"protocol"','"\uD800"',$valid),
            str_replace('"protocol"','"bad\q"',$valid),
            str_replace('"protocol"','"pro'."\x00".'tocol"',$valid),
            str_replace('imagine.lead-intake','imagine.lead-'.chr(0xff).'intake',$valid),
            str_replace('"33333333-3333-4333-8333-333333333333"',
                '"00000000-0000-0000-0000-000000000000"',$valid),
        ] as $bad) {
            $this->assertRejects($bad);
        }
    }

    public function test_rejects_malformed_escape_and_trailing_content(): void
    {
        $valid=self::json(self::frame());
        foreach ([
            str_replace('"protocol"','"proto\\"col"',$valid),
            str_replace('"protocol"','"proto\\uXX00col"',$valid),
            '{'.$valid,
            $valid.' extra',
            $valid."\x01",
            trim($valid,'}'),
        ] as $bad) {
            $this->assertRejects($bad);
        }
    }

    private function assertRejects(string $input): void
    {
        try {
            (new PortalTechnicalEnvelopeDecoder())->decode($input);
            self::fail('malformed or oversized technical JSON accepted');
        } catch (InvalidArgumentException $e) {
            self::assertStringStartsWith('TECHNICAL_ENVELOPE_',$e->getMessage());
            self::assertStringNotContainsString('11111111-1111',$e->getMessage());
        }
    }
}
