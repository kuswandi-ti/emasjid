<?php

namespace Tests\Unit\Enums;

use App\Enums\AnnouncementStatus;
use PHPUnit\Framework\TestCase;

class AnnouncementStatusTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $cases = AnnouncementStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertSame('draft', AnnouncementStatus::Draft->value);
        $this->assertSame('published', AnnouncementStatus::Published->value);
        $this->assertSame('archived', AnnouncementStatus::Archived->value);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        foreach (AnnouncementStatus::cases() as $case) {
            $label = $case->labels();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_from_creates_instance(): void
    {
        $this->assertSame(AnnouncementStatus::Draft, AnnouncementStatus::from('draft'));
        $this->assertSame(AnnouncementStatus::Published, AnnouncementStatus::from('published'));
        $this->assertSame(AnnouncementStatus::Archived, AnnouncementStatus::from('archived'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(AnnouncementStatus::tryFrom('invalid'));
    }
}
