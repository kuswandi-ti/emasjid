@extends('layouts.app')

@section('body-class', 'layout-fixed sidebar-expand-lg bg-body-tertiary')

@section('body')
<div class="app-wrapper">

    {{-- Top Navbar --}}
    <x-navbar title="Owner Panel" />

    {{-- Sidebar --}}
    @php
        $menuItems = [
            [
                'label' => 'Dashboard',
                'route' => 'owner.dashboard',
                'icon'  => 'fas fa-tachometer-alt',
            ],
            [
                'label'    => 'Masjid',
                'icon'     => 'fas fa-mosque',
                'children' => [
                    ['label' => 'Semua Masjid',   'route' => 'owner.mosques.index'],
                    ['label' => 'Pending Verifikasi', 'route' => 'owner.mosques.pending'],
                ],
            ],
            [
                'label' => 'Pengaturan Fee',
                'route' => 'owner.settings.index',
                'icon'  => 'fas fa-cog',
            ],
            [
                'label' => 'Akun Owner',
                'route' => 'owner.users.index',
                'icon'  => 'fas fa-users-cog',
            ],
            [
                'label' => 'Laporan Fee',
                'route' => 'owner.reports.index',
                'icon'  => 'fas fa-chart-bar',
            ],
        ];
    @endphp
    <x-sidebar :items="$menuItems" brand="Owner Panel" brand-route="owner.dashboard" />

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
