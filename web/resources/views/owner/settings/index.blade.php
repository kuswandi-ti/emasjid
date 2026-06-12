@extends('layouts.owner')

@section('title', 'Pengaturan Fee Platform')

@section('page-header')
    <x-page-header
        title="Pengaturan Fee Platform"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'route' => 'owner.dashboard'],
            ['label' => 'Pengaturan Fee']
        ]"
    />
@endsection

@section('content')

<div class="row">
    <div class="col-lg-8">
        {{-- Form Pengaturan Fee --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cog me-2"></i>
                    Konfigurasi Fee Platform
                </h3>
            </div>
            <form action="{{ route('owner.settings.update') }}" method="POST" id="feeSettingForm">
                @csrf
                @method('PUT')
                
                <div class="card-body">
                    
                    {{-- Fee Percentage --}}
                    <div class="mb-4">
                        <label for="fee_percentage" class="form-label fw-bold">
                            Persentase Fee <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input 
                                type="number" 
                                class="form-control @error('fee_percentage') is-invalid @enderror" 
                                id="fee_percentage" 
                                name="fee_percentage" 
                                value="{{ old('fee_percentage', $settings['fee_percentage']) }}"
                                min="0"
                                max="10000"
                                step="1"
                                required
                            >
                            <span class="input-group-text">basis poin</span>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Masukkan dalam basis poin. Contoh: 250 = 2,5% | 500 = 5% | 1000 = 10%
                        </div>
                        <div id="percentageDisplay" class="mt-2 text-primary fw-bold">
                            <!-- Will be filled by JavaScript -->
                        </div>
                        @error('fee_percentage')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Fee Mechanism --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Mekanisme Fee <span class="text-danger">*</span>
                        </label>
                        
                        <div class="form-check mb-2">
                            <input 
                                class="form-check-input @error('fee_mechanism') is-invalid @enderror" 
                                type="radio" 
                                name="fee_mechanism" 
                                id="mechanism_donor" 
                                value="added_to_donor"
                                {{ old('fee_mechanism', $settings['fee_mechanism']) === 'added_to_donor' ? 'checked' : '' }}
                                required
                            >
                            <label class="form-check-label" for="mechanism_donor">
                                <strong>Ditambahkan ke Donatur</strong>
                                <div class="text-muted small">
                                    Fee ditambahkan ke nominal donasi. Donatur membayar lebih dari nominal donasi.
                                    <br>
                                    <em>Contoh: Donasi Rp 100.000 + Fee Rp 2.500 = Donatur bayar Rp 102.500</em>
                                </div>
                            </label>
                        </div>

                        <div class="form-check">
                            <input 
                                class="form-check-input @error('fee_mechanism') is-invalid @enderror" 
                                type="radio" 
                                name="fee_mechanism" 
                                id="mechanism_deduct" 
                                value="deducted_from_donation"
                                {{ old('fee_mechanism', $settings['fee_mechanism']) === 'deducted_from_donation' ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="mechanism_deduct">
                                <strong>Dipotong dari Donasi</strong>
                                <div class="text-muted small">
                                    Fee dipotong dari nominal donasi. Masjid menerima lebih sedikit dari nominal donasi.
                                    <br>
                                    <em>Contoh: Donasi Rp 100.000 - Fee Rp 2.500 = Masjid terima Rp 97.500</em>
                                </div>
                            </label>
                        </div>

                        @error('fee_mechanism')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Fee Active Status --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Status Fee
                        </label>
                        
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input @error('fee_active') is-invalid @enderror" 
                                type="checkbox" 
                                role="switch" 
                                id="fee_active" 
                                name="fee_active" 
                                value="1"
                                {{ old('fee_active', $settings['fee_active']) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="fee_active">
                                <span id="feeStatusLabel">
                                    {{ $settings['fee_active'] ? 'Fee Aktif' : 'Fee Nonaktif' }}
                                </span>
                            </label>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Jika dinonaktifkan, tidak ada fee yang dikenakan pada donasi.
                        </div>
                        @error('fee_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Simpan Pengaturan
                    </button>
                    <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Preview Kalkulasi --}}
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-calculator me-2"></i>
                    Preview Kalkulasi
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-light border">
                    <div class="mb-3">
                        <strong>Contoh Donasi:</strong>
                        <h4 class="mb-0">{{ $preview['sample_amount_formatted'] }}</h4>
                    </div>

                    <hr>

                    <div id="previewCalculation">
                        <div class="mb-2">
                            <small class="text-muted">Fee Platform:</small>
                            <div class="fw-bold text-danger" id="previewFee">{{ $preview['fee_amount_formatted'] }}</div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted" id="previewPaymentLabel">Total Dibayar Donatur:</small>
                            <div class="fw-bold text-primary" id="previewPayment">{{ $preview['payment_amount_formatted'] }}</div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted">Diterima Masjid:</small>
                            <div class="fw-bold text-success" id="previewMosque">{{ $preview['mosque_receives_formatted'] }}</div>
                        </div>
                    </div>

                    <hr>

                    <div class="alert alert-warning mb-0" id="previewMechanism">
                        <i class="fas fa-info-circle me-1"></i>
                        <small id="mechanismInfo">{{ $preview['mechanism_label'] }}</small>
                    </div>
                </div>

                <div class="text-muted small">
                    <i class="fas fa-lightbulb me-1"></i>
                    <strong>Tips:</strong> Preview ini otomatis update saat Anda mengubah pengaturan.
                </div>
            </div>
        </div>

        {{-- Penjelasan --}}
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-question-circle me-2"></i>
                    Penjelasan
                </h3>
            </div>
            <div class="card-body">
                <h6 class="fw-bold">Basis Poin (Basis Points)</h6>
                <p class="small text-muted">
                    Basis poin adalah satuan untuk persentase. 1 basis poin = 0,01%.
                    <br>
                    • 100 basis poin = 1%
                    <br>
                    • 250 basis poin = 2,5%
                    <br>
                    • 500 basis poin = 5%
                </p>

                <hr>

                <h6 class="fw-bold">Mekanisme Fee</h6>
                <p class="small text-muted mb-1">
                    <strong>Added to Donor:</strong> Lebih transparan untuk donatur, mereka tahu persis berapa yang diterima masjid.
                </p>
                <p class="small text-muted">
                    <strong>Deducted from Donation:</strong> Lebih sederhana untuk donatur, mereka hanya membayar nominal yang diinginkan.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const feePercentageInput = document.getElementById('fee_percentage');
    const feeActiveCheckbox = document.getElementById('fee_active');
    const mechanismRadios = document.querySelectorAll('input[name="fee_mechanism"]');
    const percentageDisplay = document.getElementById('percentageDisplay');
    const feeStatusLabel = document.getElementById('feeStatusLabel');
    
    // Preview elements
    const previewFee = document.getElementById('previewFee');
    const previewPayment = document.getElementById('previewPayment');
    const previewMosque = document.getElementById('previewMosque');
    const mechanismInfo = document.getElementById('mechanismInfo');
    const previewPaymentLabel = document.getElementById('previewPaymentLabel');

    const sampleAmount = 100000; // Rp 100.000

    // Update percentage display
    function updatePercentageDisplay() {
        const basisPoints = parseInt(feePercentageInput.value) || 0;
        const percentage = (basisPoints / 100).toFixed(2);
        percentageDisplay.textContent = `= ${percentage}%`;
    }

    // Update fee status label
    function updateFeeStatusLabel() {
        feeStatusLabel.textContent = feeActiveCheckbox.checked ? 'Fee Aktif' : 'Fee Nonaktif';
    }

    // Format rupiah
    function formatRupiah(amount) {
        return 'Rp ' + amount.toLocaleString('id-ID');
    }

    // Calculate and update preview
    function updatePreview() {
        const basisPoints = parseInt(feePercentageInput.value) || 0;
        const isActive = feeActiveCheckbox.checked;
        const mechanism = document.querySelector('input[name="fee_mechanism"]:checked').value;

        if (!isActive) {
            // Fee nonaktif
            previewFee.textContent = formatRupiah(0);
            previewPayment.textContent = formatRupiah(sampleAmount);
            previewMosque.textContent = formatRupiah(sampleAmount);
            mechanismInfo.textContent = 'Fee tidak aktif, tidak ada biaya yang dikenakan.';
            previewPaymentLabel.textContent = 'Total Dibayar Donatur:';
            return;
        }

        // Calculate fee
        const feeAmount = Math.round((sampleAmount * basisPoints) / 10000);

        if (mechanism === 'added_to_donor') {
            // Fee ditambahkan ke donatur
            const paymentAmount = sampleAmount + feeAmount;
            previewFee.textContent = formatRupiah(feeAmount);
            previewPayment.textContent = formatRupiah(paymentAmount);
            previewMosque.textContent = formatRupiah(sampleAmount);
            mechanismInfo.textContent = 'Biaya ditambahkan ke donatur';
            previewPaymentLabel.textContent = 'Total Dibayar Donatur:';
        } else {
            // Fee dipotong dari donasi
            const mosqueReceives = sampleAmount - feeAmount;
            previewFee.textContent = formatRupiah(feeAmount);
            previewPayment.textContent = formatRupiah(sampleAmount);
            previewMosque.textContent = formatRupiah(mosqueReceives);
            mechanismInfo.textContent = 'Biaya dipotong dari donasi';
            previewPaymentLabel.textContent = 'Total Dibayar Donatur:';
        }
    }

    // Event listeners
    feePercentageInput.addEventListener('input', function() {
        updatePercentageDisplay();
        updatePreview();
    });

    feeActiveCheckbox.addEventListener('change', function() {
        updateFeeStatusLabel();
        updatePreview();
    });

    mechanismRadios.forEach(radio => {
        radio.addEventListener('change', updatePreview);
    });

    // Initial update
    updatePercentageDisplay();
    updateFeeStatusLabel();
    updatePreview();

    // Form confirmation
    document.getElementById('feeSettingForm').addEventListener('submit', function(e) {
        const percentage = (parseInt(feePercentageInput.value) || 0) / 100;
        const mechanism = document.querySelector('input[name="fee_mechanism"]:checked').value;
        const mechanismLabel = mechanism === 'added_to_donor' ? 'ditambahkan ke donatur' : 'dipotong dari donasi';
        
        const confirmed = confirm(
            `Konfirmasi Perubahan:\n\n` +
            `Fee: ${percentage.toFixed(2)}%\n` +
            `Mekanisme: ${mechanismLabel}\n` +
            `Status: ${feeActiveCheckbox.checked ? 'Aktif' : 'Nonaktif'}\n\n` +
            `Yakin ingin menyimpan perubahan?`
        );

        if (!confirmed) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
