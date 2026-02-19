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

All protected endpoints require authentication using Laravel Sanctum:

```bash
Authorization: Bearer {your_token}
```

---

## Authentication Endpoints

### POST /auth/register
Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+265991234567",
  "password": "Password123",
  "password_confirmation": "Password123",
  "profile_photo_url": "https://example.com/photo.jpg",
  "preferred_language": "en",
  "timezone": "Africa/Blantyre"
}
```

**Success Response (201):**
```json
{
  "message": "Registration successful.",
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+265991234567",
    "profile_photo_url": "https://example.com/photo.jpg",
    "preferred_language": "en",
    "timezone": "Africa/Blantyre",
    "two_factor_enabled": false,
    "is_active": true,
    "last_login_at": null,
    "email_verified_at": null,
    "phone_verified_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "token": "plain-text-token"
}
```

**Error Responses:**
- `422` - Validation errors
- `500` - Server error

---

### POST /auth/login
Login with email or phone number.

**Request Body:**
```json
{
  "login": "john@example.com",
  "password": "Password123"
}
```

**Success Response (200):**
```json
{
  "message": "Login successful.",
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+265991234567",
    "profile_photo_url": "https://example.com/photo.jpg",
    "preferred_language": "en",
    "timezone": "Africa/Blantyre",
    "two_factor_enabled": false,
    "is_active": true,
    "last_login_at": "2024-01-01T12:00:00.000000Z",
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "phone_verified_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z"
  },
  "token": "plain-text-token"
}
```

**Error Responses:**
- `401` - Invalid credentials
- `403` - Account deactivated or locked
- `422` - Validation errors
- `500` - Server error

---

### POST /auth/logout
Logout the authenticated user (requires authentication).

**Request Body:** None

**Success Response (200):**
```json
{
  "message": "Logout successful."
}
```

**Error Responses:**
- `401` - Unauthenticated
- `500` - Server error

---

### GET /auth/user
Get the authenticated user's profile (requires authentication).

**Query Parameters:**
- `include` (optional) - Comma-separated relationships: `ownedShops,shops`

**Success Response (200):**
```json
{
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+265991234567",
    "profile_photo_url": "https://example.com/photo.jpg",
    "preferred_language": "en",
    "timezone": "Africa/Blantyre",
    "two_factor_enabled": false,
    "is_active": true,
    "last_login_at": "2024-01-01T12:00:00.000000Z",
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "phone_verified_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z",
    "owned_shops": [],
    "shops": []
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `500` - Server error

---

### POST /auth/forgot-password
Send password reset link to user's email.

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
  "message": "Password reset link sent to your email address."
}
```

**Error Responses:**
- `400` - Unable to send reset link
- `422` - Validation errors
- `500` - Server error

---

### POST /auth/reset-password
Reset user password with token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123",
  "token": "reset-token"
}
```

**Success Response (200):**
```json
{
  "message": "Password reset successful. Please login with your new password."
}
```

**Error Responses:**
- `400` - Invalid or expired token
- `422` - Validation errors
- `500` - Server error

---

### PUT /auth/change-password
Change authenticated user's password (requires authentication).

**Request Body:**
```json
{
  "current_password": "OldPassword123",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123"
}
```

