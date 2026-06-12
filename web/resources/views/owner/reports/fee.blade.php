@extends('owner.layouts.app')

@section('title', 'Laporan Fee Platform')

@section('content')

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col">
        <h2>Laporan Fee Platform</h2>
        <p class="text-muted">Ringkasan pendapatan fee platform per periode bulan</p>
    </div>
</div>

{{-- Flash Messages --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

{{-- Filter Form --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('owner.reports.fee.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="month" class="form-label fw-semibold">Bulan</label>
                <select name="month" id="month" class="form-select">
                    @php
                        $bulanIndonesia = [
                            1  => 'Januari',
                            2  => 'Februari',
                            3  => 'Maret',
                            4  => 'April',
                            5  => 'Mei',
                            6  => 'Juni',
                            7  => 'Juli',
                            8  => 'Agustus',
                            9  => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
                        ];
                    @endphp
                    @foreach ($bulanIndonesia as $num => $label)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="year" class="form-label fw-semibold">Tahun</label>
                <input
                    type="number"
                    name="year"
                    id="year"
                    class="form-control"
                    value="{{ $selectedYear }}"
                    min="2020"
                    max="{{ date('Y') + 1 }}"
                    required
                >
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-4 mb-4">
    {{-- Total Fee --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Fee</h6>
                        <h3 class="mb-0 fw-bold text-primary">
                            Rp {{ number_format($summary['total_fee'], 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-cash-stack fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Jumlah Donasi --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Jumlah Donasi</h6>
                        <h3 class="mb-0 fw-bold text-success">
                            {{ number_format($summary['count'], 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-receipt fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rata-rata Fee --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Rata-rata Fee</h6>
                        <h3 class="mb-0 fw-bold text-info">
                            Rp {{ number_format($summary['average'], 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-graph-up fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Empty State --}}
@if ($summary['count'] === 0)
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <span>Belum ada data fee pada periode ini.</span>
    </div>
@endif

{{-- Export CSV Button --}}
<div class="d-flex justify-content-end mb-4">
    <a
        href="{{ route('owner.reports.fee.export', ['month' => $selectedMonth, 'year' => $selectedYear]) }}"
        class="btn btn-outline-success"
    >
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
    </a>
</div>

{{-- Bar Chart --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-bar-chart-line me-1"></i> Tren Fee 12 Bulan Terakhir
        </h5>
    </div>
    <div class="card-body">
        <canvas id="feeChart" height="100" aria-label="Bar chart tren fee 12 bulan terakhir" role="img"></canvas>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    var labels = @json($chartData['labels']);
    var values = @json($chartData['values']);

    function formatRupiah(value) {
        return 'Rp ' + value.toLocaleString('id-ID');
    }

    var ctx = document.getElementById('feeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Fee',
                data: values,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return ' ' + formatRupiah(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return formatRupiah(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                    }
                }
            }
        }
    });
}());
</script>
@endpush
