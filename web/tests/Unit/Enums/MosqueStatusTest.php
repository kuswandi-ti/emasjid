<?php

namespace Tests\Unit\Enums;

use App\Enums\MosqueStatus;
use PHPUnit\Framework\TestCase;

class MosqueStatusTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $cases = MosqueStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertSame('pending', MosqueStatus::Pending->value);
        $this->assertSame('active', MosqueStatus::Active->value);
        $this->assertSame('suspended', MosqueStatus::Suspended->value);
        $this->assertSame('rejected', MosqueStatus::Rejected->value);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        foreach (MosqueStatus::cases() as $case) {
            $label = $case->labels();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_from_creates_instance(): void
    {
        $this->assertSame(MosqueStatus::Pending, MosqueStatus::from('pending'));
        $this->assertSame(MosqueStatus::Active, MosqueStatus::from('active'));
        $this->assertSame(MosqueStatus::Suspended, MosqueStatus::from('suspended'));
        $this->assertSame(MosqueStatus::Rejected, MosqueStatus::from('rejected'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(MosqueStatus::tryFrom('invalid'));
    }
}
