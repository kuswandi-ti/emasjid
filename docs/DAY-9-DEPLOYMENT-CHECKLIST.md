# Day 9 - Deployment Checklist

> Checklist untuk deploy fitur Platform Settings & Fee Configuration ke environment.

---

## 📋 Pre-Deployment Checklist

### Environment Setup

- [ ] **PHP Dependencies**
  ```bash
  cd web
  composer install --no-dev --optimize-autoloader
  ```

- [ ] **JavaScript Dependencies**
  ```bash
  cd web
  npm install
  npm run build
  ```

- [ ] **Environment File**
  ```bash
  cp .env.example .env
  # Edit .env with production values
  php artisan key:generate
  ```

- [ ] **Database Configuration**
  - [ ] Database created
  - [ ] Credentials in .env correct
  - [ ] Connection tested

- [ ] **Cache Configuration**
  - [ ] Redis/Memcached installed (recommended)
  - [ ] Cache driver in .env set
  - [ ] Cache working

---

## 🗄️ Database Migration & Seeding

### Run Migrations

```bash
cd web
php artisan migrate --force
```

Expected output:
```
✔ 0001_01_01_000000_create_users_table
✔ 0001_01_01_000001_create_cache_table
✔ ...
✔ 2026_06_08_000003_create_platform_settings_table
...
```

### Run Seeders

```bash
# Permission & Roles
php artisan db:seed --class=PermissionSeeder

# Platform Settings (REQUIRED for Day 9)
php artisan db:seed --class=PlatformSettingsSeeder

# Super Admin User (REQUIRED for Day 9)
php artisan db:seed --class=SuperAdminSeeder
```

### Verify Seeding

```bash
php artisan tinker
```

```php
// Check platform settings
\App\Models\PlatformSetting::all();
// Should return 3 settings: platform_fee_*

// Check super admin exists
\App\Models\User::role('super-admin')->first();
// Should return super admin user

exit
```

---

## 🔐 Permission Setup

### Verify Permissions Exist

```sql
SELECT * FROM permissions WHERE name LIKE '%settings%';
```

If not exists, create manually or via seeder:

```php
// In PermissionSeeder or tinker
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'settings.view']);
Permission::create(['name' => 'settings.update']);
```

### Assign to Super Admin Role

```sql
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p, roles r
WHERE p.name IN ('settings.view', 'settings.update')
  AND r.name = 'super-admin';
```

---

## 🌐 Web Server Configuration

### Apache (.htaccess already included)

Make sure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx (add to server block)

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## 🔧 Laravel Configuration

### Optimize

```bash
cd web

# Config cache
php artisan config:cache

# Route cache
php artisan route:cache

# View cache
php artisan view:cache

# Event cache (if using)
php artisan event:cache
```

### Storage Link

```bash
php artisan storage:link
```

### Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🧪 Testing Before Go-Live

### 1. Access Check

- [ ] Visit: `http://yourdomain.com/owner/settings`
- [ ] Should redirect to login if not authenticated
- [ ] Login with super-admin credentials
- [ ] Settings page loads without errors

### 2. Functionality Check

- [ ] Page displays current settings
- [ ] Change fee percentage → preview updates
- [ ] Change mechanism → preview updates
- [ ] Toggle active → preview updates
- [ ] Save changes → success message
- [ ] Refresh page → changes persisted

### 3. Validation Check

- [ ] Input invalid percentage (negative) → error
- [ ] Input percentage > 10000 → error
- [ ] Submit without selecting mechanism → error
- [ ] All errors display correctly

### 4. Authorization Check

- [ ] Logout from super-admin
- [ ] Login as regular admin masjid
- [ ] Try access `/owner/settings` → 403 or redirect
- [ ] Should not be accessible

### 5. Database Check

```sql
-- Settings should be updated
SELECT * FROM platform_settings 
WHERE `key` IN ('platform_fee_percentage', 'platform_fee_mechanism', 'platform_fee_active');
```

### 6. Cache Check

```bash
# Clear cache
php artisan cache:clear

# Access settings page
# Visit: /owner/settings

# Check cache
php artisan tinker
>>> Cache::get('platform_settings_all');
>>> Cache::get('platform_settings_platform_fee_percentage');
```

Should show cached values.

### 7. Browser Console Check

