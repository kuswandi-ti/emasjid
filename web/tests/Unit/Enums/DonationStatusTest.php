<?php

namespace Tests\Unit\Enums;

use App\Enums\DonationStatus;
use PHPUnit\Framework\TestCase;

class DonationStatusTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $cases = DonationStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertSame('pending', DonationStatus::Pending->value);
        $this->assertSame('confirmed', DonationStatus::Confirmed->value);
        $this->assertSame('failed', DonationStatus::Failed->value);
        $this->assertSame('expired', DonationStatus::Expired->value);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        foreach (DonationStatus::cases() as $case) {
            $label = $case->labels();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_from_creates_instance(): void
    {
        $this->assertSame(DonationStatus::Pending, DonationStatus::from('pending'));
        $this->assertSame(DonationStatus::Confirmed, DonationStatus::from('confirmed'));
        $this->assertSame(DonationStatus::Failed, DonationStatus::from('failed'));
        $this->assertSame(DonationStatus::Expired, DonationStatus::from('expired'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(DonationStatus::tryFrom('invalid'));
    }
}
