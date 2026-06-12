# Day 9 Quick Start Guide

> Panduan cepat untuk menjalankan dan test fitur Platform Settings & Fee Configuration.

---

## 🚀 Quick Start

### 1. Ensure Database is Seeded

Pastikan platform settings sudah di-seed:

```bash
cd web
php artisan db:seed --class=PlatformSettingsSeeder
```

### 2. Ensure Super Admin Exists

Pastikan ada user dengan role `super-admin`:

```bash
php artisan db:seed --class=SuperAdminSeeder
```

### 3. Login as Owner

1. Buka browser: `http://localhost:8000/login`
2. Login dengan kredensial owner (super-admin)
3. Akses: `http://localhost:8000/owner/settings`

---

## 🧪 Manual Testing Checklist

### Test Case 1: View Settings Page

- [ ] Navigate ke `/owner/settings`
- [ ] Page load tanpa error
- [ ] Form menampilkan nilai default:
  - Fee Percentage: 250 (2.5%)
  - Fee Mechanism: Added to Donor
  - Fee Active: Yes
- [ ] Preview card menampilkan kalkulasi:
  - Sample: Rp 100.000
  - Fee: Rp 2.500
  - Payment: Rp 102.500
  - Mosque receives: Rp 100.000

### Test Case 2: Change Fee Percentage

- [ ] Input fee percentage: `500` (5%)
- [ ] Percentage display auto-update: "= 5.00%"
- [ ] Preview auto-update:
  - Fee: Rp 5.000
  - Payment: Rp 105.000
- [ ] Click "Simpan Pengaturan"
- [ ] Success message muncul
- [ ] Refresh page, nilai tetap 500

### Test Case 3: Change Fee Mechanism

- [ ] Select "Dipotong dari Donasi"
- [ ] Preview auto-update:
  - Fee: Rp 2.500
  - Payment: Rp 100.000
  - Mosque receives: Rp 97.500
- [ ] Mechanism info update: "Biaya dipotong dari donasi"
- [ ] Save and verify

### Test Case 4: Toggle Fee Active/Inactive

- [ ] Toggle OFF
- [ ] Label berubah: "Fee Nonaktif"
- [ ] Preview menampilkan fee = Rp 0
- [ ] Mosque receives = Rp 100.000 (full amount)
- [ ] Toggle ON
- [ ] Label berubah: "Fee Aktif"
- [ ] Preview kembali normal
- [ ] Save and verify

### Test Case 5: Validation

- [ ] Input fee percentage: `-10`
- [ ] Submit → error: "Persentase fee minimal 0"
- [ ] Input fee percentage: `15000`
- [ ] Submit → error: "Persentase fee maksimal 10000"
- [ ] Clear fee percentage
- [ ] Submit → error: "Persentase fee wajib diisi"
- [ ] Uncheck radio buttons manually (via browser console)
- [ ] Submit → error: "Mekanisme fee wajib dipilih"

### Test Case 6: Authorization

- [ ] Logout
- [ ] Login sebagai non-owner user (admin masjid)
- [ ] Try access `/owner/settings`
- [ ] Should be redirected or get 403 error

---

## 🔍 Database Verification

### Check Settings in Database

```sql
SELECT * FROM platform_settings WHERE `key` LIKE 'platform_fee%';
```

Expected output:

```
+----+---------------------------+-------+----------------------------------------------------+
| id | key                       | value | description                                        |
+----+---------------------------+-------+----------------------------------------------------+
|  1 | platform_fee_percentage   | 250   | Persentase fee platform dalam basis poin (250=2.5%)|
|  2 | platform_fee_mechanism    | added_to_donor | Mekanisme fee: added_to_donor atau ...   |
|  3 | platform_fee_active       | 1     | Status fee platform aktif (1) atau nonaktif (0)    |
+----+---------------------------+-------+----------------------------------------------------+
```

---

