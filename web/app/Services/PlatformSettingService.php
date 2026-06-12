<?php

namespace App\Services;

use App\Contracts\Repositories\PlatformSettingRepositoryInterface;
use Illuminate\Support\Collection;

class PlatformSettingService
{
    public function __construct(
        private PlatformSettingRepositoryInterface $repository
    ) {}

    /**
     * Get all platform settings.
     */
    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Get fee settings (percentage, mechanism, active).
     */
    public function getFeeSettings(): array
    {
        return [
            'fee_percentage' => (int) $this->repository->getValue('platform_fee_percentage', 250),
            'fee_mechanism' => $this->repository->getValue('platform_fee_mechanism', 'added_to_donor'),
            'fee_active' => (bool) $this->repository->getValue('platform_fee_active', true),
        ];
    }

    /**
     * Update fee settings.
     */
    public function updateFeeSettings(array $data): bool
    {
        $settings = [];

        if (isset($data['fee_percentage'])) {
            $settings['platform_fee_percentage'] = (string) $data['fee_percentage'];
        }

        if (isset($data['fee_mechanism'])) {
            $settings['platform_fee_mechanism'] = $data['fee_mechanism'];
        }

        if (isset($data['fee_active'])) {
            $settings['platform_fee_active'] = $data['fee_active'] ? '1' : '0';
        }

        return $this->repository->setMany($settings);
    }

    /**
     * Calculate fee amount based on donation amount.
     */
    public function calculateFee(int $amount): array
    {
        $settings = $this->getFeeSettings();

        if (!$settings['fee_active']) {
            return [
                'fee_amount' => 0,
                'payment_amount' => $amount,
                'mosque_receives' => $amount,
            ];
        }

        $feePercentage = $settings['fee_percentage'];
        $feeMechanism = $settings['fee_mechanism'];

        // Calculate fee (basis poin: 250 = 2.5%)
        $feeAmount = (int) round(($amount * $feePercentage) / 10000);

        if ($feeMechanism === 'added_to_donor') {
            // Fee ditambahkan ke donatur
            return [
                'fee_amount' => $feeAmount,
                'payment_amount' => $amount + $feeAmount,
                'mosque_receives' => $amount,
            ];
        }

        // Fee dipotong dari donasi (deducted_from_donation)
        return [
            'fee_amount' => $feeAmount,
            'payment_amount' => $amount,
            'mosque_receives' => $amount - $feeAmount,
        ];
    }

    /**
     * Get fee preview example for given amount.
     */
    public function getFeePreview(int $sampleAmount = 100000): array
    {
        $settings = $this->getFeeSettings();
        $calculation = $this->calculateFee($sampleAmount);

        return [
            'settings' => $settings,
            'sample_amount' => $sampleAmount,
            'sample_amount_formatted' => 'Rp ' . number_format($sampleAmount, 0, ',', '.'),
            'fee_amount' => $calculation['fee_amount'],
            'fee_amount_formatted' => 'Rp ' . number_format($calculation['fee_amount'], 0, ',', '.'),
            'payment_amount' => $calculation['payment_amount'],
            'payment_amount_formatted' => 'Rp ' . number_format($calculation['payment_amount'], 0, ',', '.'),
            'mosque_receives' => $calculation['mosque_receives'],
            'mosque_receives_formatted' => 'Rp ' . number_format($calculation['mosque_receives'], 0, ',', '.'),
            'mechanism_label' => $this->getMechanismLabel($settings['fee_mechanism']),
        ];
    }

    /**
     * Get human-readable label for fee mechanism.
     */
    private function getMechanismLabel(string $mechanism): string
    {
        return match ($mechanism) {
            'added_to_donor' => 'Biaya ditambahkan ke donatur',
            'deducted_from_donation' => 'Biaya dipotong dari donasi',
            default => 'Tidak diketahui',
        };
    }
}
