<?php

namespace App\Providers;

use App\Contracts\Repositories\AnnouncementRepositoryInterface;
use App\Contracts\Repositories\CashTransactionRepositoryInterface;
use App\Contracts\Repositories\CongregationRepositoryInterface;
use App\Contracts\Repositories\DonationRepositoryInterface;
use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Contracts\Repositories\PlatformSettingRepositoryInterface;
use App\Contracts\Repositories\ScheduleRepositoryInterface;
use App\Contracts\Repositories\StaffRepositoryInterface;
use App\Repositories\AnnouncementRepository;
use App\Repositories\CashTransactionRepository;
use App\Repositories\CongregationRepository;
use App\Repositories\DonationRepository;
use App\Repositories\MosqueRepository;
use App\Repositories\PlatformSettingRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\StaffRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public array $bindings = [
        MosqueRepositoryInterface::class => MosqueRepository::class,
        ScheduleRepositoryInterface::class => ScheduleRepository::class,
        CashTransactionRepositoryInterface::class => CashTransactionRepository::class,
        DonationRepositoryInterface::class => DonationRepository::class,
        AnnouncementRepositoryInterface::class => AnnouncementRepository::class,
        CongregationRepositoryInterface::class => CongregationRepository::class,
        StaffRepositoryInterface::class => StaffRepository::class,
        PlatformSettingRepositoryInterface::class => PlatformSettingRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
