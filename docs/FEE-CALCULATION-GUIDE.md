# Fee Calculation Guide

> Panduan lengkap untuk perhitungan fee platform pada sistem donasi EMasjid.

---

## 📐 Formula Dasar

### Basis Points to Percentage

```
percentage = basis_points / 100
```

**Contoh:**
- 250 basis points = 2.5%
- 500 basis points = 5%
- 1000 basis points = 10%

### Fee Amount Calculation

```
fee_amount = round((donation_amount × fee_percentage) / 10000)
```

**Mengapa 10000?**
- Karena fee_percentage dalam basis points (1 bp = 0.01%)
- 100 (untuk konversi persen) × 100 (basis points) = 10000

---

## 🔀 Mekanisme Fee

### 1. Added to Donor

Fee ditambahkan ke nominal donasi. Donatur menanggung fee.

```
amount = nominal yang diinginkan donatur
fee_amount = (amount × fee_percentage) / 10000
payment_amount = amount + fee_amount
mosque_receives = amount
```

**Contoh:**
```
amount = Rp 100.000
fee_percentage = 250 (2.5%)
fee_amount = round((100000 × 250) / 10000) = Rp 2.500
payment_amount = 100000 + 2500 = Rp 102.500
mosque_receives = Rp 100.000
```

**User Flow:**
1. User input: "Saya mau donasi Rp 100.000"
2. System shows: "Total pembayaran: Rp 102.500 (Rp 100.000 + Rp 2.500 fee)"
3. User pays: Rp 102.500
4. Mosque receives: Rp 100.000
5. Platform gets: Rp 2.500

**Advantages:**
- ✅ Transparent untuk donatur
- ✅ Masjid pasti terima nominal penuh
- ✅ Platform fee terjamin

**Disadvantages:**
- ❌ Donatur perlu bayar lebih
- ❌ Friction lebih tinggi (extra cost)

---

### 2. Deducted from Donation

Fee dipotong dari nominal donasi. Masjid yang "menanggung" fee (receive less).

```
amount = nominal yang diinginkan donatur
fee_amount = (amount × fee_percentage) / 10000
payment_amount = amount
mosque_receives = amount - fee_amount
```

**Contoh:**
```
amount = Rp 100.000
fee_percentage = 250 (2.5%)
fee_amount = round((100000 × 250) / 10000) = Rp 2.500
payment_amount = Rp 100.000
mosque_receives = 100000 - 2500 = Rp 97.500
```

**User Flow:**
1. User input: "Saya mau donasi Rp 100.000"
2. System shows: "Total pembayaran: Rp 100.000"
3. User pays: Rp 100.000
4. Mosque receives: Rp 97.500
5. Platform gets: Rp 2.500

**Advantages:**
- ✅ Sederhana untuk donatur
- ✅ No extra cost perception
- ✅ Lower friction

**Disadvantages:**
- ❌ Masjid terima lebih sedikit
- ❌ Kurang transparan
- ❌ Donatur tidak sadar ada fee

---

## 🧮 Calculation Examples

### Example 1: Small Donation (Rp 10.000)

**Settings:**
- Fee: 250 bp (2.5%)
- Mechanism: Added to Donor

**Calculation:**
```
fee_amount = round((10000 × 250) / 10000) = 250
payment_amount = 10000 + 250 = 10250
mosque_receives = 10000
```

**Result:**
- Donatur bayar: Rp 10.250
- Masjid terima: Rp 10.000
- Platform fee: Rp 250

---

### Example 2: Medium Donation (Rp 500.000)

**Settings:**
- Fee: 500 bp (5%)
- Mechanism: Deducted from Donation

**Calculation:**
```
fee_amount = round((500000 × 500) / 10000) = 25000
payment_amount = 500000
mosque_receives = 500000 - 25000 = 475000
```

**Result:**
- Donatur bayar: Rp 500.000
- Masjid terima: Rp 475.000
- Platform fee: Rp 25.000

---

### Example 3: Large Donation (Rp 10.000.000)

**Settings:**
- Fee: 250 bp (2.5%)
- Mechanism: Added to Donor

**Calculation:**
```
fee_amount = round((10000000 × 250) / 10000) = 250000
payment_amount = 10000000 + 250000 = 10250000
mosque_receives = 10000000
```

