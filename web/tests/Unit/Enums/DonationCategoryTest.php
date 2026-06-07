<?php

namespace Tests\Unit\Enums;

use App\Enums\DonationCategory;
use PHPUnit\Framework\TestCase;

class DonationCategoryTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $cases = DonationCategory::cases();

        $this->assertCount(4, $cases);
        $this->assertSame('infaq', DonationCategory::Infaq->value);
        $this->assertSame('zakat', DonationCategory::Zakat->value);
        $this->assertSame('sadaqah', DonationCategory::Sadaqah->value);
        $this->assertSame('waqf', DonationCategory::Waqf->value);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        foreach (DonationCategory::cases() as $case) {
            $label = $case->labels();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_from_creates_instance(): void
    {
        $this->assertSame(DonationCategory::Infaq, DonationCategory::from('infaq'));
        $this->assertSame(DonationCategory::Zakat, DonationCategory::from('zakat'));
        $this->assertSame(DonationCategory::Sadaqah, DonationCategory::from('sadaqah'));
        $this->assertSame(DonationCategory::Waqf, DonationCategory::from('waqf'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(DonationCategory::tryFrom('invalid'));
    }
}
