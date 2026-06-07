<?php

namespace Tests\Unit\Enums;

use App\Enums\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionTypeTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $cases = TransactionType::cases();

        $this->assertCount(2, $cases);
        $this->assertSame('income', TransactionType::Income->value);
        $this->assertSame('expense', TransactionType::Expense->value);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        foreach (TransactionType::cases() as $case) {
            $label = $case->labels();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_from_creates_instance(): void
    {
        $this->assertSame(TransactionType::Income, TransactionType::from('income'));
        $this->assertSame(TransactionType::Expense, TransactionType::from('expense'));
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(TransactionType::tryFrom('invalid'));
    }
}