## 🧩 Integration Testing

### Test with Mock Donation

Create a test donation to verify fee calculation:

```php
use App\Services\PlatformSettingService;

Route::get('/test-fee', function () {
    $service = app(PlatformSettingService::class);
    
    $settings = $service->getFeeSettings();
    $calculation = $service->calculateFee(100000);
    
    return [
        'settings' => $settings,
        'calculation' => $calculation,
    ];
});
```

Visit: `http://localhost:8000/test-fee`

Expected output (if fee = 250, mechanism = added_to_donor, active = true):

```json
{
  "settings": {
    "fee_percentage": 250,
    "fee_mechanism": "added_to_donor",
    "fee_active": true
  },
  "calculation": {
    "fee_amount": 2500,
    "payment_amount": 102500,
    "mosque_receives": 100000
  }
}
```

---

## 🐛 Troubleshooting

### Issue: Settings page shows blank values

**Solution:**
```bash
php artisan db:seed --class=PlatformSettingsSeeder
php artisan cache:clear
```

### Issue: 403 Forbidden

**Solution:**
- Ensure logged in user has `super-admin` role
- Check: `$user->hasRole('super-admin')`
- Reseed: `php artisan db:seed --class=PermissionSeeder`

### Issue: Preview not updating

**Solution:**
- Check browser console for JavaScript errors
- Ensure jQuery is loaded
- Clear browser cache

### Issue: Cache not clearing

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## 📊 Unit Test

Run unit tests for PlatformSettingService:

```bash
cd web
php artisan test --filter=PlatformSettingServiceTest
```

Expected output:
```
PASS  Tests\Unit\Services\PlatformSettingServiceTest
✓ get fee settings returns array with required keys
✓ calculate fee with added to donor mechanism
✓ calculate fee with deducted from donation mechanism
✓ calculate fee when fee is inactive
✓ calculate fee with different percentage
✓ calculate fee rounds correctly
✓ get fee preview returns formatted values
✓ update fee settings persists values

Tests:    8 passed (8 assertions)
Duration: 0.15s
```

---

## 🎯 Feature Demo

### Recommended Demo Flow

1. **Show Default State**
   - Login as owner
   - Show settings page with default values
   - Explain basis points concept

2. **Demonstrate Real-time Preview**
   - Change fee percentage from 250 to 500
   - Show preview update instantly
   - Explain calculation

3. **Show Fee Mechanisms**
   - Switch between "Added to Donor" and "Deducted from Donation"
   - Highlight difference in preview
   - Explain impact to users and mosques

4. **Toggle Fee Status**
   - Turn off fee
   - Show preview: fee = 0
   - Explain use case (promo, special events)

5. **Save and Verify**
   - Save changes
   - Show success message
   - Refresh page to verify persistence

---

## 📚 Related Documentation

- [Day 9 Implementation Summary](./day-9-implementation-summary.md)
- [Service Layer README](../web/app/Services/README.md)
- [Database Design](./database-design.md)
- [API Contract](./api-contract.md)

---

## ✅ Acceptance Criteria

Feature is considered complete when:

- [x] Owner can view current fee settings
- [x] Owner can change fee percentage (0-10000 basis points)
- [x] Owner can choose fee mechanism (added/deducted)
- [x] Owner can toggle fee active/inactive
- [x] Real-time preview updates correctly
- [x] Changes are saved to database
- [x] Validation works for all fields
- [x] Only super-admin can access
- [x] UI is intuitive with helper text
- [x] No console errors
- [x] Responsive on mobile

---

## 🎉 Next Steps

After Day 9 is verified:

1. **Day 10:** Owner - Manage Owner Accounts & Reports
2. **Integration:** Use `PlatformSettingService` in donation flow (Day 22-23)
3. **Testing:** Write feature tests for full donation flow with fee

---

**Last Updated:** 8 Juni 2026
**Status:** ✅ Ready for Testing
