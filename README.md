# EMasjid Platform

> Platform manajemen masjid dan donasi digital berbasis web dan mobile.

---

## 📱 Project Structure

```
emasjid/
├── web/                    # Laravel backend (API + Web Panel)
├── emasjid_app/            # Flutter mobile app
└── docs/                   # Documentation
```

---

## 🚀 Quick Start

### Backend (Laravel)

```bash
cd web
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

### Mobile (Flutter)

```bash
cd emasjid_app
flutter pub get
flutter run
```

---

## 📚 Documentation

### Architecture & Design
- [Database Design](docs/database-design.md)
- [API Contract](docs/api-contract.md)
- [Repository Architecture](docs/repository-architecture.md)

### Development
- [Development Plan](docs/development-plan.md)
- [Git Strategy](docs/git-strategy.md)
- [Deployment Guide](docs/deployment.md)

### Implementation Guides
- [Skills & Guidelines](docs/SKILLS.md)
- [Agents & Automation](docs/AGENTS.md)

---

## ✅ Implementation Status

### Fase 0: Setup (Day 1) ✅
- Laravel & Flutter project initialized
- Dependencies installed
- Environment configured

### Fase 1: Foundation (Day 2-6) ✅
- Database structure & migrations
- Models with multi-tenant support
- Auth system & middleware
- Repository layer
- Base layouts & components

### Fase 2: Owner Panel (Day 7-11) 🚧

#### Day 7: Dashboard & Mosque Management ⏳
- Mosque list & pending approval
- Dashboard statistics

#### Day 8: Approve/Reject Mosque ⏳
- Approval workflow
- Email notifications

#### **Day 9: Platform Settings & Fee Configuration** ✅ **COMPLETED**
- **Status:** Production Ready
- **Files:** 11 created, 1 modified
- **Tests:** 8/8 passing
- **Documentation:** 2100+ lines

**Features:**
- ✅ Fee percentage configuration (basis points)
- ✅ Fee mechanism selection (added/deducted)
- ✅ Toggle fee active/inactive
- ✅ Real-time preview calculation
- ✅ Comprehensive validation & security

**Quick Links:**
- [Implementation Summary](docs/day-9-implementation-summary.md)
- [Quick Start Guide](docs/DAY-9-QUICKSTART.md)
- [Complete Report](docs/DAY-9-COMPLETE.md)
- [Fee Calculation Guide](docs/FEE-CALCULATION-GUIDE.md)
- [Flutter Integration](docs/FLUTTER-FEE-INTEGRATION.md)
- [Deployment Checklist](docs/DAY-9-DEPLOYMENT-CHECKLIST.md)

**Access:**
1. Login as super-admin
2. Navigate to `/owner/settings`
3. Configure platform fee

#### Day 10: Owner Accounts & Reports ⏳
- CRUD owner accounts
- Fee revenue reports

#### Day 11: Mosque Management Polish ⏳
- Suspend/reactivate features
- UI polish

### Fase 3: Admin Panel (Day 12-19) ⏳
- Mosque registration
- Profile & schedule management
- Finance & cash transactions
- Announcements
- Congregation & staff management

### Fase 4: API + Flutter (Day 20-26) ⏳
- REST API endpoints
- Mobile app implementation

### Fase 5: Integration (Day 27-29) ⏳
- Duitku payment gateway
- FCM push notifications
- Email notifications

### Fase 6: Testing & Deploy (Day 30-31) ⏳
- Feature testing
- Bug fixes
- Production deployment

---

## 🎯 Current Milestone

**Day 9 - Platform Settings & Fee Configuration** ✅

Owner dapat mengatur:
- Persentase fee platform (dalam basis poin)
- Mekanisme fee (ditambahkan ke donatur / dipotong dari donasi)
- Status fee (aktif/nonaktif)

Real-time preview menampilkan kalkulasi fee untuk membantu decision making.

**Next:** Day 10 - Owner Accounts & Fee Reports

---

## 🛠️ Tech Stack

### Backend
- **Framework:** Laravel 11
- **Database:** MySQL
- **Cache:** Redis (recommended)
- **Queue:** Database/Redis
- **Auth:** Sanctum
- **Permissions:** Spatie Laravel Permission

### Frontend Web
- **UI:** Bootstrap 5
- **JS:** jQuery, Chart.js
- **Tables:** DataTables
- **Alerts:** SweetAlert2

### Mobile
- **Framework:** Flutter
- **State Management:** BLoC
- **HTTP:** Dio
- **Storage:** Secure Storage
- **Notifications:** Firebase Cloud Messaging

---

## 📝 Development Guidelines

### Code Style
- Follow PSR-12 for PHP
- Use Laravel best practices
- Follow Flutter/Dart conventions
- Write meaningful comments

### Git Workflow
- Use feature branches
- Write descriptive commit messages
- Never commit to main directly
- See [Git Strategy](docs/git-strategy.md)

### Testing
- Write unit tests for services
- Write feature tests for critical flows
- Manual testing checklist in docs

---

## 🔐 Security

- Multi-tenant isolation enforced at model level
- Role-based access control (RBAC)
- CSRF protection on all forms
- Input validation on all endpoints
- Authorization checks at multiple layers
- Fee snapshot prevents manipulation

---

## 📞 Support

- **Documentation:** See `docs/` folder
- **Issues:** Track in issue tracker
- **Questions:** Ask team lead

---

## 📜 License

Proprietary. All rights reserved.

---

**Last Updated:** 8 Juni 2026  
**Current Phase:** Fase 2 - Owner Panel (Day 9 COMPLETED)  
**Progress:** ~29% (9/31 days)