**Success Response (200):**
```json
{
  "message": "Password changed successfully."
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors or incorrect current password
- `500` - Server error

---

### POST /auth/email/resend
Resend email verification link (requires authentication).

**Request Body:** None

**Success Response (200):**
```json
{
  "message": "Verification email sent successfully."
}
```

**Error Responses:**
- `400` - Email already verified
- `401` - Unauthenticated
- `500` - Server error

---

### GET /auth/email/verify/{id}/{hash}
Verify user's email address (public route with signed URL).

**Success Response (200):**
```json
{
  "message": "Email verified successfully.",
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+265991234567",
    "profile_photo_url": "https://example.com/photo.jpg",
    "preferred_language": "en",
    "timezone": "Africa/Blantyre",
    "two_factor_enabled": false,
    "is_active": true,
    "last_login_at": null,
    "email_verified_at": "2024-01-01T12:00:00.000000Z",
    "phone_verified_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z"
  }
}
```

**Error Responses:**
- `400` - Email already verified or invalid link
- `404` - User not found
- `500` - Server error

---

## Dashboard Endpoints

### GET /dashboard
Get comprehensive dashboard statistics (requires authentication).

**Query Parameters:**
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "today": {
      "summary": {
        "total_sales": 50,
        "total_revenue": 150000,
        "average_order_value": 3000,
        "total_tax": 22500
      },
      "sales": []
    },
    "week_comparison": {
      "comparison": {
        "current_period": {
          "total_sales": 350,
          "total_revenue": 1050000
        },
        "previous_period": {
          "total_sales": 300,
          "total_revenue": 900000
        },
        "growth": {
          "sales_percentage": 16.67,
          "revenue_percentage": 16.67
        }
      }
    },
    "month_comparison": {
      "comparison": {
        "current_period": {
          "total_sales": 1500,
          "total_revenue": 4500000
        },
        "previous_period": {
          "total_sales": 1200,
          "total_revenue": 3600000
        },
        "growth": {
          "sales_percentage": 25,
          "revenue_percentage": 25
        }
      }
    },
    "stock_alerts": {
      "critical": 5,
      "warning": 12,
      "total": 17
    },
    "top_products": [
      {
        "product_id": "uuid",
        "product_name": "Product A",
        "total_quantity_sold": 100,
        "total_revenue": 300000
      }
    ],
    "inventory_summary": {
      "total_products": 500,
      "total_quantity": 10000,
      "total_cost_value": 5000000,
      "total_selling_value": 7500000
    }
  },
  "generated_at": "2024-01-01T12:00:00.000000Z"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /dashboard/sales
Get sales overview statistics (requires authentication).

**Query Parameters:**
- `branch_id` (optional, uuid) - Filter by specific branch
- `period` (optional, string) - `today`, `week`, `month` (default: `today`)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current": {
      "summary": {
        "total_sales": 50,
        "total_revenue": 150000,
        "average_order_value": 3000,
        "total_tax": 22500
      },
      "sales": []
    },
    "comparison": {
      "current_period": {
        "total_sales": 50,
        "total_revenue": 150000
      },
      "previous_period": {
        "total_sales": 45,
        "total_revenue": 135000
      },
      "growth": {
        "sales_percentage": 11.11,
        "revenue_percentage": 11.11
      }
    }
  },
  "generated_at": "2024-01-01T12:00:00.000000Z"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /dashboard/inventory
Get inventory overview statistics (requires authentication).

**Query Parameters:**
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "valuation": {
      "summary": {
        "total_products": 500,
        "total_quantity": 10000,
        "total_cost_value": 5000000,
        "total_selling_value": 7500000,
        "potential_profit": 2500000
      }
    },
    "alerts": {
      "summary": {
        "critical": 5,
        "warning": 12,
        "total": 17
      }
    },
    "low_stock_products": [
      {
        "product_id": "uuid",
        "product_name": "Product A",
        "current_quantity": 5,
        "min_stock_level": 10,
        "status": "critical"
      }
    ]
  },
  "generated_at": "2024-01-01T12:00:00.000000Z"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /dashboard/products
Get product insights (requires authentication).

**Query Parameters:**
- `branch_id` (optional, uuid) - Filter by specific branch
- `days` (optional, integer, 1-90) - Number of days to analyze (default: 7)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "period": {
      "start_date": "2024-01-01",
      "end_date": "2024-01-07",
      "days": 7
    },
    "top_selling": [
      {
        "product_id": "uuid",
        "product_name": "Product A",
        "total_quantity_sold": 100,
        "total_revenue": 300000,
        "average_daily_sales": 14.29
      }
    ],
    "slow_moving": [
      {
        "product_id": "uuid",
        "product_name": "Product B",
        "current_quantity": 500,
        "total_quantity_sold": 5,
        "days_in_stock": 30
      }
    ],
    "category_performance": [
      {
        "category_id": "uuid",
        "category_name": "Electronics",
        "total_sales": 200,
        "total_revenue": 600000,
        "percentage": 40
      }
    ]
  },
  "generated_at": "2024-01-01T12:00:00.000000Z"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /dashboard/quick-stats
Get quick statistics for mobile app (requires authentication).

**Query Parameters:**
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "today_sales_count": 50,
    "today_revenue": 150000,
    "week_sales_count": 350,
    "week_revenue": 1050000,
    "critical_alerts": 5,
    "total_stock_value": 5000000
  },
  "generated_at": "2024-01-01T12:00:00.000000Z"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

## Sales Reports Endpoints

### GET /reports/sales/summary
Get sales summary report for a date range (requires authentication).

**Query Parameters:**
- `start_date` (optional, date) - Start date
- `end_date` (optional, date) - End date
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_sales": 500,
      "total_revenue": 1500000,
      "average_order_value": 3000,
      "total_tax": 225000,
      "total_discount": 50000
    },
    "sales": []
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/daily
Get daily sales report (requires authentication).

**Query Parameters:**
- `date` (optional, date) - Specific date (default: today)
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "date": "2024-01-01",
    "summary": {
      "total_sales": 50,
      "total_revenue": 150000,
      "average_order_value": 3000,
      "total_tax": 22500
    },
    "sales": []
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/weekly
Get weekly sales report (requires authentication).

**Query Parameters:**
- `week` (optional, date) - Any date within the week
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "week_start": "2024-01-01",
    "week_end": "2024-01-07",
    "summary": {
      "total_sales": 350,
      "total_revenue": 1050000,
      "average_order_value": 3000,
      "total_tax": 157500
    },
    "sales": []
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/monthly
Get monthly sales report (requires authentication).

**Query Parameters:**
- `month` (optional, integer, 1-12) - Month number
- `year` (optional, integer, 2000-2100) - Year
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "month": 1,
    "year": 2024,
    "summary": {
      "total_sales": 1500,
      "total_revenue": 4500000,
      "average_order_value": 3000,
      "total_tax": 675000
    },
    "sales": []
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/comparison
Get sales comparison report (requires authentication).

**Query Parameters:**
- `period` (optional, string) - `today`, `week`, `month` (default: `week`)
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "comparison": {
      "current_period": {
        "total_sales": 350,
        "total_revenue": 1050000
      },
      "previous_period": {
        "total_sales": 300,
        "total_revenue": 900000
      },
      "growth": {
        "sales_percentage": 16.67,
        "revenue_percentage": 16.67
      }
    }
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/hourly
Get hourly sales report (requires authentication).

**Query Parameters:**
- `date` (optional, date) - Specific date (default: today)
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "date": "2024-01-01",
    "hourly_sales": [
      {
        "hour": 9,
        "total_sales": 5,
        "total_revenue": 15000
      }
    ]
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/top-customers
Get top customers report (requires authentication).

**Query Parameters:**
- `start_date` (optional, date) - Start date
- `end_date` (optional, date) - End date
- `branch_id` (optional, uuid) - Filter by specific branch
- `limit` (optional, integer, 1-100) - Number of customers (default: 10)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "top_customers": [
      {
        "customer_id": "uuid",
        "customer_name": "John Doe",
        "total_purchases": 20,
        "total_spent": 60000
      }
    ]
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /reports/sales/export
Export sales report in PDF or Excel format (requires authentication).

**Query Parameters:**
- `format` (required, string) - `pdf`, `excel`
- `type` (required, string) - `summary`, `daily`, `weekly`, `monthly`
- `start_date` (optional, date) - For summary type
- `end_date` (optional, date) - For summary type
- `date` (optional, date) - For daily/weekly type
- `month` (optional, integer, 1-12) - For monthly type
- `year` (optional, integer, 2000-2100) - For monthly type
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response:**
- PDF: Returns PDF file download
- Excel: Returns Excel file download

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

## Receipt Endpoints

### GET /receipts/{sale}/view
View receipt as PDF in browser (requires authentication).

**Query Parameters:**
- `locale` (optional, string) - `en`, `ny` (default: `en`)

**Success Response:**
- Returns PDF file for viewing in browser

**Error Responses:**
- `401` - Unauthenticated
- `404` - Sale not found or no access
- `500` - Server error

---

### GET /receipts/{sale}/download
Download receipt as PDF file (requires authentication).

**Query Parameters:**
- `locale` (optional, string) - `en`, `ny` (default: `en`)

**Success Response:**
- Returns PDF file download

**Error Responses:**
- `401` - Unauthenticated
- `404` - Sale not found or no access
- `500` - Server error

---

### GET /receipts/{sale}/html
Get receipt as HTML (requires authentication).

**Query Parameters:**
- `locale` (optional, string) - `en`, `ny` (default: `en`)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "html": "<html>...</html>",
    "sale_number": "SALE-ABC123"
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Sale not found or no access
- `500` - Server error

---

### GET /receipts/{sale}/print
Get print-optimized HTML receipt (requires authentication).

**Query Parameters:**
- `locale` (optional, string) - `en`, `ny` (default: `en`)

**Success Response:**
- Returns HTML with `Content-Type: text/html` header

**Error Responses:**
- `401` - Unauthenticated
- `404` - Sale not found or no access
- `500` - Server error

---

### POST /receipts/{sale}/email
Email receipt to customer (requires authentication).

**Request Body:**
```json
{
  "email": "customer@example.com",
  "locale": "en"
}
```

**Success Response (202):**
```json
{
  "success": true,
  "message": "Email functionality will be available in the next release.",
  "data": {
    "email": "customer@example.com",
    "sale_number": "SALE-ABC123",
    "status": "pending"
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Sale not found or no access
- `422` - Validation errors
- `500` - Server error

---

## Sync Endpoints

### POST /sync/push
Push changes from client to server (requires authentication).

**Request Body:**
```json
{
  "device_id": "device-123",
  "changes": [
    {
      "entity_type": "sale",
      "entity_id": "uuid",
      "action": "create",
      "data": {},
      "timestamp": "2024-01-01T12:00:00.000000Z",
      "priority": 5
    }
  ]
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Sync push completed",
  "data": {
    "processed": 10,
    "failed": 0,
    "conflicts": 0
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### POST /sync/pull
Pull changes from server to client (requires authentication).

**Request Body:**
```json
{
  "last_sync_timestamp": "2024-01-01T00:00:00.000000Z",
  "entity_types": ["sale", "product", "customer"]
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Sync pull completed",
  "data": {
    "sales": [],
    "products": [],
    "customers": [],
    "server_timestamp": "2024-01-01T12:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /sync/status
Get sync status for current user's shop (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "last_sync_at": "2024-01-01T12:00:00.000000Z",
    "pending_items": 5,
    "failed_items": 0,
    "conflict_items": 1,
    "total_synced": 1000
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - No shop found for user
- `500` - Server error

---

### GET /sync/pending
Get pending sync items (requires authentication).

**Query Parameters:**
- `limit` (optional, integer, 1-100) - Number of items (default: 50)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "entity_type": "sale",
      "entity_id": "uuid",
      "action": "create",
      "status": "pending",
      "priority": 5,
      "created_at": "2024-01-01T12:00:00.000000Z"
    }
  ]
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - No shop found for user
- `422` - Validation errors
- `500` - Server error

---

### GET /sync/conflicts
Get conflicted sync items (requires authentication).

**Query Parameters:**
- `limit` (optional, integer, 1-100) - Number of items (default: 50)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "entity_type": "sale",
      "entity_id": "uuid",
      "action": "update",
      "status": "conflict",
      "client_data": {},
      "server_data": {},
      "created_at": "2024-01-01T12:00:00.000000Z"
    }
  ]
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - No shop found for user
- `422` - Validation errors
- `500` - Server error

