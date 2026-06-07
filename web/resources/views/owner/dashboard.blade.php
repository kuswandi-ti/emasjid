@extends('layouts.owner')

@section('title', 'Dashboard')

@section('page-header')
    <x-page-header
        title="Dashboard"
        :breadcrumbs="[['label' => 'Dashboard']]"
    />
@endsection

@section('content')

{{-- Stat cards --}}
<div class="row">
    <x-stat-card
        label="Total Masjid"
        value="0"
        icon="fas fa-mosque"
        color="success"
        footer="Lihat semua"
        route="owner.mosques.index"
    />
    <x-stat-card
        label="Pending Verifikasi"
        value="0"
        icon="fas fa-hourglass-half"
        color="warning"
        footer="Tinjau sekarang"
        route="owner.mosques.pending"
    />
    <x-stat-card
        label="Total Jamaah"
        value="0"
        icon="fas fa-users"
        color="info"
    />
    <x-stat-card
        label="Pendapatan Fee"
        value="Rp 0"
        icon="fas fa-money-bill-wave"
        color="primary"
        footer="Lihat laporan"
        route="owner.reports.index"
    />
</div>

@endsection
