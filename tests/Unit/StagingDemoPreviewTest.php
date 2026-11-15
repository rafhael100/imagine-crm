<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Commercial\StagingDemoPreview;
use PHPUnit\Framework\TestCase;

final class StagingDemoPreviewTest extends TestCase
{
    public function test_demo_is_a_deterministic_read_only_pipeline_with_no_personal_information(): void
    {
        $a = StagingDemoPreview::presentation('pt');
        $b = StagingDemoPreview::presentation('pt');
        $this->assertSame($a, $b);
        $this->assertSame('pt', $a['lang']);
        $this->assertSame(['new','qualified','proposal','won'], array_keys($a['pipeline']));
        $this->assertSame(6, $a['opportunity_count']);
        $this->assertSame(3, $a['task_count']);
        foreach (['can_edit','can_contact','can_notify','can_signup','can_checkout','can_activate'] as $flag) {
            $this->assertFalse($a[$flag], $flag);
        }
        $labels = array_merge(...array_values($a['pipeline']));
        $this->assertCount(6, $labels);
        foreach ($labels as $item) {
            $this->assertMatchesRegularExpression('/\ADEMO_[0-9]{3}\z/', $item['reference']);
            $this->assertStringStartsWith('EXEMPLO_', $item['label']);
            $this->assertStringStartsWith('REGISTRO_FICTICIO_', $item['detail']);
        }
        foreach ($a['tasks'] as $task) {
            $this->assertStringStartsWith('TASK_DEMO_', $task['reference']);
            $this->assertStringStartsWith('FOLLOW_UP_DEMO_', $task['subject']);
        }
    }

    public function test_demo_locales_change_only_labels_not_records(): void
    {
        $pt = StagingDemoPreview::presentation('pt');
        foreach (['en','es'] as $language) {
            $model = StagingDemoPreview::presentation($language);
            $this->assertSame($language, $model['lang']);
            $this->assertNotSame($pt['copy']['heading'], $model['copy']['heading']);
            $this->assertSame($pt['pipeline'], $model['pipeline']);
            $this->assertSame($pt['tasks'], $model['tasks']);
            $this->assertFalse($model['can_activate']);
        }
    }

    public function test_invalid_user_supplied_language_never_controls_demo_content(): void
    {
        foreach ([null, [], '<script>', '../../', 'EN', 123, ['pt']] as $language) {
            $model = StagingDemoPreview::presentation($language);
            $this->assertSame('pt', $model['lang']);
            $this->assertSame('EXEMPLO_ALFA', $model['pipeline']['new'][0]['label']);
            $this->assertFalse($model['can_contact']);
        }
    }
}
