# TiwineBiz Backend API

A comprehensive multi-tenant retail management system built with Laravel 12, featuring offline-first architecture, multi-channel notifications, and advanced reporting capabilities.

## 🚀 Features

### 📊 Reports & Analytics
- **Sales Reports**: Summary, daily, weekly, monthly, comparison reports
- **Product Analytics**: Top-selling, slow-moving, performance tracking, category analytics
- **Inventory Management**: Valuation, movements, aging, turnover calculations
- **Dashboard**: Real-time overview of sales, inventory, and product insights
- **Export Functionality**: PDF and Excel exports for all reports

### 🧾 Receipt Generation
- **Bilingual Support**: English/Chichewa receipts
- **Professional PDFs**: Branded, EFD-compliant receipts
- **Multiple Formats**: View, download, print, email receipts
- **QR Code Integration**: For digital verification
- **Branch-Aware**: Access control based on user permissions

### 🔄 Offline Sync System
- **Push/Pull Synchronization**: Seamless data sync across devices
- **Conflict Resolution**: Three strategies (client_wins, server_wins, merge)
- **Queue-Based Processing**: Async sync with status tracking
- **Multi-Device Support**: Device ID tracking and management
- **Priority Processing**: Configurable sync priorities

### 🔔 Multi-Channel Notifications
- **Notification Types**:
  - Low Stock Alerts
  - Sale Completed
  - Payment Reminders
  - Subscription Expiring
- **Delivery Channels**: Database, Email, SMS
- **User Preferences**: Granular control per notification type and channel
- **SMS Providers**: Support for Twilio and Africa's Talking
- **Queued Delivery**: Async notification processing

### 🏢 Multi-Tenant Architecture
- **Shop Management**: Support for multiple shops per user
- **Branch System**: Multi-branch operations with role-based access
- **User Permissions**: Fine-grained access control
- **Data Isolation**: Tenant-specific data separation

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.4
- **Database**: MySQL (with SQLite for testing)
- **Authentication**: Laravel Sanctum
- **Testing**: Pest PHP
- **Code Quality**: Laravel Pint
- **PDF Generation**: DomPDF
- **Excel Export**: Maatwebsite Excel
- **Caching**: Redis support

## 📋 Requirements

- PHP >= 8.4
- Composer
- MySQL >= 8.0 or MariaDB >= 10.3
- Redis (optional, for caching)
- Node.js & NPM (for asset compilation)

## 🔧 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd TiwineBiz/Backend
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database

Update your `.env` file with database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tiwinebiz
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Migrations

```bash
php artisan migrate --seed
```

### 6. Configure Storage

```bash
php artisan storage:link
```

### 7. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## ⚙️ Configuration

### SMS Notifications

Configure your SMS provider in `.env`:

```env
# For Twilio
SMS_PROVIDER=twilio
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM=+1234567890

# For Africa's Talking
SMS_PROVIDER=africastalking
AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_api_key
AFRICASTALKING_FROM=TIWINEBIZ
```

For development, use log mode:

```env
SMS_PROVIDER=log
```

### Email Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tiwinebiz.com
MAIL_FROM_NAME="TiwineBiz"
```

### Cache Configuration

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 📚 API Documentation

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication

All endpoints require authentication using Laravel Sanctum:

```bash
Authorization: Bearer {your_token}
```

### Key Endpoints

#### Dashboard
- `GET /dashboard` - Main dashboard overview
- `GET /dashboard/sales` - Sales overview
- `GET /dashboard/inventory` - Inventory overview
- `GET /dashboard/products` - Product insights
- `GET /dashboard/quick-stats` - Quick statistics

#### Sales Reports
- `GET /reports/sales/summary` - Sales summary
- `GET /reports/sales/daily` - Daily sales
- `GET /reports/sales/weekly` - Weekly sales
- `GET /reports/sales/monthly` - Monthly sales
- `GET /reports/sales/export` - Export reports (PDF/Excel)

#### Receipts
- `GET /receipts/{sale}/view` - View receipt
- `GET /receipts/{sale}/download` - Download PDF
- `GET /receipts/{sale}/html` - HTML version
- `POST /receipts/{sale}/print` - Print receipt
- `POST /receipts/{sale}/email` - Email receipt

#### Sync
- `POST /sync/push` - Push changes to server
- `POST /sync/pull` - Pull changes from server
- `GET /sync/status` - Get sync status
- `GET /sync/conflicts` - List conflicts
- `POST /sync/conflicts/{id}/resolve` - Resolve conflict

#### Notifications
- `GET /notifications` - List notifications
- `GET /notifications/unread-count` - Get unread count
- `POST /notifications/{id}/read` - Mark as read
- `POST /notifications/read-all` - Mark all as read
- `GET /notifications/preferences` - Get preferences
- `PUT /notifications/preferences` - Update preferences

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
php artisan test tests/Feature/Api/V1/ReportControllerTest.php
```

