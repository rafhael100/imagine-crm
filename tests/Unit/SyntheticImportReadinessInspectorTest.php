<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticImportReadinessInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticImportReadinessInspectorTest extends TestCase
{
    private static function operation(): array
    {
        return [
            'plan' => 'basic',
            'operation' => 'import_csv',
            'attachment_quota_percent' => 100,
            'confirmation_claimed' => true,
            'declared_package' => 'table',
            'duplicate_warning_count' => 1,
            'formula_warning_count' => 1,
            'secret_fields_claimed' => false,
            'entity_kinds' => ['leads'],
            'target_account_state' => 'populated',
        ];
    }

    private static function mapping(): array
    {
        return [
            'entity_kind' => 'leads',
            'declared_row_count' => 3,
            'declared_duplicate_candidates' => 1,
            'target_state' => 'populated',
            'review_claimed' => true,
            'columns' => [
                ['source_position'=>0,'target_code'=>'name',
                    'nonempty_count'=>3,'formula_warning_count'=>0],
                ['source_position'=>1,'target_code'=>'consent',
                    'nonempty_count'=>1,'formula_warning_count'=>1],
            ],
        ];
    }

    public function test_aligned_claims_still_never_authorize_import_or_consent(): void
    {
        $a = self::operation(); $b = self::mapping();
        $originalA=$a; $originalB=$b;
        $inspector = new SyntheticImportReadinessInspector();
        $out = $inspector->inspect($a, $b);
        self::assertSame($originalA,$a);
        self::assertSame($originalB,$b);
        self::assertSame($out,$inspector->inspect($a,$b));
        self::assertSame('import_csv',$out['claimed_import_format']);
        self::assertSame('basic',$out['claimed_plan']);
        self::assertSame('leads',$out['declared_entity_kind']);
        self::assertSame(3,$out['declared_row_count']);
        self::assertSame(2,$out['declared_source_column_count']);
        self::assertSame(2,$out['declared_mapped_column_count']);
        self::assertSame(0,$out['declared_target_collision_count']);
        self::assertSame([
            'OPERATION_SPREADSHEET_FORMULA_REVIEW_REQUIRED',
            'OPERATION_DUPLICATE_CONFLICT_REVIEW_REQUIRED',
            'MAPPING_SPREADSHEET_FORMULA_RISK_REQUIRES_REVIEW',
            'MAPPING_CONSENT_EVIDENCE_CANNOT_BE_IMPORTED_AS_AUTHORITY',
            'MAPPING_DUPLICATE_CANDIDATES_REQUIRE_REVIEW',
            'MAPPING_EXISTING_TARGET_NEEDS_CONFLICT_DECISIONS',
        ],$out['warning_codes']);
        self::assertSame(6,$out['warning_count']);
        self::assertCount(6,$out['unfulfilled_gate_codes']);
        self::assertTrue($out['informational_only']);
        foreach($out as $key=>$value){
            if($key==='authorized'||str_starts_with($key,'can_')
                ||str_ends_with($key,'_verified')) {
                self::assertFalse($value,$key);
            }
        }
    }

    public function test_schema_entity_target_and_counts_disagreement_cannot_disappear(): void
    {
        $op=self::operation();
        $op['declared_package']='complete';
        $op['entity_kinds']=['contacts'];
        $op['target_account_state']='unknown';
        $op['duplicate_warning_count']=0;
        $op['formula_warning_count']=0;
        $out=(new SyntheticImportReadinessInspector())->inspect($op,self::mapping());
        foreach([
            'IMPORT_REQUIRES_TABLE_PACKAGE',
            'TARGET_ENTITY_NOT_DECLARED_IN_OPERATION',
            'TARGET_STATE_CLAIMS_DISAGREE',
            'DUPLICATE_COUNTS_DISAGREE',
            'FORMULA_WARNING_COUNTS_DISAGREE',
            'OPERATION_PACKAGE_SCOPE_REQUIRES_REVIEW',
            'TARGET_STATE_UNVERIFIED',
        ] as $warning){
            self::assertContains($warning,$out['warning_codes']);
        }
        self::assertFalse($out['can_read_file']);
        self::assertFalse($out['can_import']);
        self::assertFalse($out['can_merge']);
    }

    public function test_xlsx_is_supported_only_as_unverified_intent(): void
    {
        $op=self::operation();$op['operation']='import_xlsx';
        $out=(new SyntheticImportReadinessInspector())->inspect($op,self::mapping());
        self::assertSame('import_xlsx',$out['claimed_import_format']);
        self::assertFalse($out['import_file_verified']);
        self::assertFalse($out['mapping_verified']);
        self::assertFalse($out['can_import']);
    }

    public function test_unauthorized_actions_or_pii_in_either_contract_fail_closed(): void
    {
        $invalids=[
            [array_replace(self::operation(),['operation'=>'export_csv']),self::mapping()],
            [array_replace(self::operation(),['operation'=>'restore_complete']),self::mapping()],
            [array_replace(self::operation(),['operation'=>'backup_complete']),self::mapping()],
            [array_merge(self::operation(),['email'=>'private@example.test']),self::mapping()],
            [self::operation(),array_merge(self::mapping(),['tenant_id'=>'private@example.test'])],
            [self::operation(),array_replace(self::mapping(),['columns'=>[
                ['source_position'=>0,'target_code'=>'password','nonempty_count'=>1,
                    'formula_warning_count'=>0],
            ]])],
        ];
        foreach($invalids as [$op,$mapping]){
            try{
                (new SyntheticImportReadinessInspector())->inspect($op,$mapping);
                self::fail('Untrusted import contract incorrectly accepted.');
            }catch(InvalidArgumentException $e){
                self::assertSame('Revisao sintetica de importacao invalida.',$e->getMessage());
                self::assertStringNotContainsString('private@example.test',$e->getMessage());
            }
        }
    }

    public function test_output_only_has_declared_counts_fixed_warning_codes_and_no_pii(): void
    {
        $out=(new SyntheticImportReadinessInspector())->inspect(self::operation(),self::mapping());
        $json=json_encode($out,JSON_THROW_ON_ERROR);
        foreach([
            '"email"', '"phone"', '"columns"', '"rows"', '"tenant_id"',
            '"person_id"', '"header"', '"source_name"', '"file_path"',
            '"payload"', '"checksum"', '"token"', '"password"',
        ] as $private){
            self::assertStringNotContainsString($private,$json);
        }
        self::assertFalse($out['can_confirm_consent']);
        self::assertFalse($out['can_write_file']);
        self::assertFalse($out['can_persist']);
    }
}
