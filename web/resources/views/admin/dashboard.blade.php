@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-header')
    <x-page-header
        title="Dashboard Masjid"
        :breadcrumbs="[['label' => 'Dashboard']]"
    />
@endsection

@section('content')

{{-- Stat cards --}}
<div class="row">
    <x-stat-card
        label="Total Jamaah"
        value="0"
        icon="fas fa-users"
        color="success"
        footer="Lihat jamaah"
        route="admin.congregation.index"
    />
    <x-stat-card
        label="Donasi Bulan Ini"
        value="Rp 0"
        icon="fas fa-hand-holding-heart"
        color="primary"
        footer="Lihat donasi"
        route="admin.donations.index"
    />
    <x-stat-card
        label="Pemasukan Kas"
        value="Rp 0"
        icon="fas fa-arrow-up"
        color="info"
        footer="Lihat laporan"
        route="admin.finance.report.index"
    />
    <x-stat-card
        label="Pengumuman Aktif"
        value="0"
        icon="fas fa-bullhorn"
        color="warning"
        footer="Lihat pengumuman"
        route="admin.announcements.index"
    />
</div>

@endsection