### Run Tests with Coverage

```bash
php artisan test --coverage
```

### Current Test Stats

- **Total Tests**: 69
- **Total Assertions**: 178
- **Test Coverage**: All major features covered
- **Success Rate**: 100%

### Test Structure

```
tests/
├── Feature/
│   ├── Api/
│   │   └── V1/
│   │       ├── DashboardControllerTest.php (10 tests)
│   │       ├── ReceiptControllerTest.php (12 tests)
│   │       ├── SyncControllerTest.php (18 tests)
│   │       ├── NotificationControllerTest.php (15 tests)
│   │       └── ReportExportTest.php (14 tests)
└── Unit/
    └── (Unit tests as needed)
```

## 🏗️ Architecture

### Service Layer Pattern

Business logic is encapsulated in service classes:

```
app/Services/
├── Reports/
│   ├── BaseReportService.php
│   ├── SalesReportService.php
│   ├── ProductReportService.php
│   └── InventoryReportService.php
├── ReceiptService.php
└── SyncService.php
```

### Repository Pattern

Data access is handled through repositories for complex queries.

### Notification System

```
app/Notifications/
├── Channels/
│   └── SmsChannel.php
├── LowStockAlert.php
├── SaleCompleted.php
├── PaymentReminder.php
└── SubscriptionExpiring.php
```

### Multi-Tenancy

- Shop-level isolation
- Branch-level access control
- User-based permissions with roles

### Caching Strategy

- 30-minute cache for reports
- Cache keys include user ID and parameters
- Automatic cache invalidation on data changes

## 📁 Project Structure

```
TiwineBiz/Backend/
├── app/
│   ├── Exports/              # Excel export classes
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/V1/       # API controllers
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # Notification classes
│   ├── Services/             # Business logic services
│   └── Traits/               # Reusable traits
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── resources/
│   └── views/
│       ├── receipts/         # Receipt templates
│       └── reports/          # Report templates
├── routes/
│   └── api.php              # API routes
└── tests/                   # Test suite
```

## 🔐 Security

- **Authentication**: Laravel Sanctum token-based authentication
- **Authorization**: Policy-based authorization
- **Branch Access Control**: HasBranchScope trait
- **SQL Injection Prevention**: Eloquent ORM and query builder
- **XSS Protection**: Blade template escaping
- **CSRF Protection**: Built-in Laravel CSRF protection
- **Rate Limiting**: API rate limiting configured

## 🚦 Code Quality

### Laravel Pint

Format code using Laravel Pint:

```bash
vendor/bin/pint
```

Format only modified files:

```bash
vendor/bin/pint --dirty
```

### Static Analysis

```bash
./vendor/bin/phpstan analyse
```

## 📈 Performance

- **Database Query Optimization**: Eager loading to prevent N+1 queries
- **Caching**: Redis caching for frequently accessed data
- **Queue System**: Async processing for notifications and heavy operations
- **Index Optimization**: Proper database indexing

## 🔄 Deployment

### Production Checklist

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Run `php artisan view:cache`
6. Set up proper queue workers
7. Configure Redis for caching
8. Set up SSL certificates
9. Configure CORS settings
10. Set up backup system

### Queue Workers

Start queue workers for async processing:

```bash
php artisan queue:work --queue=default,notifications,sync
```

For production, use Supervisor to manage queue workers.

## 📝 API Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation successful"
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Use Laravel best practices
- Write tests for new features
- Update documentation

## 📄 License

This project is proprietary software. All rights reserved.

## 👥 Authors

- Development Team - TiwineBiz

## 📞 Support

For support, email support@tiwinebiz.com or open an issue in the repository.

## 🎯 Roadmap

### Completed Features ✅
- Reports & Analytics System
- Receipt Generation with Bilingual Support
- Offline Sync with Conflict Resolution
- Multi-Channel Notification System
- PDF/Excel Export Functionality

### Planned Features 🔮
- Mobile API optimization
- Real-time analytics dashboard
- Advanced inventory predictions
- Automated reordering system
- Multi-currency support enhancement
- Advanced reporting customization
- Integration with external accounting systems

---

Built with ❤️ using Laravel 12
