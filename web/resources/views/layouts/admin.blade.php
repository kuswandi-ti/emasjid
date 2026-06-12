@extends('layouts.app')

@section('body-class', 'layout-fixed sidebar-expand-lg bg-body-tertiary')

@section('body')
<div class="app-wrapper">

    {{-- Top Navbar --}}
    <x-navbar title="{{ auth()->user()->activeMosque?->name ?? 'Admin Panel' }}" />

    {{-- Sidebar --}}
    @php
        $menuItems = [
            [
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon'  => 'fas fa-tachometer-alt',
            ],
            [
                'label' => 'Profil Masjid',
                'route' => 'admin.mosque.edit',
                'icon'  => 'fas fa-mosque',
            ],
            [
                'label' => 'Jadwal Sholat',
                'route' => 'admin.schedules.index',
                'icon'  => 'fas fa-clock',
            ],
            [
                'label' => 'Kegiatan',
                'route' => 'admin.activities.index',
                'icon'  => 'fas fa-calendar-alt',
            ],
            [
                'label'    => 'Keuangan',
                'icon'     => 'fas fa-money-bill-wave',
                'children' => [
                    ['label' => 'Pemasukan',  'route' => 'admin.finance.income.index'],
                    ['label' => 'Pengeluaran', 'route' => 'admin.finance.expense.index'],
                    ['label' => 'Laporan',    'route' => 'admin.finance.report.index'],
                ],
            ],
            [
                'label' => 'Pengumuman',
                'route' => 'admin.announcements.index',
                'icon'  => 'fas fa-bullhorn',
            ],
            [
                'label'    => 'Jamaah & Pengurus',
                'icon'     => 'fas fa-users',
                'children' => [
                    ['label' => 'Jamaah',   'route' => 'admin.congregation.index'],
                    ['label' => 'Pengurus', 'route' => 'admin.staff.index'],
                ],
            ],
            [
                'label' => 'Donasi Masuk',
                'route' => 'admin.donations.index',
                'icon'  => 'fas fa-hand-holding-heart',
            ],
        ];
    @endphp
    <x-sidebar :items="$menuItems" brand="Admin Masjid" brand-route="admin.dashboard" />

    {{-- Main Content --}}
    <main class="app-main">
        <div class="app-content-header">
            @yield('page-header')
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <x-alert />
                @yield('content')
            </div>
        </div>
    </main>

</div>
@endsection