**Result:**
- Donatur bayar: Rp 10.250.000
- Masjid terima: Rp 10.000.000
- Platform fee: Rp 250.000

---

### Example 4: Fee Inactive

**Settings:**
- Fee: 250 bp (2.5%)
- **Fee Active: NO**
- Mechanism: (doesn't matter)

**Calculation:**
```
fee_amount = 0
payment_amount = amount
mosque_receives = amount
```

**Result for Rp 100.000:**
- Donatur bayar: Rp 100.000
- Masjid terima: Rp 100.000
- Platform fee: Rp 0

---

## 💻 Implementation Code

### Laravel Service

```php
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
        return [
            'fee_amount' => $feeAmount,
            'payment_amount' => $amount + $feeAmount,
            'mosque_receives' => $amount,
        ];
    }

    // deducted_from_donation
    return [
        'fee_amount' => $feeAmount,
        'payment_amount' => $amount,
        'mosque_receives' => $amount - $feeAmount,
    ];
}
```

### Flutter/Dart

```dart
class FeeCalculator {
  static FeeCalculation calculate({
    required int amount,
    required int feePercentage,
    required String feeMechanism,
    required bool feeActive,
  }) {
    if (!feeActive) {
      return FeeCalculation(
        feeAmount: 0,
        paymentAmount: amount,
        mosqueReceives: amount,
      );
    }

    // Calculate fee (basis points: 250 = 2.5%)
    final feeAmount = ((amount * feePercentage) / 10000).round();

    if (feeMechanism == 'added_to_donor') {
      return FeeCalculation(
        feeAmount: feeAmount,
        paymentAmount: amount + feeAmount,
        mosqueReceives: amount,
      );
    }

    // deducted_from_donation
    return FeeCalculation(
      feeAmount: feeAmount,
      paymentAmount: amount,
      mosqueReceives: amount - feeAmount,
    );
  }
}
```

### JavaScript (Frontend Preview)

```javascript
function calculateFee(amount, feePercentage, feeMechanism, feeActive) {
    if (!feeActive) {
        return {
            feeAmount: 0,
            paymentAmount: amount,
            mosqueReceives: amount
        };
    }

    const feeAmount = Math.round((amount * feePercentage) / 10000);

    if (feeMechanism === 'added_to_donor') {
        return {
            feeAmount: feeAmount,
            paymentAmount: amount + feeAmount,
            mosqueReceives: amount
        };
    }

    return {
        feeAmount: feeAmount,
        paymentAmount: amount,
        mosqueReceives: amount - feeAmount
    };
}
```

---

## 📊 Database Schema

### Donations Table

```sql
CREATE TABLE donations (
    id BIGINT UNSIGNED PRIMARY KEY,
    mosque_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(20) NOT NULL,
    
    -- Amount breakdown (snapshot at creation time)
    amount BIGINT UNSIGNED NOT NULL,              -- Original donation amount
    fee_amount BIGINT UNSIGNED NOT NULL,          -- Platform fee
    payment_amount BIGINT UNSIGNED NOT NULL,      -- What donor pays
    mosque_receives BIGINT UNSIGNED NOT NULL,     -- What mosque gets
    
    -- Fee mechanism snapshot
    fee_mechanism VARCHAR(30) NOT NULL,
    
    -- Other fields...
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Why Store Snapshot?

Setting fee bisa berubah kapan saja, tapi donasi lama harus tetap akurat:

```
T0: Fee = 2.5%, mechanism = added_to_donor
    User donates Rp 100.000
    Stored: amount=100k, fee=2.5k, payment=102.5k, mosque=100k

T1: Owner changes fee to 5%

T2: User wants to see donation history
    Should show: payment=102.5k (not recalculated with new 5%)
```

---

## 🎯 Best Practices

### 1. Always Round Fee Amount

```php
// ✅ Good
$feeAmount = (int) round(($amount * $feePercentage) / 10000);

// ❌ Bad - floating point issues
$feeAmount = ($amount * $feePercentage) / 10000;
```

### 2. Store Snapshot in Database

```php
// ✅ Good - Store at creation time
$calculation = $feeService->calculateFee($amount);
$donation->fee_amount = $calculation['fee_amount'];
$donation->fee_mechanism = $feeSettings['fee_mechanism'];

// ❌ Bad - Don't recalculate later
// Settings might have changed
```

### 3. Use Integer for Money

```php
// ✅ Good - Store in smallest unit (Rupiah)
$amount = 100000; // Rp 100.000

// ❌ Bad - Float has precision issues
$amount = 100000.00;
```

### 4. Validate Inputs

```php
// ✅ Good
if ($amount <= 0) {
    throw new InvalidArgumentException('Amount must be positive');
}

if ($feePercentage < 0 || $feePercentage > 10000) {
    throw new InvalidArgumentException('Fee percentage out of range');
}
```

---

## 🧪 Test Cases

### Unit Test Matrix

| Amount | Fee % | Mechanism | Active | Expected Fee | Expected Payment | Expected Mosque |
|--------|-------|-----------|--------|--------------|------------------|-----------------|
| 100000 | 250   | added     | true   | 2500         | 102500           | 100000          |
| 100000 | 250   | deducted  | true   | 2500         | 100000           | 97500           |
| 100000 | 250   | added     | false  | 0            | 100000           | 100000          |
| 50000  | 500   | added     | true   | 2500         | 52500            | 50000           |
| 50000  | 500   | deducted  | true   | 2500         | 50000            | 47500           |
| 1000   | 250   | added     | true   | 25           | 1025             | 1000            |

---

## 🌐 API Response Format

### Calculate Donation Endpoint

**Request:**
```json
POST /api/v1/donations/calculate
{
  "amount": 100000,
  "payment_method": "QRIS"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "amount": 100000,
    "platform_fee": 2500,
    "payment_method_fee": 700,
    "total_payment": 103200,
    "mosque_receives": 100000,
    "fee_mechanism": "added_to_donor",
    "fee_mechanism_label": "Biaya ditanggung donatur",
    "breakdown": {
      "amount_formatted": "Rp 100.000",
      "platform_fee_formatted": "Rp 2.500",
      "payment_method_fee_formatted": "Rp 700",
      "total_payment_formatted": "Rp 103.200",
      "mosque_receives_formatted": "Rp 100.000"
    }
  }
}
```

---

## 📱 Mobile App UX

### Recommended Flow

**Step 1: Input Amount**
```
┌─────────────────────────────────┐
│  Masukkan Nominal Donasi        │
│                                 │
│  Rp [100.000]                   │
│                                 │
│  Kategori: [Infaq ▼]            │
└─────────────────────────────────┘
```

**Step 2: Show Breakdown (if added_to_donor)**
```
┌─────────────────────────────────┐
│  Rincian Donasi                 │
│                                 │
│  Nominal donasi    Rp 100.000   │
│  Biaya platform    Rp 2.500     │
│  Biaya payment     Rp 700       │
│  ─────────────────────────────  │
│  Total Pembayaran  Rp 103.200   │
│                                 │
│  ℹ️ Masjid akan menerima        │
│     Rp 100.000                  │
│                                 │
│  [Lanjutkan]                    │
└─────────────────────────────────┘
```

**Step 2: Show Breakdown (if deducted_from_donation)**
```
┌─────────────────────────────────┐
│  Rincian Donasi                 │
│                                 │
│  Total Pembayaran  Rp 100.000   │
│                                 │
│  ℹ️ Masjid akan menerima        │
│     Rp 97.500                   │
│     (Rp 100.000 - Rp 2.500 fee) │
│                                 │
│  [Lanjutkan]                    │
└─────────────────────────────────┘
```

---

## 🔍 Common Issues & Solutions

### Issue: Rounding Discrepancies

**Problem:** Fee calculation differs by 1 Rupiah between frontend and backend.

**Solution:** Always use same rounding strategy (round, not floor/ceil).

```php
// ✅ Consistent
$feeAmount = (int) round(($amount * $feePercentage) / 10000);
```

### Issue: Floating Point Precision

**Problem:** `100000 * 0.025 = 2499.9999999` instead of `2500`.

**Solution:** Use integer arithmetic with basis points.

```php
// ✅ Good - Integer math
$feeAmount = (int) round(($amount * 250) / 10000);

// ❌ Bad - Float math
$feeAmount = (int) round($amount * 0.025);
```

### Issue: Fee Calculation After Settings Change

**Problem:** Old donations show wrong fee when settings change.

**Solution:** Always store fee snapshot in donation record.

---

**Last Updated:** 8 Juni 2026
**Version:** 1.0
