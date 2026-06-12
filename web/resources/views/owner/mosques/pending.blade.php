@extends('owner.layouts.app')

@section('title', 'Pending Mosques')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2>Pending Verification</h2>
        <p class="text-muted">Review and process mosque registration requests</p>
    </div>
</div>

{{-- Task 13.2: Search Form --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('owner.mosques.pending') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="search" class="form-label">Cari Masjid</label>
                <input type="text"
                       id="search"
                       name="search"
                       class="form-control"
                       placeholder="Cari berdasarkan nama, kota, atau email admin..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
                <a href="{{ route('owner.mosques.pending') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Task 13.3: Pending Mosques Table --}}
<div class="card">
    <div class="card-body">
        @if($mosques->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-check-circle fs-1 text-success mb-3 d-block"></i>
                <h5 class="text-muted">Tidak ada masjid yang menunggu verifikasi</h5>
                <p class="text-muted mb-0">Semua pendaftaran masjid telah diproses.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Masjid</th>
                            <th>Kota</th>
                            <th>Email Admin</th>
                            <th>Telepon</th>
                            <th>Tanggal Daftar</th>
                            <th>Menunggu (hari)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mosques as $mosque)
                            @php
                                $daysWaiting = $mosque->created_at->diffInDays(now());
                            @endphp
                            <tr @if($daysWaiting > 7) style="background-color: #FFFDE7" @endif>
                                <td>
                                    <strong>{{ $mosque->name }}</strong>
                                </td>
                                <td>{{ $mosque->city }}</td>
                                <td>
                                    @if($mosque->admin)
                                        {{ $mosque->admin->email }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($mosque->admin && $mosque->admin->phone)
                                        {{ $mosque->admin->phone }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $mosque->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($daysWaiting > 7)
                                        <span class="badge bg-warning text-dark">
                                            {{ $daysWaiting }} hari
                                        </span>
                                    @else
                                        {{ $daysWaiting }} hari
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('owner.mosques.show', $mosque->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $mosques->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