- [ ] Open browser DevTools (F12)
- [ ] Go to Console tab
- [ ] Load settings page
- [ ] Should see NO JavaScript errors
- [ ] Change form values
- [ ] Preview should update smoothly

---

## 📊 Monitoring Setup

### Application Logs

```bash
tail -f storage/logs/laravel.log
```

Monitor for:
- Errors when accessing settings
- Validation errors
- Authorization failures
- Cache issues

### Web Server Logs

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

### Database Queries (if slow)

```sql
-- Enable query log
SET GLOBAL general_log = 'ON';
SET GLOBAL general_log_file = '/var/log/mysql/query.log';

-- Access settings page

-- Check log
tail -f /var/log/mysql/query.log
```

---

## 🚨 Troubleshooting

### Issue: 500 Internal Server Error

**Check:**
```bash
tail -f storage/logs/laravel.log
```

**Common causes:**
- Missing vendor dependencies → `composer install`
- Permission issues → `chmod -R 755 storage`
- .env misconfiguration → verify database, cache
- Cache issues → `php artisan cache:clear`

### Issue: 404 Not Found on /owner/settings

**Check:**
```bash
php artisan route:list --path=owner
```

**Fix:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Page blank or layout broken

**Check:**
```bash
npm run build
php artisan view:clear
```

**Fix:**
```bash
# Rebuild assets
npm install
npm run build

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Unauthorized (403)

**Check:**
```php
php artisan tinker
>>> $user = \App\Models\User::find(1);
>>> $user->hasRole('super-admin');
```

**Fix:**
```bash
php artisan db:seed --class=PermissionSeeder
```

```php
php artisan tinker
>>> $user = \App\Models\User::find(1);
>>> $user->assignRole('super-admin');
```

### Issue: Settings not saving

**Check:**
```bash
tail -f storage/logs/laravel.log
```

**Common causes:**
- CSRF token mismatch → Clear cookies
- Validation failing → Check form values
- Database connection → Check .env
- Cache lock → `php artisan cache:clear`

### Issue: Preview not updating

**Check browser console (F12):**

**Common causes:**
- JavaScript error → Fix syntax
- jQuery not loaded → Check layout
- Cache → Clear browser cache (Ctrl+F5)

---

## 🔄 Rollback Plan

If issues occur after deployment:

### 1. Revert Code

```bash
git log --oneline
git revert <commit-hash>
git push
```

### 2. Restore Database

```sql
-- If you have backup
mysql -u root -p emasjid < backup_before_day9.sql
```

### 3. Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 4. Restore Previous State

```bash
git checkout <previous-commit>
composer install
npm install
npm run build
php artisan migrate
```

---

## ✅ Post-Deployment Verification

### 1. Smoke Test (5 minutes)

- [ ] Login as owner
- [ ] Access settings page
- [ ] Change one value
- [ ] Save successfully
- [ ] Logout

### 2. Full Feature Test (15 minutes)

- [ ] Run through all test cases in DAY-9-QUICKSTART.md
- [ ] Verify all functionality works
- [ ] Check all validation errors
- [ ] Test authorization

### 3. Performance Check

- [ ] Page load < 500ms
- [ ] No N+1 queries
- [ ] Cache working (check cache hit logs)

### 4. Security Check

- [ ] Non-owner cannot access
- [ ] CSRF protection working
- [ ] Validation preventing invalid input

---

## 📧 Notification

After successful deployment, notify:

- [x] **Project Manager** - Feature deployed
- [x] **QA Team** - Ready for testing
- [x] **Owner (Stakeholder)** - Can start using
- [x] **Team** - Update development plan

---

## 📝 Documentation Update

- [ ] Update README.md with new feature
- [ ] Update API documentation (if exposed)
- [ ] Update user manual (for owner)
- [ ] Update change log

---

## 🎉 Success Criteria

Deployment considered successful when:

- [x] All checks passing
- [x] Owner can access and use settings
- [x] No errors in logs
- [x] Performance acceptable
- [x] Security verified
- [x] Stakeholder approval

---

## 📞 Support

If issues arise:

**Developer Contact:** [Your contact]  
**Emergency Rollback:** Use rollback plan above  
**Documentation:** See DAY-9-QUICKSTART.md for details  

---

**Checklist Prepared By:** AI Assistant (Kiro)  
**Date:** 8 Juni 2026  
**Version:** 1.0
