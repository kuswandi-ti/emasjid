<?php

namespace Tests\Unit\Enums;

use App\Enums\ActivityStatus;
use PHPUnit\Framework\TestCase;

class ActivityStatusTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $cases = ActivityStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertSame('upcoming', ActivityStatus::Upcoming->value);
        $this->assertSame('ongoing', ActivityStatus::Ongoing->value);
        $this->assertSame('completed', ActivityStatus::Completed->value);
        $this->assertSame('cancelled', ActivityStatus::Cancelled->value);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        foreach (ActivityStatus::cases() as $case) {
            $label = $case->labels();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_from_creates_instance(): void
    {
        $this->assertSame(ActivityStatus::Upcoming, ActivityStatus::from('upcoming'));
        $this->assertSame(ActivityStatus::Ongoing, ActivityStatus::from('ongoing'));
        $this->assertSame(ActivityStatus::Completed, ActivityStatus::from('completed'));
        $this->assertSame(ActivityStatus::Cancelled, ActivityStatus::from('cancelled'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(ActivityStatus::tryFrom('invalid'));
    }
}