---

### POST /sync/conflicts/{queueItem}/resolve
Resolve a sync conflict (requires authentication).

**Request Body:**
```json
{
  "resolution": "client_wins",
  "merged_data": {}
}
```

**Resolution Options:**
- `client_wins` - Use client data
- `server_wins` - Use server data
- `merge` - Use provided merged data

**Success Response (200):**
```json
{
  "success": true,
  "message": "Conflict resolved successfully",
  "data": {
    "queue_item_id": "uuid",
    "resolution": "client_wins",
    "resolved_at": "2024-01-01T12:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Queue item not found
- `422` - Validation errors
- `500` - Server error

---

### POST /sync/{queueItem}/retry
Retry a failed sync item (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item queued for retry",
  "data": {
    "id": "uuid",
    "status": "pending",
    "error_message": null
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Queue item not found
- `400` - Item cannot be retried
- `500` - Server error

---

### DELETE /sync/{queueItem}
Delete a sync queue item (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "message": "Queue item deleted"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Queue item not found
- `500` - Server error

---

### GET /sync/history
Get sync history (requires authentication).

**Query Parameters:**
- `limit` (optional, integer, 1-100) - Number of items (default: 50)
- `status` (optional, string) - `completed`, `failed`, `conflict`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "entity_type": "sale",
      "entity_id": "uuid",
      "action": "create",
      "status": "completed",
      "created_at": "2024-01-01T12:00:00.000000Z",
      "completed_at": "2024-01-01T12:00:05.000000Z"
    }
  ]
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - No shop found for user
- `422` - Validation errors
- `500` - Server error

---

## Notification Endpoints

### GET /notifications
Get user notifications (requires authentication).

**Query Parameters:**
- `limit` (optional, integer, 1-100) - Number of notifications (default: 50)
- `unread_only` (optional, boolean) - Filter unread only

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "low_stock",
      "notifiable_id": "uuid",
      "notifiable_type": "App\\Models\\User",
      "data": {
        "message": "Product A is running low on stock",
        "product_id": "uuid",
        "product_name": "Product A"
      },
      "read_at": null,
      "created_at": "2024-01-01T12:00:00.000000Z"
    }
  ]
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

### GET /notifications/unread-count
Get unread notification count (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "count": 5
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `500` - Server error

---

### POST /notifications/{id}/read
Mark a notification as read (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "message": "Notification marked as read",
  "data": {
    "id": "uuid",
    "read_at": "2024-01-01T12:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Notification not found
- `500` - Server error

---

### POST /notifications/read-all
Mark all notifications as read (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `500` - Server error

---

### DELETE /notifications/{id}
Delete a notification (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

**Error Responses:**
- `401` - Unauthenticated
- `404` - Notification not found
- `500` - Server error

---

### GET /notifications/preferences
Get user notification preferences (requires authentication).

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "low_stock": [
      {
        "id": "uuid",
        "user_id": "uuid",
        "notification_type": "low_stock",
        "channel": "database",
        "enabled": true
      },
      {
        "id": "uuid",
        "user_id": "uuid",
        "notification_type": "low_stock",
        "channel": "mail",
        "enabled": false
      }
    ]
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `500` - Server error

---

### PUT /notifications/preferences
Update notification preferences (requires authentication).

**Request Body:**
```json
{
  "preferences": [
    {
      "notification_type": "low_stock",
      "channel": "database",
      "enabled": true
    },
    {
      "notification_type": "sale_completed",
      "channel": "mail",
      "enabled": true
    }
  ]
}
```

**Notification Types:**
- `low_stock` - Low stock alerts
- `sale_completed` - Sale completed notifications
- `payment_reminder` - Payment reminders
- `subscription_expiring` - Subscription expiring alerts
- `system_announcement` - System announcements

**Channels:**
- `database` - In-app notifications
- `mail` - Email notifications
- `sms` - SMS notifications
- `push` - Push notifications

**Success Response (200):**
```json
{
  "success": true,
  "message": "Notification preferences updated",
  "data": [
    {
      "id": "uuid",
      "user_id": "uuid",
      "notification_type": "low_stock",
      "channel": "database",
      "enabled": true
    }
  ]
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

## Customer Endpoints

### GET /customers
List customers with pagination (requires authentication).

**Query Parameters:**
- `page` (optional, integer) - Page number for pagination (default: 1)
- `per_page` (optional, integer, 1-100) - Number of items per page (default: 15)
- `search` (optional, string) - Search by name, phone, or email
- `branch_id` (optional, uuid) - Filter by specific branch

**Success Response (200):**
```json
{
  "data": [
    {
      "id": "string",
      "shop_id": "string",
      "customer_number": "string",
      "name": "string",
      "phone": "string",
      "email": "string",
      "whatsapp_number": "string",
      "physical_address": "string",
      "city": "string",
      "district": "string",
      "credit_limit": "string",
      "current_balance": "string",
      "total_spent": "string",
      "total_credit_issued": "string",
      "total_credit_collected": "string",
      "trust_level": "string",
      "payment_behavior_score": 0,
      "purchase_count": 0,
      "last_purchase_date": "string",
      "average_purchase_value": "string",
      "preferred_language": "string",
      "preferred_contact_method": "string",
      "notes": "string",
      "tags": [
        null
      ],
      "is_active": true,
      "blocked_at": "string",
      "block_reason": "string",
      "created_at": "string",
      "updated_at": "string",
      "shop": {
        "id": "string",
        "owner_id": "string",
        "name": "string",
        "business_type": "string",
        "legal_name": "string",
        "registration_number": "string",
        "tpin": "string",
        "vrn": "string",
        "is_vat_registered": true,
        "phone": "string",
        "email": "string",
        "website": "string",
        "address": "string",
        "city": "string",
        "district": "string",
        "country": "string",
        "latitude": "string",
        "longitude": "string",
        "logo_url": "string",
        "primary_color": "string",
        "default_currency": "string",
        "fiscal_year_start_month": 0,
        "subscription_tier": "string",
        "subscription_status": "string",
        "subscription_started_at": "string",
        "subscription_expires_at": "string",
        "trial_ends_at": "string",
        "features": [
          null
        ],
        "limits": [
          null
        ],
        "settings": [
          null
        ],
        "is_active": true,
        "deactivated_at": "string",
        "deactivation_reason": "string",
        "created_at": "string",
        "updated_at": "string",
        "owner": {
          "id": "string",
          "name": "string",
          "email": "string",
          "phone": "string",
          "profile_photo_url": "string",
          "preferred_language": "string",
          "timezone": "string",
          "two_factor_enabled": true,
          "is_active": true,
          "last_login_at": "string",
          "email_verified_at": "string",
          "phone_verified_at": "string",
          "created_at": "string",
          "updated_at": "string",
          "owned_shops": [
            {}
          ],
          "shops": [
            {}
          ]
        },
        "users": [
          {
            "id": "string",
            "name": "string",
            "email": "string",
            "phone": "string",
            "profile_photo_url": "string",
            "preferred_language": "string",
            "timezone": "string",
            "two_factor_enabled": true,
            "is_active": true,
            "last_login_at": "string",
            "email_verified_at": "string",
            "phone_verified_at": "string",
            "created_at": "string",
            "updated_at": "string",
            "owned_shops": [
              {}
            ],
            "shops": [
              {}
            ]
          }
        ]
      },
      "created_by": {
        "id": "string",
        "name": "string",
        "email": "string",
        "phone": "string",
        "profile_photo_url": "string",
        "preferred_language": "string",
        "timezone": "string",
        "two_factor_enabled": true,
        "is_active": true,
        "last_login_at": "string",
        "email_verified_at": "string",
        "phone_verified_at": "string",
        "created_at": "string",
        "updated_at": "string",
        "owned_shops": [
          {
            "id": "string",
            "owner_id": "string",
            "name": "string",
            "business_type": "string",
            "legal_name": "string",
            "registration_number": "string",
            "tpin": "string",
            "vrn": "string",
            "is_vat_registered": true,
            "phone": "string",
            "email": "string",
            "website": "string",
            "address": "string",
            "city": "string",
            "district": "string",
            "country": "string",
            "latitude": "string",
            "longitude": "string",
            "logo_url": "string",
            "primary_color": "string",
            "default_currency": "string",
            "fiscal_year_start_month": 0,
            "subscription_tier": "string",
            "subscription_status": "string",
            "subscription_started_at": "string",
            "subscription_expires_at": "string",
            "trial_ends_at": "string",
            "features": [
              null
            ],
            "limits": [
              null
            ],
            "settings": [
              null
            ],
            "is_active": true,
            "deactivated_at": "string",
            "deactivation_reason": "string",
            "created_at": "string",
            "updated_at": "string",
            "owner": {},
            "users": [
              {}
            ]
          }
        ],
        "shops": [
          {
            "id": "string",
            "owner_id": "string",
            "name": "string",
            "business_type": "string",
            "legal_name": "string",
            "registration_number": "string",
            "tpin": "string",
            "vrn": "string",
            "is_vat_registered": true,
            "phone": "string",
            "email": "string",
            "website": "string",
            "address": "string",
            "city": "string",
            "district": "string",
            "country": "string",
            "latitude": "string",
            "longitude": "string",
            "logo_url": "string",
            "primary_color": "string",
            "default_currency": "string",
            "fiscal_year_start_month": 0,
            "subscription_tier": "string",
            "subscription_status": "string",
            "subscription_started_at": "string",
            "subscription_expires_at": "string",
            "trial_ends_at": "string",
            "features": [
              null
            ],
            "limits": [
              null
            ],
            "settings": [
              null
            ],
            "is_active": true,
            "deactivated_at": "string",
            "deactivation_reason": "string",
            "created_at": "string",
            "updated_at": "string",
            "owner": {},
            "users": [
              {}
            ]
          }
        ]
      }
    }
  ],
  "meta": {
    "current_page": 0,
    "last_page": 0,
    "per_page": 0,
    "total": 0
  }
}
```

**Error Responses:**
- `401` - Unauthenticated
- `422` - Validation errors
- `500` - Server error

---

## Additional API Resources

### Products
- `GET /products` - List products with pagination
- `POST /products` - Create a new product
- `GET /products/{id}` - Get product details
- `PUT /products/{id}` - Update product
- `DELETE /products/{id}` - Delete product (soft delete)
- `POST /products/import` - Import products from Excel/CSV
- `POST /products/{id}/images` - Upload product image
- `DELETE /products/{id}/images/{imageIndex}` - Delete product image
- `POST /products/{id}/adjust-stock` - Adjust product stock
- `POST /products/{id}/transfer-stock` - Transfer stock between branches

### Sales
- `GET /sales` - List sales with pagination
- `POST /sales` - Create a new sale
- `GET /sales/{id}` - Get sale details
- `PUT /sales/{id}` - Update sale (limited fields)
- `DELETE /sales/{id}` - Cancel sale
- `POST /sales/{id}/refund` - Process refund
- `POST /sales/{id}/fiscalize` - Fiscalize sale with MRA EIS

### Customers
- `GET /customers` - List customers
- `POST /customers` - Create customer
- `GET /customers/{id}` - Get customer details
- `PUT /customers/{id}` - Update customer
- `DELETE /customers/{id}` - Delete customer

### Suppliers
- `GET /suppliers` - List suppliers
- `POST /suppliers` - Create supplier
- `GET /suppliers/{id}` - Get supplier details
- `PUT /suppliers/{id}` - Update supplier
- `DELETE /suppliers/{id}` - Delete supplier

### Categories
- `GET /categories` - List categories
- `POST /categories` - Create category
- `GET /categories/{id}` - Get category details
- `PUT /categories/{id}` - Update category
- `DELETE /categories/{id}` - Delete category

### Shops
- `GET /shops` - List shops
- `POST /shops` - Create shop
- `GET /shops/{id}` - Get shop details
- `PUT /shops/{id}` - Update shop
- `DELETE /shops/{id}` - Delete shop

### Branches
- `GET /branches` - List branches
- `POST /branches` - Create branch
- `GET /branches/{id}` - Get branch details
- `PUT /branches/{id}` - Update branch
- `DELETE /branches/{id}` - Delete branch
- `POST /branches/{branch}/users` - Assign user to branch
- `DELETE /branches/{branch}/users` - Remove user from branch
- `GET /branches/{branch}/users` - List branch users

### Roles
- `GET /roles` - List roles
- `POST /roles` - Create role
- `GET /roles/{id}` - Get role details
- `PUT /roles/{id}` - Update role
- `DELETE /roles/{id}` - Delete role

### Payments
- `GET /payments` - List payments
- `POST /payments` - Create payment
- `GET /payments/{id}` - Get payment details

### Credits
- `GET /credits` - List credits
- `POST /credits` - Create credit
- `GET /credits/{id}` - Get credit details
- `PUT /credits/{id}` - Update credit
- `DELETE /credits/{id}` - Delete credit

### Stock Movements
- `GET /stock-movements` - List stock movements
- `POST /stock-movements` - Create stock movement
- `GET /stock-movements/{id}` - Get stock movement details

### Purchase Orders
- `GET /purchase-orders` - List purchase orders
- `POST /purchase-orders` - Create purchase order
- `GET /purchase-orders/{id}` - Get purchase order details
- `PUT /purchase-orders/{id}` - Update purchase order
- `DELETE /purchase-orders/{id}` - Delete purchase order

### Product Batches
- `GET /product-batches` - List product batches
- `POST /product-batches` - Create product batch
- `GET /product-batches/{id}` - Get product batch details
- `PUT /product-batches/{id}` - Update product batch
- `DELETE /product-batches/{id}` - Delete product batch

### Exchange Rates
- `GET /exchange-rates/latest` - Get latest exchange rate
- `GET /exchange-rates` - List exchange rates
- `POST /exchange-rates` - Create exchange rate
- `GET /exchange-rates/{id}` - Get exchange rate details
- `DELETE /exchange-rates/{id}` - Delete exchange rate

### Mobile Money Transactions
- `GET /mobile-money-transactions` - List transactions
- `POST /mobile-money-transactions` - Create transaction
- `GET /mobile-money-transactions/{id}` - Get transaction details

### EFD Transactions
- `GET /efd-transactions` - List EFD transactions
- `POST /efd-transactions` - Create EFD transaction
- `GET /efd-transactions/{id}` - Get EFD transaction details

### Subscriptions
- `GET /subscriptions` - List subscriptions
- `POST /subscriptions` - Create subscription
- `GET /subscriptions/{id}` - Get subscription details
- `PUT /subscriptions/{id}` - Update subscription
- `DELETE /subscriptions/{id}` - Delete subscription

### Subscription Payments
- `GET /subscription-payments` - List subscription payments
- `POST /subscription-payments` - Create subscription payment
- `GET /subscription-payments/{id}` - Get subscription payment details

### Profile
- `GET /profile` - Get user profile
- `PUT /profile` - Update user profile
- `POST /profile/photo` - Upload profile photo
- `DELETE /profile/photo` - Delete profile photo

### Shop Invitations
- `POST /shop-invitations/send` - Send shop invitation
- `POST /shop-invitations/{token}/accept` - Accept invitation
- `POST /shop-invitations/{token}/decline` - Decline invitation
- `GET /shop-invitations/pending` - Get pending invitations
- `DELETE /shop-invitations/{shopId}/{userId}` - Cancel invitation

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

- Joseph Mojoo

## 📞 Support

For support, email mojoojoseph@gmail.com or open an issue in the repository.

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
