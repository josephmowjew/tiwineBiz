# TiwineBiz Implementation Plan
## Comprehensive Technical Development Roadmap

**Document Version:** 1.0  
**Last Updated:** November 9, 2024  
**Project Duration:** 18 months  
**Target Launch:** MVP in 4 months

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Technical Architecture](#technical-architecture)
3. [Development Environment Setup](#development-environment-setup)
4. [Database Schema](#database-schema)
5. [API Architecture](#api-architecture)
6. [Phase 1: MVP Development (Months 1-4)](#phase-1-mvp-development)
7. [Phase 2: Core Features (Months 5-8)](#phase-2-core-features)
8. [Phase 3: Advanced Features (Months 9-12)](#phase-3-advanced-features)
9. [Phase 4: Ecosystem Expansion (Months 13-18)](#phase-4-ecosystem-expansion)
10. [Testing Strategy](#testing-strategy)
11. [Deployment Strategy](#deployment-strategy)
12. [Security Implementation](#security-implementation)
13. [Performance Optimization](#performance-optimization)
14. [Monitoring and Maintenance](#monitoring-and-maintenance)

---

## Executive Summary

TiwineBiz is a web-first, offline-capable Progressive Web Application (PWA) designed specifically for Malawian small-scale retail businesses. This implementation plan details the technical approach, architecture, and phased development strategy to build a production-ready system.

### Key Technologies
- **Frontend:** React 18+ with Vite, TypeScript, Tailwind CSS
- **Backend:** Laravel 11 (PHP 8.2+)
- **Database:** PostgreSQL 15+
- **Cache:** Redis
- **Storage:** AWS S3 / DigitalOcean Spaces
- **Infrastructure:** DigitalOcean + Cloudflare

### Development Approach
- Agile methodology with 2-week sprints
- Test-Driven Development (TDD) where applicable
- Continuous Integration/Continuous Deployment (CI/CD)
- Offline-first architecture
- Mobile-first responsive design

---

## Current Implementation Status

**Last Updated:** November 11, 2025

### Overall Progress
- **Backend API:** ~40% Complete (Infrastructure + 9 MVP Features)
- **Frontend Application:** 0% Complete (Not Started)
- **Overall MVP:** ~20% Complete

### ✅ Completed Features

#### Backend Infrastructure
- Multi-tenant architecture (Shop → Branch → Users)
- Branch-based access control with role-based permissions
- Repository pattern implementation
- Comprehensive test suite (93 tests passing)
- Laravel 12 with PHP 8.4
- MySQL database with proper migrations
- Laravel Pint code formatting

#### Backend API Endpoints (51 Total)
1. **Reports & Analytics** - 22 endpoints
   - Dashboard overview with real-time metrics
   - Sales reports (summary, daily, weekly, monthly, comparison, hourly)
   - Product analytics (top-selling, slow-moving, performance tracking)
   - Inventory reports (valuation, movements, aging, alerts, turnover)
   - 30-minute caching for performance
   - Branch-aware filtering

2. **Receipt Generation** - 5 endpoints
   - Bilingual support (English/Chichewa)
   - Professional PDF generation with DomPDF
   - Multiple output formats (view, download, HTML, print, email)
   - EFD-compliant receipt design
   - QR code integration ready
   - Branch-aware access control

3. **Offline Sync System** - 9 endpoints
   - Push/pull synchronization
   - Conflict detection via timestamp comparison
   - Three resolution strategies (client_wins, server_wins, merge)
   - Queue-based processing with priority support
   - Multi-device tracking
   - Support for sale, product, customer, payment, credit entities

4. **Multi-Channel Notifications** - 7 endpoints
   - Notification types: Low Stock Alert, Sale Completed, Payment Reminder, Subscription Expiring
   - Delivery channels: Database, Email, SMS
   - User preference management per notification type and channel
   - SMS providers: Twilio, Africa's Talking (with log fallback)
   - Queue-based async delivery

5. **Report Export Functionality** - 1 endpoint
   - PDF exports with professional styling
   - Excel exports with formatted headers and auto-sizing
   - Support for all report types (summary, daily, weekly, monthly)
   - Date range filtering
   - Branch-level filtering

6. **Authentication & User Management** - 7 endpoints
   - User registration with validation
   - Login with email or phone number
   - Logout with token revocation
   - Password reset flow using Laravel Password Broker
   - Password change for authenticated users
   - Email verification with signed URLs
   - User profile management with photo upload
   - Comprehensive validation and security

7. **Authorization & Role Management** - 1 endpoint + Policies
   - Complete role CRUD API (create, read, update, delete)
   - Role-based permissions system
   - Authorization policies for all resources (Customer, Product, Role, Sale, Shop)
   - Shop-scoped role management
   - Permission assignment and validation

8. **Shop Invitation System** - 4 endpoints
   - Send email invitations to join shops
   - Accept/decline invitation flow
   - Token-based secure invitations
   - Automatic role assignment on acceptance
   - Pending invitations tracking and cancellation

9. **Product Enhancements** - 4 endpoints
   - Product image upload (multiple images support)
   - Product image deletion with re-indexing
   - Stock adjustment endpoint (increase/decrease with audit trail)
   - Bulk product import from CSV/Excel using Laravel Excel
   - Stock transfer between branches with linked movements

10. **Sale Refund System** - 1 endpoint
    - Full refund processing with complete inventory restoration
    - Partial refund with specific items and quantities
    - Multiple refund methods (cash, mobile_money, bank_transfer, card, credit_note)
    - Refund metadata tracking in internal notes
    - Prevents duplicate refunds and refunding cancelled sales

### ❌ Not Yet Implemented

#### Critical Missing Components
- **Frontend Application** - Entire React PWA (0% complete)
  - No POS interface
  - No product management UI
  - No sales recording interface
  - No reports dashboard
  - No user settings pages

- **PWA Features**
  - Service workers for offline support
  - IndexedDB for local data storage
  - Push notifications
  - App installation capability

- **Core Backend Features**
  - Customer management endpoints
  - Credit/payment tracking system
  - Barcode scanning integration
  - WhatsApp integration for receipts

#### Planned Features (Phase 2-4)
- Mobile money integration (Airtel Money, TNM Mpamba)
- MRA EFD compliance and integration
- Multi-currency system
- Multi-user collaboration features
- Advanced analytics and automation
- Voice commands
- Native mobile apps
- B2B and enterprise features

### Next Priorities
1. Build frontend React PWA application
2. Implement customer and credit management endpoints
3. Add barcode scanning integration
4. Add WhatsApp integration for receipts
5. Complete user registration with phone verification
6. Implement language selection (English/Chichewa)
7. User testing and documentation

---

## Technical Architecture

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  React PWA (TypeScript + Vite)                       │  │
│  │  - Service Workers (Offline Support)                 │  │
│  │  - IndexedDB (Local Storage)                         │  │
│  │  - Tailwind CSS + Shadcn/ui                          │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           ↓ HTTPS/REST
┌─────────────────────────────────────────────────────────────┐
│                  CDN LAYER (Cloudflare)                     │
│  - Static Asset Caching                                     │
│  - DDoS Protection                                          │
│  - SSL/TLS Termination                                      │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                   API LAYER (Laravel 11)                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    Auth      │  │   Business   │  │  Integration │     │
│  │   Service    │  │    Logic     │  │   Services   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
│  - RESTful API Endpoints                                    │
│  - JWT Authentication                                       │
│  - Rate Limiting                                            │
│  - Input Validation                                         │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                    DATA LAYER                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  PostgreSQL  │  │    Redis     │  │   Storage    │     │
│  │  (Primary DB)│  │   (Cache)    │  │   (S3/DO)    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│              EXTERNAL INTEGRATIONS                          │
│  - Airtel Money API                                         │
│  - TNM Mpamba API                                           │
│  - MRA EFD Devices                                          │
│  - SMS Gateway (BulkSMS/Africa's Talking)                  │
│  - WhatsApp Business API                                    │
└─────────────────────────────────────────────────────────────┘
```

### Frontend Architecture

```
src/
├── app/
│   ├── App.tsx                 # Main app component
│   ├── routes.tsx              # Route definitions
│   └── providers/              # Context providers
├── features/                   # Feature-based organization
│   ├── auth/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── services/
│   │   └── types/
│   ├── inventory/
│   ├── sales/
│   ├── customers/
│   ├── reports/
│   └── settings/
├── shared/
│   ├── components/             # Reusable UI components
│   ├── hooks/                  # Custom React hooks
│   ├── utils/                  # Helper functions
│   ├── types/                  # TypeScript types
│   └── services/               # API services
├── lib/
│   ├── api.ts                  # API client
│   ├── db.ts                   # IndexedDB wrapper
│   ├── sync.ts                 # Sync logic
│   └── i18n.ts                 # Internationalization
└── workers/
    └── service-worker.ts       # PWA service worker
```

### Backend Architecture (Laravel)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ProductController.php
│   │   │   ├── SaleController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── CreditController.php
│   │   │   ├── ReportController.php
│   │   │   └── SettingsController.php
│   │   └── Webhooks/
│   │       ├── AirtelMoneyController.php
│   │       └── MpambaController.php
│   ├── Middleware/
│   │   ├── CheckSubscription.php
│   │   ├── VerifyTpin.php
│   │   └── RateLimitApi.php
│   └── Requests/
│       ├── ProductRequest.php
│       ├── SaleRequest.php
│       └── CustomerRequest.php
├── Models/
│   ├── User.php
│   ├── Shop.php
│   ├── Product.php
│   ├── Sale.php
│   ├── SaleItem.php
│   ├── Customer.php
│   ├── Credit.php
│   ├── Payment.php
│   └── Subscription.php
├── Services/
│   ├── SalesService.php
│   ├── InventoryService.php
│   ├── CreditService.php
│   ├── ReportService.php
│   ├── SyncService.php
│   ├── PaymentGateways/
│   │   ├── AirtelMoneyService.php
│   │   └── MpambaService.php
│   └── Integrations/
│       ├── EFDService.php
│       ├── SMSService.php
│       └── WhatsAppService.php
├── Jobs/
│   ├── SendPaymentReminder.php
│   ├── GenerateReport.php
│   └── SyncToMRA.php
└── Events/
    ├── SaleCompleted.php
    ├── StockLow.php
    └── PaymentReceived.php
```

---

## Development Environment Setup

### Prerequisites

```bash
# Required Software
- Node.js 18+ LTS
- PHP 8.2+
- Composer 2.6+
- PostgreSQL 15+
- Redis 7+
- Git

# Recommended Tools
- VS Code with extensions:
  - ESLint
  - Prettier
  - PHP Intelephense
  - Laravel Extension Pack
  - Tailwind CSS IntelliSense
- Docker Desktop (for local services)
- Postman or Insomnia (API testing)
```

### Backend Setup (Laravel)

```bash
# 1. Create Laravel project
composer create-project laravel/laravel tiwinebiz-api
cd tiwinebiz-api

# 2. Install additional packages
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require barryvdh/laravel-cors
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require predis/predis

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Database configuration in .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tiwinebiz
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 5. Run migrations
php artisan migrate
php artisan db:seed

# 6. Start development server
php artisan serve
```

### Frontend Setup (React + Vite)

```bash
# 1. Create Vite project with React + TypeScript
npm create vite@latest tiwinebiz-web -- --template react-ts
cd tiwinebiz-web

# 2. Install dependencies
npm install

# Core dependencies
npm install react-router-dom
npm install @tanstack/react-query
npm install zustand
npm install axios
npm install date-fns
npm install zod

# UI dependencies
npm install tailwindcss postcss autoprefixer
npm install @radix-ui/react-dialog @radix-ui/react-dropdown-menu
npm install lucide-react
npm install recharts

# PWA dependencies
npm install workbox-window
npm install idb

# i18n
npm install i18next react-i18next

# Dev dependencies
npm install -D @types/node
npm install -D vite-plugin-pwa

# 3. Initialize Tailwind
npx tailwindcss init -p

# 4. Start development server
npm run dev
```

### Docker Setup (Optional but Recommended)

```yaml
# docker-compose.yml
version: '3.8'

services:
  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: tiwinebiz
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  postgres_data:
  redis_data:
```

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down
```

---

## Database Schema

### Core Tables

#### 1. Users Table
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP,
    phone_verified_at TIMESTAMP,
    preferred_language VARCHAR(10) DEFAULT 'en', -- 'en' or 'ny' (Chichewa)
    timezone VARCHAR(50) DEFAULT 'Africa/Blantyre',
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_phone ON users(phone);
```

#### 2. Shops Table
```sql
CREATE TABLE shops (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    business_type VARCHAR(100), -- 'spare_parts', 'electronics', 'hardware', etc.
    tpin VARCHAR(20), -- MRA Tax Payer Identification Number
    vrn VARCHAR(20), -- VAT Registration Number
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    logo_url TEXT,
    currency VARCHAR(3) DEFAULT 'MWK',
    subscription_tier VARCHAR(20) DEFAULT 'free', -- 'free', 'business', 'professional', 'enterprise'
    subscription_status VARCHAR(20) DEFAULT 'active', -- 'active', 'suspended', 'cancelled'
    subscription_expires_at TIMESTAMP,
    settings JSONB DEFAULT '{}', -- Shop-specific settings
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_shops_owner ON shops(owner_id);
CREATE INDEX idx_shops_tpin ON shops(tpin);
```

#### 3. Products Table
```sql
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    name_chichewa VARCHAR(255),
    description TEXT,
    sku VARCHAR(100), -- Shop's internal product code
    barcode VARCHAR(100),
    manufacturer_code VARCHAR(100),
    category VARCHAR(100),
    subcategory VARCHAR(100),
    
    -- Pricing (stored in MWK)
    cost_price DECIMAL(15, 2) DEFAULT 0.00, -- Buying price
    selling_price DECIMAL(15, 2) NOT NULL,
    min_price DECIMAL(15, 2), -- Minimum selling price (for discounts)
    
    -- Multi-currency pricing
    base_currency VARCHAR(3) DEFAULT 'MWK',
    base_currency_price DECIMAL(15, 2),
    exchange_rate_snapshot JSONB, -- Store rates at time of price setting
    
    -- Inventory
    quantity DECIMAL(10, 2) DEFAULT 0.00,
    unit VARCHAR(20) DEFAULT 'piece', -- 'piece', 'kg', 'litre', 'meter', etc.
    min_stock_level DECIMAL(10, 2) DEFAULT 0.00,
    max_stock_level DECIMAL(10, 2),
    reorder_point DECIMAL(10, 2),
    reorder_quantity DECIMAL(10, 2),
    
    -- Location
    storage_location VARCHAR(100),
    
    -- Tax
    is_vat_applicable BOOLEAN DEFAULT false,
    vat_rate DECIMAL(5, 2) DEFAULT 16.5, -- Current Malawi VAT rate
    tax_category VARCHAR(50) DEFAULT 'standard', -- 'standard', 'zero_rated', 'exempt'
    
    -- Supplier
    supplier_id UUID REFERENCES suppliers(id),
    
    -- Images
    images JSONB DEFAULT '[]', -- Array of image URLs
    
    -- Tracking
    is_active BOOLEAN DEFAULT true,
    is_deleted BOOLEAN DEFAULT false,
    created_by UUID REFERENCES users(id),
    updated_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_products_shop ON products(shop_id);
CREATE INDEX idx_products_sku ON products(shop_id, sku);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_products_category ON products(shop_id, category);
CREATE INDEX idx_products_active ON products(shop_id, is_active, is_deleted);
```

#### 4. Product Batches Table (for FIFO tracking)
```sql
CREATE TABLE product_batches (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    batch_number VARCHAR(100),
    quantity DECIMAL(10, 2) NOT NULL,
    remaining_quantity DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(15, 2) NOT NULL, -- Cost at time of this batch purchase
    supplier_id UUID REFERENCES suppliers(id),
    purchase_date DATE NOT NULL,
    expiry_date DATE,
    import_details JSONB, -- Shipping, customs, clearing costs
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_batches_product ON product_batches(product_id);
CREATE INDEX idx_batches_expiry ON product_batches(expiry_date);
```

#### 5. Customers Table
```sql
CREATE TABLE customers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    
    -- Credit management
    credit_limit DECIMAL(15, 2) DEFAULT 0.00,
    current_balance DECIMAL(15, 2) DEFAULT 0.00, -- Amount owed
    total_spent DECIMAL(15, 2) DEFAULT 0.00, -- Lifetime spending
    trust_level VARCHAR(20) DEFAULT 'new', -- 'trusted', 'monitor', 'restricted', 'new'
    payment_behavior_score INTEGER DEFAULT 50, -- 0-100, calculated based on payment history
    
    -- Preferences
    preferred_language VARCHAR(10) DEFAULT 'en',
    preferred_contact_method VARCHAR(20) DEFAULT 'phone', -- 'phone', 'whatsapp', 'sms', 'email'
    
    -- Metadata
    notes TEXT,
    tags JSONB DEFAULT '[]',
    is_active BOOLEAN DEFAULT true,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_customers_shop ON customers(shop_id);
CREATE INDEX idx_customers_phone ON customers(shop_id, phone);
CREATE INDEX idx_customers_trust ON customers(shop_id, trust_level);
```

#### 6. Sales Table
```sql
CREATE TABLE sales (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    sale_number VARCHAR(50) NOT NULL, -- Sequential: SALE-20240101-0001
    customer_id UUID REFERENCES customers(id) ON DELETE SET NULL,
    
    -- Amounts
    subtotal DECIMAL(15, 2) NOT NULL,
    discount_amount DECIMAL(15, 2) DEFAULT 0.00,
    discount_percentage DECIMAL(5, 2) DEFAULT 0.00,
    tax_amount DECIMAL(15, 2) DEFAULT 0.00, -- VAT
    total_amount DECIMAL(15, 2) NOT NULL,
    
    -- Payment
    amount_paid DECIMAL(15, 2) DEFAULT 0.00,
    balance DECIMAL(15, 2) DEFAULT 0.00, -- Remaining to be paid
    payment_status VARCHAR(20) DEFAULT 'pending', -- 'paid', 'partial', 'pending', 'cancelled'
    
    -- Payment methods (can be split across multiple methods)
    payment_methods JSONB DEFAULT '[]', -- [{method: 'cash', amount: 5000}, {method: 'airtel', amount: 3000}]
    
    -- Currency
    currency VARCHAR(3) DEFAULT 'MWK',
    exchange_rate DECIMAL(10, 4) DEFAULT 1.0000,
    
    -- EFD Integration
    is_fiscalized BOOLEAN DEFAULT false,
    efd_receipt_number VARCHAR(100),
    efd_qr_code TEXT,
    efd_signature TEXT,
    efd_transmitted_at TIMESTAMP,
    
    -- Metadata
    sale_type VARCHAR(20) DEFAULT 'pos', -- 'pos', 'whatsapp', 'phone_order'
    notes TEXT,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    served_by UUID REFERENCES users(id), -- Staff member who made the sale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_at TIMESTAMP,
    cancelled_by UUID REFERENCES users(id),
    cancellation_reason TEXT
);

CREATE INDEX idx_sales_shop ON sales(shop_id);
CREATE INDEX idx_sales_number ON sales(shop_id, sale_number);
CREATE INDEX idx_sales_customer ON sales(customer_id);
CREATE INDEX idx_sales_date ON sales(shop_id, sale_date);
CREATE INDEX idx_sales_status ON sales(shop_id, payment_status);
CREATE INDEX idx_sales_served_by ON sales(served_by);
```

#### 7. Sale Items Table
```sql
CREATE TABLE sale_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    sale_id UUID NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id),
    product_name VARCHAR(255) NOT NULL, -- Snapshot at time of sale
    
    -- Quantities
    quantity DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(20),
    
    -- Pricing
    unit_price DECIMAL(15, 2) NOT NULL,
    cost_price DECIMAL(15, 2), -- For profit calculation
    discount_amount DECIMAL(15, 2) DEFAULT 0.00,
    tax_amount DECIMAL(15, 2) DEFAULT 0.00,
    subtotal DECIMAL(15, 2) NOT NULL,
    
    -- Batch tracking (for FIFO)
    batch_id UUID REFERENCES product_batches(id),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product ON sale_items(product_id);
```

#### 8. Credits Table
```sql
CREATE TABLE credits (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    sale_id UUID REFERENCES sales(id) ON DELETE SET NULL,
    
    credit_number VARCHAR(50) NOT NULL, -- CREDIT-20240101-0001
    
    -- Amounts
    original_amount DECIMAL(15, 2) NOT NULL,
    amount_paid DECIMAL(15, 2) DEFAULT 0.00,
    balance DECIMAL(15, 2) NOT NULL,
    
    -- Dates
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_term VARCHAR(50), -- 'lero', 'mawa', 'sabata', 'malipiro', 'custom'
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'partial', 'paid', 'overdue', 'written_off'
    
    -- Reminders
    last_reminder_sent_at TIMESTAMP,
    reminder_count INTEGER DEFAULT 0,
    
    -- Metadata
    notes TEXT,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_credits_shop ON credits(shop_id);
CREATE INDEX idx_credits_customer ON credits(customer_id);
CREATE INDEX idx_credits_status ON credits(shop_id, status);
CREATE INDEX idx_credits_due_date ON credits(shop_id, due_date, status);
```

#### 9. Payments Table
```sql
CREATE TABLE payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    customer_id UUID REFERENCES customers(id),
    credit_id UUID REFERENCES credits(id) ON DELETE SET NULL,
    sale_id UUID REFERENCES sales(id) ON DELETE SET NULL,
    
    payment_number VARCHAR(50) NOT NULL, -- PAY-20240101-0001
    
    -- Amount
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MWK',
    
    -- Payment method
    payment_method VARCHAR(50) NOT NULL, -- 'cash', 'airtel_money', 'mpamba', 'bank_transfer'
    transaction_reference VARCHAR(100), -- Mobile money transaction ID
    
    -- Mobile money details
    mobile_money_details JSONB, -- {phone: '0999123456', name: 'John Doe', agent: 'A12345'}
    
    -- Dates
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Metadata
    notes TEXT,
    received_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_shop ON payments(shop_id);
CREATE INDEX idx_payments_customer ON payments(customer_id);
CREATE INDEX idx_payments_credit ON payments(credit_id);
CREATE INDEX idx_payments_date ON payments(shop_id, payment_date);
CREATE INDEX idx_payments_method ON payments(shop_id, payment_method);
```

#### 10. Suppliers Table
```sql
CREATE TABLE suppliers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    
    -- Payment terms
    payment_terms TEXT,
    credit_days INTEGER DEFAULT 0,
    
    -- Performance tracking
    reliability_score INTEGER DEFAULT 50, -- 0-100
    average_delivery_days INTEGER,
    
    -- Metadata
    notes TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_suppliers_shop ON suppliers(shop_id);
```

#### 11. Purchase Orders Table
```sql
CREATE TABLE purchase_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    supplier_id UUID NOT NULL REFERENCES suppliers(id),
    
    po_number VARCHAR(50) NOT NULL, -- PO-20240101-0001
    
    -- Amounts
    subtotal DECIMAL(15, 2) NOT NULL,
    tax_amount DECIMAL(15, 2) DEFAULT 0.00,
    shipping_cost DECIMAL(15, 2) DEFAULT 0.00,
    customs_duty DECIMAL(15, 2) DEFAULT 0.00,
    clearing_fee DECIMAL(15, 2) DEFAULT 0.00,
    other_charges DECIMAL(15, 2) DEFAULT 0.00,
    total_amount DECIMAL(15, 2) NOT NULL,
    
    -- Currency
    currency VARCHAR(3) DEFAULT 'USD',
    exchange_rate DECIMAL(10, 4),
    amount_in_mwk DECIMAL(15, 2),
    
    -- Status
    status VARCHAR(20) DEFAULT 'draft', -- 'draft', 'sent', 'confirmed', 'in_transit', 'received', 'cancelled'
    
    -- Dates
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    
    -- Shipping & Clearance
    shipping_method VARCHAR(100),
    tracking_number VARCHAR(100),
    border_point VARCHAR(100), -- 'Mwanza', 'Dedza', 'Songwe'
    clearing_agent VARCHAR(255),
    clearing_agent_contact VARCHAR(100),
    
    -- Documents
    documents JSONB DEFAULT '[]', -- URLs to uploaded docs (Bill of Lading, Packing List, etc.)
    
    -- Metadata
    notes TEXT,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_purchase_orders_shop ON purchase_orders(shop_id);
CREATE INDEX idx_purchase_orders_supplier ON purchase_orders(supplier_id);
CREATE INDEX idx_purchase_orders_status ON purchase_orders(shop_id, status);
```

#### 12. Purchase Order Items Table
```sql
CREATE TABLE purchase_order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    purchase_order_id UUID NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    product_id UUID REFERENCES products(id),
    
    product_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    quantity_received DECIMAL(10, 2) DEFAULT 0.00,
    unit VARCHAR(20),
    unit_price DECIMAL(15, 2) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL,
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_po_items_po ON purchase_order_items(purchase_order_id);
```

#### 13. Stock Movements Table
```sql
CREATE TABLE stock_movements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    
    movement_type VARCHAR(50) NOT NULL, -- 'sale', 'purchase', 'return', 'adjustment', 'damage', 'theft', 'transfer'
    
    -- Quantities
    quantity DECIMAL(10, 2) NOT NULL, -- Positive for increase, negative for decrease
    quantity_before DECIMAL(10, 2) NOT NULL,
    quantity_after DECIMAL(10, 2) NOT NULL,
    
    -- Cost
    unit_cost DECIMAL(15, 2),
    total_cost DECIMAL(15, 2),
    
    -- References
    reference_id UUID, -- ID of related sale, purchase, etc.
    reference_type VARCHAR(50), -- 'sale', 'purchase_order', 'adjustment'
    
    -- Batch
    batch_id UUID REFERENCES product_batches(id),
    
    -- Metadata
    reason TEXT,
    notes TEXT,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_stock_movements_shop ON stock_movements(shop_id);
CREATE INDEX idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_date ON stock_movements(shop_id, created_at);
CREATE INDEX idx_stock_movements_type ON stock_movements(shop_id, movement_type);
```

#### 14. Exchange Rates Table
```sql
CREATE TABLE exchange_rates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    base_currency VARCHAR(3) DEFAULT 'MWK',
    target_currency VARCHAR(3) NOT NULL,
    
    official_rate DECIMAL(10, 4) NOT NULL, -- NBM rate
    street_rate DECIMAL(10, 4), -- Parallel market rate
    rate_used VARCHAR(20) DEFAULT 'official', -- Which rate shops should use
    
    effective_date DATE NOT NULL,
    source VARCHAR(100), -- 'nbm', 'user_input', 'api'
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_exchange_rates_currency ON exchange_rates(target_currency, effective_date);
CREATE UNIQUE INDEX idx_exchange_rates_unique ON exchange_rates(target_currency, effective_date);
```

#### 15. User Roles & Permissions Tables

```sql
CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID REFERENCES shops(id) ON DELETE CASCADE,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100),
    description TEXT,
    is_system_role BOOLEAN DEFAULT false, -- System roles can't be deleted
    permissions JSONB DEFAULT '[]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE shop_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id UUID NOT NULL REFERENCES roles(id),
    
    is_active BOOLEAN DEFAULT true,
    invited_by UUID REFERENCES users(id),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(shop_id, user_id)
);

CREATE INDEX idx_shop_users_shop ON shop_users(shop_id);
CREATE INDEX idx_shop_users_user ON shop_users(user_id);
```

#### 16. Activity Log Table
```sql
CREATE TABLE activity_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID REFERENCES shops(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    action VARCHAR(100) NOT NULL, -- 'product.created', 'sale.completed', 'price.changed'
    entity_type VARCHAR(50), -- 'product', 'sale', 'customer'
    entity_id UUID,
    
    old_values JSONB,
    new_values JSONB,
    
    ip_address INET,
    user_agent TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_activity_logs_shop ON activity_logs(shop_id, created_at);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id, created_at);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
```

#### 17. Subscriptions & Billing Tables

```sql
CREATE TABLE subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    
    plan VARCHAR(20) NOT NULL, -- 'free', 'business', 'professional', 'enterprise'
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'cancelled', 'suspended', 'grace_period'
    
    -- Billing
    billing_cycle VARCHAR(20) DEFAULT 'monthly', -- 'monthly', 'annual'
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MWK',
    
    -- Dates
    started_at TIMESTAMP NOT NULL,
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    cancelled_at TIMESTAMP,
    
    -- Features
    features JSONB DEFAULT '{}',
    limits JSONB DEFAULT '{}', -- {max_products: 1000, max_users: 10, max_sales_per_month: unlimited}
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subscription_payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    subscription_id UUID NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
    
    payment_number VARCHAR(50) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MWK',
    
    payment_method VARCHAR(50), -- 'airtel_money', 'mpamba', 'bank_transfer'
    transaction_reference VARCHAR(100),
    
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'confirmed', 'failed'
    
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,
    
    payment_date TIMESTAMP,
    confirmed_at TIMESTAMP,
    confirmed_by UUID REFERENCES users(id),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 18. Sync Queue Table (for offline sync)

```sql
CREATE TABLE sync_queue (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id),
    
    entity_type VARCHAR(50) NOT NULL, -- 'sale', 'product', 'payment'
    entity_id UUID,
    action VARCHAR(20) NOT NULL, -- 'create', 'update', 'delete'
    
    data JSONB NOT NULL,
    
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
    attempts INTEGER DEFAULT 0,
    last_attempt_at TIMESTAMP,
    error_message TEXT,
    
    priority INTEGER DEFAULT 5, -- 1-10, higher = more urgent
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP
);

CREATE INDEX idx_sync_queue_shop ON sync_queue(shop_id, status);
CREATE INDEX idx_sync_queue_priority ON sync_queue(priority DESC, created_at);
```

### Database Migrations Plan

**Week 1-2: Core Schema**
- Users, Shops, Roles
- Products, Categories
- Customers

**Week 3-4: Sales & Inventory**
- Sales, Sale Items
- Stock Movements
- Product Batches

**Week 5-6: Credit & Payments**
- Credits
- Payments
- Subscriptions

**Week 7-8: Advanced Features**
- Suppliers, Purchase Orders
- Exchange Rates
- Activity Logs
- Sync Queue

---

## API Architecture

### RESTful API Design Principles

1. **Base URL:** `https://api.tiwinebiz.mw/v1`
2. **Authentication:** JWT Bearer tokens
3. **Response Format:** JSON
4. **HTTP Methods:** GET (read), POST (create), PUT/PATCH (update), DELETE (remove)
5. **Status Codes:**
   - 200: Success
   - 201: Created
   - 204: No Content (successful delete)
   - 400: Bad Request
   - 401: Unauthorized
   - 403: Forbidden
   - 404: Not Found
   - 422: Validation Error
   - 429: Rate Limited
   - 500: Server Error

### Standard Response Format

```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "meta": {
    "timestamp": "2024-11-09T10:30:00Z",
    "request_id": "req_abc123"
  }
}
```

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."]
    }
  },
  "meta": {
    "timestamp": "2024-11-09T10:30:00Z",
    "request_id": "req_abc123"
  }
}
```

### API Endpoints

#### Authentication Endpoints

```
POST   /auth/register              # Register new user
POST   /auth/login                 # Login
POST   /auth/logout                # Logout
POST   /auth/refresh               # Refresh token
GET    /auth/user                  # Get current user
POST   /auth/verify-phone          # Verify phone number
POST   /auth/resend-verification   # Resend verification code
POST   /auth/forgot-password       # Request password reset
POST   /auth/reset-password        # Reset password
```

**Example: Register**
```bash
POST /auth/register
Content-Type: application/json

{
  "name": "John Banda",
  "email": "john@example.com",
  "phone": "0999123456",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "preferred_language": "ny"
}

Response (201):
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid",
      "name": "John Banda",
      "email": "john@example.com",
      "phone": "0999123456",
      "preferred_language": "ny"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_at": "2024-11-10T10:30:00Z"
  }
}
```

#### Shop Endpoints

```
GET    /shops                      # Get user's shops
POST   /shops                      # Create new shop
GET    /shops/{id}                 # Get shop details
PUT    /shops/{id}                 # Update shop
DELETE /shops/{id}                 # Delete shop
POST   /shops/{id}/logo            # Upload shop logo
GET    /shops/{id}/settings        # Get shop settings
PUT    /shops/{id}/settings        # Update shop settings
GET    /shops/{id}/users           # Get shop users
POST   /shops/{id}/users           # Invite user to shop
DELETE /shops/{id}/users/{userId}  # Remove user from shop
```

#### Product Endpoints

```
GET    /products                   # List all products (with filters)
POST   /products                   # Create product
GET    /products/{id}              # Get product details
PUT    /products/{id}              # Update product
DELETE /products/{id}              # Delete product (soft delete)
POST   /products/bulk-import       # Import products from CSV/Excel
POST   /products/{id}/images       # Upload product images
DELETE /products/{id}/images/{imageId}  # Delete product image
GET    /products/{id}/stock-history # Get stock movement history
POST   /products/{id}/adjust-stock # Adjust stock manually
GET    /products/low-stock         # Get products below min stock level
POST   /products/bulk-price-update # Update multiple product prices
GET    /products/{id}/sales-stats  # Get product sales statistics
```

**Example: List Products**
```bash
GET /products?shop_id=uuid&category=electronics&search=phone&page=1&per_page=20&sort=name

Response (200):
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Samsung Galaxy A14",
      "name_chichewa": "Foni ya Samsung",
      "sku": "SAM-A14-BLK",
      "barcode": "1234567890123",
      "category": "electronics",
      "selling_price": 145000.00,
      "cost_price": 120000.00,
      "quantity": 15,
      "min_stock_level": 5,
      "is_low_stock": false,
      "images": ["url1", "url2"],
      "created_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 145,
    "last_page": 8
  }
}
```

#### Sales Endpoints

```
GET    /sales                      # List all sales
POST   /sales                      # Create new sale
GET    /sales/{id}                 # Get sale details
PUT    /sales/{id}                 # Update sale (before fiscalization)
DELETE /sales/{id}                 # Cancel sale
POST   /sales/{id}/fiscalize       # Send to EFD for fiscalization
GET    /sales/{id}/receipt         # Get receipt (PDF/HTML)
POST   /sales/{id}/send-receipt    # Send receipt via WhatsApp/SMS/Email
GET    /sales/today                # Today's sales summary
GET    /sales/reports              # Sales reports (date range, filters)
POST   /sales/{id}/refund          # Process refund/return
GET    /sales/stats                # Sales statistics
```

**Example: Create Sale**
```bash
POST /sales
Content-Type: application/json

{
  "shop_id": "uuid",
  "customer_id": "uuid",
  "sale_type": "pos",
  "items": [
    {
      "product_id": "uuid",
      "quantity": 2,
      "unit_price": 25000.00
    },
    {
      "product_id": "uuid",
      "quantity": 1,
      "unit_price": 15000.00
    }
  ],
  "payment_methods": [
    {
      "method": "cash",
      "amount": 50000.00
    },
    {
      "method": "airtel_money",
      "amount": 15000.00,
      "transaction_reference": "AM12345678"
    }
  ],
  "discount_amount": 0.00,
  "notes": "Customer is a regular"
}

Response (201):
{
  "success": true,
  "data": {
    "id": "uuid",
    "sale_number": "SALE-20241109-0001",
    "subtotal": 65000.00,
    "discount_amount": 0.00,
    "tax_amount": 0.00,
    "total_amount": 65000.00,
    "amount_paid": 65000.00,
    "balance": 0.00,
    "payment_status": "paid",
    "items": [...],
    "receipt_url": "https://api.tiwinebiz.mw/receipts/uuid.pdf"
  }
}
```

#### Customer Endpoints

```
GET    /customers                  # List customers
POST   /customers                  # Create customer
GET    /customers/{id}             # Get customer details
PUT    /customers/{id}             # Update customer
DELETE /customers/{id}             # Delete customer
GET    /customers/{id}/sales       # Get customer purchase history
GET    /customers/{id}/credits     # Get customer credit ledger
POST   /customers/{id}/send-reminder # Send payment reminder
GET    /customers/{id}/stats       # Get customer statistics
POST   /customers/bulk-import      # Import customers from CSV
```

#### Credit Endpoints

```
GET    /credits                    # List all credits
POST   /credits                    # Create credit entry
GET    /credits/{id}               # Get credit details
PUT    /credits/{id}               # Update credit
POST   /credits/{id}/payment       # Record payment against credit
GET    /credits/overdue            # Get overdue credits
POST   /credits/bulk-reminder      # Send reminders to multiple customers
GET    /credits/aging-report       # Get aging analysis report
```

#### Payment Endpoints

```
GET    /payments                   # List all payments
POST   /payments                   # Record payment
GET    /payments/{id}              # Get payment details
DELETE /payments/{id}              # Void payment (with reason)
GET    /payments/reconciliation    # Daily reconciliation report
POST   /payments/mobile-money-webhook # Webhook for mobile money confirmation
```

#### Reports Endpoints

```
GET    /reports/daily              # Daily sales/inventory report
GET    /reports/weekly             # Weekly summary
GET    /reports/monthly            # Monthly summary
GET    /reports/custom             # Custom date range report
GET    /reports/profit-loss        # P&L statement
GET    /reports/cash-flow          # Cash flow report
GET    /reports/inventory-value    # Current inventory valuation
GET    /reports/top-products       # Best selling products
GET    /reports/top-customers      # Best customers
GET    /reports/tax-summary        # MRA tax summary
POST   /reports/export             # Export report (PDF/Excel/CSV)
```

#### Supplier Endpoints

```
GET    /suppliers                  # List suppliers
POST   /suppliers                  # Create supplier
GET    /suppliers/{id}             # Get supplier details
PUT    /suppliers/{id}             # Update supplier
DELETE /suppliers/{id}             # Delete supplier
GET    /suppliers/{id}/orders      # Get supplier's purchase orders
GET    /suppliers/{id}/performance # Get supplier performance metrics
```

#### Purchase Order Endpoints

```
GET    /purchase-orders            # List purchase orders
POST   /purchase-orders            # Create purchase order
GET    /purchase-orders/{id}       # Get PO details
PUT    /purchase-orders/{id}       # Update PO
DELETE /purchase-orders/{id}       # Cancel PO
POST   /purchase-orders/{id}/send  # Send PO to supplier
POST   /purchase-orders/{id}/receive # Mark as received and add to inventory
POST   /purchase-orders/{id}/documents # Upload documents
```

#### Settings Endpoints

```
GET    /settings/exchange-rates    # Get current exchange rates
POST   /settings/exchange-rates    # Update exchange rates (manual)
GET    /settings/tax-rates         # Get tax configuration
PUT    /settings/tax-rates         # Update tax settings
GET    /settings/receipt-template  # Get receipt template
PUT    /settings/receipt-template  # Update receipt template
GET    /settings/notifications     # Get notification settings
PUT    /settings/notifications     # Update notification settings
```

#### Sync Endpoints (for offline support)

```
POST   /sync/push                  # Push local changes to server
GET    /sync/pull                  # Pull server changes
GET    /sync/status                # Get sync status
POST   /sync/resolve-conflicts     # Resolve sync conflicts
```

#### Dashboard Endpoints

```
GET    /dashboard/summary          # Dashboard overview
GET    /dashboard/charts           # Charts data
GET    /dashboard/alerts           # Important alerts
GET    /dashboard/recent-activity  # Recent activity feed
```

### API Rate Limiting

```
Rate Limits (per shop per minute):
- Free tier: 60 requests/minute
- Business tier: 120 requests/minute
- Professional tier: 300 requests/minute
- Enterprise tier: Unlimited

Headers returned:
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1699534800
```

### API Versioning

- Current version: v1
- Version in URL: `/v1/products`
- Deprecated versions supported for 12 months after new version release
- Breaking changes require new version

---

## Phase 1: MVP Development (Months 1-4)

### Month 1: Foundation & Setup

#### Week 1-2: Project Setup & Authentication

**Backend Tasks:**
1. ✅ Initialize Laravel project
2. ✅ Set up PostgreSQL database
3. ✅ Configure Redis cache
4. ✅ Install Laravel Sanctum for authentication
5. ✅ Create User and Shop models
6. ✅ Implement JWT authentication
7. ✅ Build registration endpoint with phone verification
8. ✅ Build login/logout endpoints
9. ✅ Implement password reset flow
10. ✅ Set up API rate limiting

**Frontend Tasks:**
1. ✅ Initialize React + Vite project with TypeScript
2. ✅ Configure Tailwind CSS
3. ✅ Install and configure React Router
4. ✅ Set up React Query for data fetching
5. ✅ Create Zustand stores for state management
6. ✅ Build authentication UI components:
   - Login form
   - Registration form (multi-step with phone verification)
   - Forgot password form
7. ✅ Implement authentication flow
8. ✅ Create protected route wrapper
9. ✅ Set up axios interceptors for API calls
10. ✅ Implement language switcher (English/Chichewa)

**Deliverables:**
- Working authentication system
- User can register, verify phone, login
- JWT token management
- Protected routes

#### Week 3-4: Shop Setup & Basic Product Management

**Backend Tasks:**
1. ✅ Create Shop model and migrations
2. ✅ Implement shop creation API
3. ✅ Create Product model and migrations
4. ✅ Build product CRUD endpoints
5. ✅ Implement product image upload (S3/DO Spaces)
6. ✅ Add product search and filtering
7. ✅ Create product categories/subcategories
8. ✅ Implement basic validation rules
9. ✅ Set up activity logging
10. ✅ Create product factory and seeders for testing

**Frontend Tasks:**
1. ✅ Build shop setup wizard (onboarding)
2. ✅ Create shop settings page
3. ✅ Build product management interface:
   - Product list with search/filter
   - Add product form
   - Edit product form
   - Product details view
4. ✅ Implement image upload with preview
5. ✅ Create category selector component
6. ✅ Build barcode scanner integration (camera)
7. ✅ Implement bilingual product names (EN/Chichewa)
8. ✅ Create responsive table for mobile
9. ✅ Add loading states and error handling
10. ✅ Implement optimistic updates

**Deliverables:**
- Shop creation and configuration
- Complete product management system
- Image upload functionality
- Search and filter products

### Month 2: Sales & Inventory Tracking

#### Week 5-6: Sales System

**Backend Tasks:**
1. ✅ Create Sales and SaleItems models
2. ✅ Implement sale creation endpoint
3. ✅ Build automatic stock deduction logic
4. ✅ Create sequential sale numbering system
5. ✅ Implement sale cancellation/refund logic
6. ✅ Build receipt generation (PDF)
7. ✅ Create sale history endpoint
8. ✅ Implement daily sales summary
9. ✅ Add payment method tracking
10. ✅ Create sale statistics endpoint

**Frontend Tasks:**
1. ✅ Build POS (Point of Sale) interface:
   - Product search/selection
   - Shopping cart
   - Quantity adjustment
   - Price display
2. ✅ Implement fast sale entry (optimized for speed)
3. ✅ Create payment method selector (cash, mobile money, credit)
4. ✅ Build split payment interface (multiple methods)
5. ✅ Create receipt view/print component
6. ✅ Implement WhatsApp share functionality
7. ✅ Build sales history page
8. ✅ Create daily summary dashboard
9. ✅ Add recently sold items for quick access
10. ✅ Implement barcode scanning for product selection

**Deliverables:**
- Fully functional POS system
- Receipt generation and sharing
- Sales history and reporting
- Automatic inventory updates

#### Week 7-8: Inventory Management & Stock Tracking

**Backend Tasks:**
1. ✅ Create StockMovements model
2. ✅ Implement stock adjustment endpoint
3. ✅ Build stock movement history
4. ✅ Create low stock alerts system
5. ✅ Implement stock value calculation
6. ✅ Build inventory reports
7. ✅ Create stock-taking/audit functionality
8. ✅ Implement product batch tracking (basic)
9. ✅ Add reorder point calculations
10. ✅ Create inventory statistics

**Frontend Tasks:**
1. ✅ Build inventory dashboard:
   - Current stock levels
   - Low stock alerts
   - Stock value summary
2. ✅ Create stock adjustment form
3. ✅ Build stock movement history view
4. ✅ Implement stock-taking interface
5. ✅ Create low stock alert notifications
6. ✅ Build inventory reports page
7. ✅ Add visual indicators (color-coded stock levels)
8. ✅ Implement bulk stock update
9. ✅ Create stock movement filters
10. ✅ Build reorder alerts

**Deliverables:**
- Complete inventory tracking
- Stock movement history
- Low stock alerts
- Stock adjustment tools
- Basic reporting

### Month 3: Offline Functionality & PWA

#### Week 9-10: PWA Setup & Service Workers

**Frontend Tasks:**
1. ✅ Configure Vite PWA plugin
2. ✅ Create service worker configuration
3. ✅ Implement offline detection
4. ✅ Set up IndexedDB for local storage
5. ✅ Create data sync queue system
6. ✅ Build cache strategies:
   - Cache-first for static assets
   - Network-first for API calls with fallback
7. ✅ Implement background sync
8. ✅ Create offline indicator UI
9. ✅ Build sync status component
10. ✅ Test offline scenarios

**Backend Tasks:**
1. ✅ Create sync endpoints
2. ✅ Implement conflict resolution logic
3. ✅ Build sync queue table
4. ✅ Create sync status tracking
5. ✅ Implement idempotency for repeated requests
6. ✅ Add timestamp-based sync
7. ✅ Create data versioning
8. ✅ Build sync error handling
9. ✅ Implement partial sync (delta updates)
10. ✅ Add sync monitoring/logging

**Deliverables:**
- Fully functional PWA
- Offline mode for critical features
- Automatic sync when online
- Conflict resolution system

#### Week 11-12: Offline Sales & Data Persistence

**Frontend Tasks:**
1. ✅ Implement offline sale creation
2. ✅ Build local queue for pending sales
3. ✅ Create offline product search
4. ✅ Implement local stock deduction
5. ✅ Build offline receipt generation
6. ✅ Create sync indicators for each entity
7. ✅ Implement auto-sync when connection restored
8. ✅ Build manual sync trigger
9. ✅ Create sync conflict resolution UI
10. ✅ Add offline storage management

**Backend Tasks:**
1. ✅ Optimize sync endpoints for batch operations
2. ✅ Implement efficient diff calculation
3. ✅ Create bulk sale processing
4. ✅ Build duplicate detection
5. ✅ Implement server-side conflict resolution
6. ✅ Add sync performance monitoring
7. ✅ Create data compression for sync
8. ✅ Implement incremental sync
9. ✅ Build sync audit trail
10. ✅ Add sync recovery mechanisms

**Deliverables:**
- Offline sales capability
- Local data persistence
- Automatic synchronization
- Conflict resolution

### Month 4: Reports & MVP Polish

#### Week 13-14: Basic Reporting

**Backend Tasks:**
1. ✅ Create report generation service
2. ✅ Build daily sales report
3. ✅ Implement weekly summary report
4. ✅ Create monthly report
5. ✅ Build product performance report
6. ✅ Implement cash flow report
7. ✅ Create PDF export functionality
8. ✅ Build Excel export (using Maatwebsite/Excel)
9. ✅ Implement report caching
10. ✅ Add report scheduling (background jobs)

**Frontend Tasks:**
1. ❌ Build reports dashboard
2. ❌ Create date range selector
3. ❌ Implement report visualization:
   - Charts (line, bar, pie)
   - Tables
   - KPI cards
4. ❌ Build export buttons (PDF, Excel)
5. ❌ Create print-friendly view
6. ❌ Implement WhatsApp sharing for reports
7. ❌ Build report filters
8. ❌ Create comparison views (this week vs last week)
9. ❌ Add visual indicators and trends
10. ❌ Implement report bookmarks/favorites

**Deliverables:**
- Daily, weekly, monthly reports
- Export functionality
- Visual charts and graphs
- Shareable reports

#### Week 15-16: MVP Polish, Testing & Documentation

**Backend Tasks:**
1. ❌ Performance optimization
2. ❌ Security audit
3. ❌ API documentation (Swagger/OpenAPI)
4. ❌ Error handling improvements
5. ❌ Database optimization (indexes, queries)
6. ✅ Add request validation everywhere
7. ❌ Implement proper error logging
8. ❌ Set up monitoring (Sentry)
9. ❌ Create API response caching
10. ✅ Write unit tests for critical paths

**Frontend Tasks:**
1. ❌ UI/UX improvements
2. ❌ Performance optimization (code splitting, lazy loading)
3. ❌ Accessibility improvements (ARIA labels, keyboard navigation)
4. ❌ Mobile responsiveness polish
5. ❌ Error message improvements
6. ❌ Loading state consistency
7. ❌ Add helpful tooltips and hints
8. ❌ Implement onboarding tour
9. ❌ Create help documentation
10. ❌ Build feedback/support form

**Testing Tasks:**
1. ❌ Write E2E tests (Playwright/Cypress)
2. ✅ Unit test critical components (Backend only - 69 tests)
3. ❌ Test offline scenarios thoroughly
4. ❌ Load testing (simulate 100 concurrent users)
5. ❌ Test on various devices and browsers
6. ❌ Test slow network conditions
7. ❌ Security testing (OWASP Top 10)
8. ❌ Test data integrity after sync
9. ❌ User acceptance testing with pilot shops
10. ❌ Fix all critical bugs

**Documentation Tasks:**
1. ❌ Write user manual (English & Chichewa)
2. ❌ Create video tutorials
3. ❌ Build knowledge base
4. ❌ Write API documentation
5. ❌ Create deployment guide
6. ❌ Document common issues and solutions
7. ❌ Write onboarding checklist
8. ❌ Create training materials
9. ❌ Build FAQ section
10. ✅ Write developer documentation (README only)

**Deliverables:**
- Polished, production-ready MVP
- Complete documentation
- Comprehensive test coverage
- Performance optimized
- Ready for pilot launch

### MVP Feature Checklist

**Authentication & User Management:**
- [x] User registration (Backend API complete with validation)
- [x] Login/Logout (Backend API complete with email/phone support)
- [x] Password reset (Backend API complete)
- [ ] Language selection (English/Chichewa)
- [x] Profile management (Backend API complete with photo upload)
- [x] Email verification (Backend API complete with signed URLs)
- [x] Password change (Backend API complete)
- [ ] Phone verification (Frontend integration needed)

**Shop Management:**
- [x] Shop creation and setup (Backend API complete)
- [x] Shop settings configuration (Backend API complete)
- [ ] Logo upload (Frontend integration needed)
- [x] Basic shop information (Backend API complete)
- [x] Shop invitation system (Backend API complete with email invitations)
- [x] Role-based access control (Backend API complete with policies)
- [x] Multi-user collaboration (Backend authorization complete)
- [x] Branch management (Backend API complete with CRUD operations)

**Product Management:**
- [x] Add/Edit/Delete products (Backend API complete)
- [x] Product search and filtering (Backend API complete)
- [x] Image upload (Backend API complete with multiple images support)
- [x] Bilingual product names (Backend API complete - name_chichewa field)
- [ ] Barcode scanning (Frontend integration needed)
- [x] Category management (Backend API complete with CRUD operations)
- [x] Pricing (cost & selling price) (Backend API complete)
- [x] Basic stock tracking (Backend API complete)
- [x] Stock adjustments (Backend API complete with audit trail)
- [x] Bulk product import (Backend API complete with CSV/Excel support)
- [x] Stock transfer between branches (Backend API complete)
- [x] Product batches (Backend API complete for expiry tracking)

**Sales:**
- [ ] POS interface (Frontend needed)
- [ ] Fast product selection (Frontend needed)
- [ ] Shopping cart (Frontend needed)
- [x] Cash payment processing (Backend API complete)
- [x] Manual mobile money logging (Backend API complete)
- [x] Sale creation and tracking (Backend API complete)
- [x] Receipt generation (Backend API - PDF generation complete)
- [x] Receipt email (Backend API complete)
- [ ] Receipt sharing (WhatsApp)
- [x] Sales history (Backend API complete with filtering)
- [x] Daily summary (Backend API complete)
- [x] Sale refunds (Backend API complete with full and partial refund support)
- [x] Payment tracking (Backend API complete - immutable payment records)
- [x] Credit/Layaway tracking (Backend API complete)

**Inventory:**
- [x] Current stock view (Backend API complete)
- [x] Low stock alerts (Backend notifications complete)
- [x] Stock adjustments (Backend API complete - increase/decrease with audit trail)
- [x] Stock movement history (Backend inventory reports complete)
- [x] Stock value calculation (Backend inventory valuation complete)
- [x] Stock transfers between branches (Backend API complete)
- [x] Supplier management (Backend API complete)
- [x] Purchase orders (Backend API complete)
- [x] Customer management (Backend API complete)

**Reports:**
- [x] Daily sales report (Backend API complete)
- [x] Weekly summary (Backend API complete)
- [x] Monthly summary (Backend API complete)
- [x] Hourly sales breakdown (Backend API complete)
- [x] Sales comparison (Backend API complete)
- [x] Top customers report (Backend API complete)
- [x] Product performance (Backend API complete)
- [x] Top-selling products (Backend API complete)
- [x] Slow-moving products (Backend API complete)
- [x] Category performance (Backend API complete)
- [x] Low stock report (Backend API complete)
- [x] Inventory valuation (Backend API complete)
- [x] Stock movement tracking (Backend API complete)
- [x] Inventory aging analysis (Backend API complete)
- [x] Stock turnover (Backend API complete)
- [x] Dashboard with quick stats (Backend API complete)
- [x] Basic cash flow (Backend API complete)
- [x] PDF export (Backend API complete)
- [x] Excel export (Backend API complete)
- [ ] WhatsApp sharing (Integration needed)

**Offline Features:**
- [ ] PWA installation (Frontend needed)
- [ ] Offline sales recording (Frontend needed)
- [ ] Local data storage (Frontend IndexedDB needed)
- [x] Automatic sync (Backend sync endpoints complete)
- [ ] Sync status indicators (Frontend needed)
- [x] Conflict resolution (Backend conflict resolution complete with 3 strategies)
- [x] Queue-based sync processing (Backend API complete)
- [x] Multi-device tracking (Backend API complete)

**Other:**
- [x] Bilingual support (Backend API complete - Chichewa fields in products, receipts)
- [ ] Chichewa translation (Frontend i18n needed)
- [ ] Mobile responsive design (Frontend needed)
- [ ] Help documentation (Content needed)
- [ ] User onboarding (Frontend needed)
- [x] Multi-channel notifications (Backend API complete - Email, SMS, Database)
- [x] Notification preferences (Backend API complete)
- [x] Multi-currency support (Backend API complete - Exchange rates)
- [x] EFD transaction tracking (Backend API complete)
- [x] Mobile money transaction logging (Backend API complete)
- [x] Subscription management (Backend API complete)

---

## Phase 2: Core Features (Months 5-8)

### Month 5: Customer & Credit Management

#### Week 17-18: Customer Management

**Backend Tasks:**
1. ❌ Create Customer model with complete schema
2. ❌ Implement customer CRUD endpoints
3. ❌ Build customer search with phone lookup
4. ❌ Create customer purchase history
5. ❌ Implement customer statistics
6. ❌ Build customer segmentation
7. ❌ Create customer import from CSV
8. ❌ Implement duplicate detection
9. ❌ Add customer notes and tags
10. ❌ Build customer lifecycle tracking

**Frontend Tasks:**
1. ❌ Build customer directory
2. ❌ Create customer profile page
3. ❌ Implement quick customer lookup in POS
4. ❌ Build customer add/edit forms
5. ❌ Create purchase history view
6. ❌ Implement customer statistics dashboard
7. ❌ Build customer search with filters
8. ❌ Create customer import tool
9. ❌ Add customer tags management
10. ❌ Implement customer notes

**Deliverables:**
- Complete customer database
- Customer profiles and history
- Customer search and segmentation
- CSV import capability

#### Week 19-20: Credit Management System

**Backend Tasks:**
1. ❌ Create Credit model
2. ❌ Implement credit creation from sale
3. ❌ Build credit payment recording
4. ❌ Create aging analysis
5. ❌ Implement overdue detection
6. ❌ Build payment reminder system
7. ❌ Create credit limits enforcement
8. ❌ Implement payment behavior scoring
9. ❌ Build credit history
10. ❌ Create credit reports

**Frontend Tasks:**
1. ❌ Build credit option in POS
2. ❌ Create credit ledger view
3. ❌ Implement payment terms selector (Lero, Mawa, etc.)
4. ❌ Build payment recording interface
5. ❌ Create overdue credits dashboard
6. ❌ Implement aging analysis report
7. ❌ Build payment reminder UI
8. ❌ Create customer trust level indicators
9. ❌ Implement credit limit warnings
10. ❌ Build credit history timeline

**Deliverables:**
- Complete credit tracking system
- Payment terms in Chichewa
- Automated reminders
- Trust level system
- Aging analysis

### Month 6: Multi-Currency & Advanced Features

#### Week 21-22: Multi-Currency System

**Backend Tasks:**
1. ❌ Create ExchangeRate model
2. ❌ Implement exchange rate management
3. ❌ Build currency conversion service
4. ❌ Create dual pricing (official & street rates)
5. ❌ Implement automatic price updates
6. ❌ Build historical rate tracking
7. ❌ Create currency snapshot on sales
8. ❌ Implement multi-currency reporting
9. ❌ Build exchange rate API integration (NBM)
10. ❌ Create currency management endpoints

**Frontend Tasks:**
1. ❌ Build exchange rate management page
2. ❌ Create currency selector in product form
3. ❌ Implement dual rate display
4. ❌ Build price in multiple currencies view
5. ❌ Create exchange rate alert system
6. ❌ Implement bulk price update by exchange rate
7. ❌ Build currency conversion calculator
8. ❌ Create historical rate charts
9. ❌ Implement currency indicators in dashboard
10. ❌ Add currency in all reports

**Deliverables:**
- Multi-currency product pricing
- Exchange rate management
- Dual rate support (official & street)
- Automatic price updates
- Currency conversion tools

#### Week 23-24: WhatsApp Integration & Notifications

**Backend Tasks:**
1. ❌ Integrate WhatsApp Business API
2. ❌ Implement receipt sharing via WhatsApp
3. ❌ Build payment reminder via WhatsApp
4. ❌ Create message templates
5. ❌ Implement SMS fallback (BulkSMS/Africa's Talking)
6. ❌ Build notification service
7. ❌ Create notification preferences
8. ❌ Implement notification queue
9. ❌ Build notification history
10. ❌ Create notification analytics

**Frontend Tasks:**
1. ❌ Build notification preferences page
2. ❌ Implement WhatsApp share buttons
3. ❌ Create message template editor
4. ❌ Build notification history view
5. ❌ Implement in-app notifications
6. ❌ Create notification center
7. ❌ Build notification settings
8. ❌ Implement notification badges
9. ❌ Create bulk messaging interface
10. ❌ Add notification test functionality

**Deliverables:**
- WhatsApp Business integration
- SMS notifications
- Payment reminders
- Receipt sharing
- Notification preferences

### Month 7: Multi-User & Permissions

#### Week 25-26: User Roles & Permissions

**Backend Tasks:**
1. ❌ Create Role and Permission models
2. ❌ Implement role-based access control (RBAC)
3. ❌ Build predefined roles (Owner, Manager, Cashier, Accountant)
4. ❌ Create custom role builder
5. ❌ Implement permission checking middleware
6. ❌ Build user invitation system
7. ❌ Create shop user management
8. ❌ Implement activity logging by user
9. ❌ Build user performance tracking
10. ❌ Create audit trail

**Frontend Tasks:**
1. ❌ Build team management page
2. ❌ Create role assignment interface
3. ❌ Implement custom role builder
4. ❌ Build permission matrix view
5. ❌ Create user invitation flow
6. ❌ Implement user activity feed
7. ❌ Build user performance dashboard
8. ❌ Create audit log viewer
9. ❌ Implement permission-based UI hiding
10. ❌ Add user switcher for multi-shop access

**Deliverables:**
- Multi-user support
- Role-based permissions
- User invitation system
- Activity logging
- Audit trail

#### Week 27-28: Advanced Reporting & Analytics

**Backend Tasks:**
1. ❌ Build advanced report generator
2. ❌ Implement custom date range reports
3. ❌ Create profit & loss statement
4. ❌ Build inventory valuation report
5. ❌ Implement product performance analysis
6. ❌ Create customer analysis report
7. ❌ Build staff performance report
8. ❌ Implement comparative reports
9. ❌ Create forecast projections
10. ❌ Build report scheduling

**Frontend Tasks:**
1. ❌ Build advanced reports dashboard
2. ❌ Create custom report builder
3. ❌ Implement interactive charts (drill-down)
4. ❌ Build comparison tools (periods, products)
5. ❌ Create forecast visualizations
6. ❌ Implement report filters and grouping
7. ❌ Build scheduled report management
8. ❌ Create report sharing capabilities
9. ❌ Implement report bookmarks
10. ❌ Add export in multiple formats

**Deliverables:**
- Advanced reporting suite
- Profit & Loss statements
- Custom reports
- Comparative analysis
- Scheduled reports

### Month 8: MRA Compliance & Tax Features

#### Week 29-30: MRA-Compliant Receipts

**Backend Tasks:**
1. ❌ Implement TPIN validation
2. ❌ Build VAT calculation engine
3. ❌ Create MRA-compliant receipt format
4. ❌ Implement sequential invoice numbering
5. ❌ Build VAT return report
6. ❌ Create withholding tax tracking
7. ❌ Implement tax category management
8. ❌ Build monthly tax summary
9. ❌ Create tax audit trail
10. ❌ Implement tax configuration

**Frontend Tasks:**
1. ❌ Build TPIN setup in shop settings
2. ❌ Create VAT configuration interface
3. ❌ Implement tax display on receipts
4. ❌ Build tax reports page
5. ❌ Create VAT return generator
6. ❌ Implement tax summary dashboard
7. ❌ Build withholding tax tracker
8. ❌ Create tax category management
9. ❌ Implement tax audit viewer
10. ❌ Add MRA export formats

**Deliverables:**
- MRA-compliant receipts
- VAT calculation
- Tax reports
- TPIN validation
- Monthly tax summaries

#### Week 31-32: Phase 2 Testing & Refinement

**Tasks:**
1. ❌ Comprehensive testing of all Phase 2 features
2. ❌ Performance testing with larger datasets
3. ❌ Multi-user concurrency testing
4. ❌ Security audit and fixes
5. ❌ UI/UX refinements based on feedback
6. ❌ Documentation updates
7. ❌ Bug fixes and optimization
8. ❌ Prepare for Phase 3
9. ❌ User training materials
10. ❌ Marketing materials preparation

**Deliverables:**
- Fully tested Phase 2 features
- Updated documentation
- Training materials
- Bug-free release

---

## Phase 3: Advanced Features (Months 9-12)

### Month 9: Mobile Money Integration

#### Week 33-34: Airtel Money Integration

**Backend Tasks:**
1. ❌ Research Airtel Money Business API
2. ❌ Set up Airtel Money developer account
3. ❌ Implement API authentication
4. ❌ Build payment initiation (C2B)
5. ❌ Implement payment confirmation webhook
6. ❌ Create payment status checking
7. ❌ Build automatic reconciliation
8. ❌ Implement refund processing
9. ❌ Create transaction logging
10. ❌ Build error handling and retry logic

**Frontend Tasks:**
1. ❌ Build Airtel Money connection setup
2. ❌ Create payment initiation UI
3. ❌ Implement payment status display
4. ❌ Build reconciliation dashboard
5. ❌ Create transaction history
6. ❌ Implement payment verification
7. ❌ Build customer phone input validation
8. ❌ Create payment confirmation flow
9. ❌ Implement payment retry UI
10. ❌ Add Airtel Money reports

**Deliverables:**
- Airtel Money API integration
- Automated payment processing
- Payment reconciliation
- Transaction history

#### Week 35-36: TNM Mpamba Integration

**Backend Tasks:**
1. ❌ Research TNM Mpamba API
2. ❌ Set up Mpamba developer account
3. ❌ Implement API authentication
4. ❌ Build payment processing
5. ❌ Create webhook handling
6. ❌ Implement status checking
7. ❌ Build reconciliation logic
8. ❌ Create unified payment interface
9. ❌ Implement fallback to manual
10. ❌ Build payment analytics

**Frontend Tasks:**
1. ❌ Build Mpamba connection setup
2. ❌ Create unified mobile money selector
3. ❌ Implement Mpamba payment flow
4. ❌ Build float (Ndola) tracker
5. ❌ Create unified transaction view
6. ❌ Implement payment method analytics
7. ❌ Build mobile money settings
8. ❌ Create commission tracking
9. ❌ Implement optimal method suggester
10. ❌ Add mobile money dashboard

**Deliverables:**
- TNM Mpamba integration
- Unified mobile money interface
- Float tracking
- Multi-provider support

### Month 10: MRA EFD Integration & Supplier Management

#### Week 37-38: MRA EFD Device Integration

**Backend Tasks:**
1. ❌ Research MRA EFD requirements
2. ❌ Study fiscal device protocols (SDK/APIs)
3. ❌ Implement EFD device communication
4. ❌ Build automatic fiscalization service
5. ❌ Create EFD queue management
6. ❌ Implement Z-report generation
7. ❌ Build EFD status monitoring
8. ❌ Create fallback mechanisms
9. ❌ Implement EFD data backup
10. ❌ Build MRA transmission service

**Frontend Tasks:**
1. ❌ Build EFD device setup wizard
2. ❌ Create device connection status
3. ❌ Implement automatic fiscalization
4. ❌ Build manual fiscalization option
5. ❌ Create EFD queue viewer
6. ❌ Implement fiscal receipt display
7. ❌ Build Z-report generator
8. ❌ Create device diagnostics page
9. ❌ Implement EFD error handling
10. ❌ Add fiscal compliance dashboard

**Deliverables:**
- MRA EFD device integration
- Automatic fiscalization
- Fiscal receipt generation
- MRA compliance tools
- Z-reports

#### Week 39-40: Supplier & Import Management

**Backend Tasks:**
1. ❌ Create Supplier model (completed in DB schema)
2. ❌ Implement supplier CRUD
3. ❌ Create PurchaseOrder model
4. ❌ Build PO creation and management
5. ❌ Implement import tracking
6. ❌ Create border clearance tracking
7. ❌ Build landed cost calculation
8. ❌ Implement document storage
9. ❌ Create clearing agent directory
10. ❌ Build supplier performance metrics

**Frontend Tasks:**
1. ❌ Build supplier directory
2. ❌ Create supplier profile pages
3. ❌ Implement PO creation wizard
4. ❌ Build import tracking dashboard
5. ❌ Create border clearance tracker
6. ❌ Implement landed cost calculator
7. ❌ Build document upload interface
8. ❌ Create clearing agent directory
9. ❌ Implement supplier performance view
10. ❌ Add import calendar/timeline

**Deliverables:**
- Supplier management
- Purchase order system
- Import tracking
- Landed cost calculation
- Border clearance tracking

### Month 11: Advanced Analytics & Automation

#### Week 41-42: Business Intelligence & Forecasting

**Backend Tasks:**
1. ❌ Build sales forecasting engine
2. ❌ Implement trend analysis
3. ❌ Create seasonal pattern detection
4. ❌ Build product demand prediction
5. ❌ Implement reorder automation
6. ❌ Create ABC analysis (product importance)
7. ❌ Build cohort analysis
8. ❌ Implement customer lifetime value calculation
9. ❌ Create predictive alerts
10. ❌ Build ML model integration framework

**Frontend Tasks:**
1. ❌ Build analytics dashboard
2. ❌ Create forecast visualizations
3. ❌ Implement trend charts
4. ❌ Build seasonal analysis view
5. ❌ Create product performance matrix
6. ❌ Implement ABC classification view
7. ❌ Build customer segmentation dashboard
8. ❌ Create predictive alerts center
9. ❌ Implement scenario planning tools
10. ❌ Add business intelligence reports

**Deliverables:**
- Sales forecasting
- Trend analysis
- Demand prediction
- Automated reordering
- Business intelligence dashboard

#### Week 43-44: Automation & Smart Features

**Backend Tasks:**
1. ❌ Build automated stock alerts
2. ❌ Implement smart reorder suggestions
3. ❌ Create automatic price adjustments (exchange rate)
4. ❌ Build payment reminder automation
5. ❌ Implement low stock auto-reorder
6. ❌ Create batch operations scheduler
7. ❌ Build automatic report generation
8. ❌ Implement data archiving
9. ❌ Create backup automation
10. ❌ Build health check system

**Frontend Tasks:**
1. ❌ Build automation center
2. ❌ Create automation rules builder
3. ❌ Implement alert preferences
4. ❌ Build scheduled task manager
5. ❌ Create automation history
6. ❌ Implement smart suggestions UI
7. ❌ Build notification preferences
8. ❌ Create automation analytics
9. ❌ Implement automation testing
10. ❌ Add automation dashboard

**Deliverables:**
- Automated alerts
- Smart reorder system
- Scheduled tasks
- Automatic reports
- Rule-based automation

### Month 12: Voice Commands & Final Polish

#### Week 45-46: Voice Commands (Chichewa)

**Backend Tasks:**
1. ❌ Research Chichewa speech recognition
2. ❌ Implement voice command API
3. ❌ Build command parsing service
4. ❌ Create voice-to-action mapping
5. ❌ Implement context-aware commands
6. ❌ Build voice response system
7. ❌ Create command history
8. ❌ Implement security for voice commands
9. ❌ Build voice command analytics
10. ❌ Create voice training data

**Frontend Tasks:**
1. ❌ Build voice input interface
2. ❌ Implement speech recognition
3. ❌ Create voice command list
4. ❌ Build voice feedback system
5. ❌ Implement listening indicator
6. ❌ Create command suggestions
7. ❌ Build voice settings
8. ❌ Implement hands-free mode
9. ❌ Create voice tutorial
10. ❌ Add voice shortcuts

**Deliverables:**
- Voice commands in Chichewa
- Hands-free operation
- Voice-based product search
- Voice sales entry
- Voice tutorials

#### Week 47-48: Phase 3 Final Testing & Launch Prep

**Tasks:**
1. ❌ Comprehensive system testing
2. ❌ Load testing (1000+ concurrent users)
3. ❌ Security penetration testing
4. ❌ Mobile money integration testing
5. ❌ EFD integration testing
6. ❌ Voice command accuracy testing
7. ❌ Performance optimization
8. ❌ Database optimization
9. ❌ API optimization
10. ❌ Final bug fixes

**Documentation & Training:**
1. ❌ Update all documentation
2. ❌ Create video tutorials for new features
3. ❌ Build training curriculum
4. ❌ Create certification program
5. ❌ Write API integration guides
6. ❌ Update FAQ
7. ❌ Create troubleshooting guides
8. ❌ Build support knowledge base
9. ❌ Prepare marketing materials
10. ❌ Plan launch event

**Deliverables:**
- Production-ready system
- Complete documentation
- Training materials
- Marketing collateral
- Launch strategy

---

## Phase 4: Ecosystem Expansion (Months 13-18)

### Month 13-14: Native Mobile Apps

#### React Native iOS/Android App

**Setup & Core Features:**
1. ❌ Initialize React Native project
2. ❌ Set up navigation (React Navigation)
3. ❌ Implement authentication
4. ❌ Build offline-first architecture
5. ❌ Create shared components
6. ❌ Implement biometric auth (fingerprint/face)
7. ❌ Build push notifications
8. ❌ Create camera integration (barcode)
9. ❌ Implement background sync
10. ❌ Build app-specific optimizations

**Platform-Specific Features:**
1. ❌ iOS: Apple Pay integration (if applicable)
2. ❌ Android: Google Pay integration
3. ❌ Platform-specific UI adjustments
4. ❌ App store optimization
5. ❌ Beta testing (TestFlight, Google Play Beta)
6. ❌ Performance monitoring (Firebase)
7. ❌ Crash reporting
8. ❌ Analytics integration
9. ❌ App store listings
10. ❌ Launch strategy

**Deliverables:**
- Native iOS app
- Native Android app
- App store presence
- Push notifications
- Biometric authentication

### Month 15-16: B2B Features & Marketplace

#### Supplier Marketplace

**Backend Tasks:**
1. ❌ Create marketplace platform
2. ❌ Implement supplier onboarding
3. ❌ Build product catalog syndication
4. ❌ Create order management system
5. ❌ Implement payment escrow
6. ❌ Build commission system
7. ❌ Create rating and review system
8. ❌ Implement dispute resolution
9. ❌ Build marketplace analytics
10. ❌ Create marketing tools for suppliers

**Frontend Tasks:**
1. ❌ Build marketplace storefront
2. ❌ Create product discovery
3. ❌ Implement search and filters
4. ❌ Build supplier profiles
5. ❌ Create order placement flow
6. ❌ Implement order tracking
7. ❌ Build review system
8. ❌ Create messaging system
9. ❌ Implement comparison tools
10. ❌ Add wishlist/favorites

**Deliverables:**
- Supplier marketplace
- B2B ordering platform
- Product discovery
- Order management
- Rating system

### Month 17-18: Enterprise Features & White Label

#### Enterprise Platform

**Features:**
1. ❌ Multi-location management
2. ❌ Franchise/chain support
3. ❌ Centralized inventory
4. ❌ Inter-branch transfers
5. ❌ Consolidated reporting
6. ❌ Regional management hierarchy
7. ❌ Custom workflows
8. ❌ Advanced integrations (ERP systems)
9. ❌ White-label option
10. ❌ Custom branding

**Infrastructure:**
1. ❌ Scalability improvements
2. ❌ Multi-tenancy architecture
3. ❌ Advanced security
4. ❌ Compliance certifications
5. ❌ SLA guarantees
6. ❌ Dedicated support
7. ❌ Custom hosting options
8. ❌ On-premise deployment
9. ❌ Enterprise SLAs
10. ❌ Priority support system

**Deliverables:**
- Enterprise platform
- Multi-location support
- White-label capability
- Advanced integrations
- Enterprise-grade infrastructure

---

## Testing Strategy

### Testing Pyramid

```
           /\
          /E2E\         End-to-End Tests (10%)
         /------\
        /  API   \      Integration Tests (30%)
       /----------\
      /   Unit     \    Unit Tests (60%)
     /--------------\
```

### Unit Testing

**Backend (PHP/Laravel):**
- Framework: PHPUnit
- Coverage target: 80%+
- Test all models, services, and utilities
- Mock external dependencies
- Test validation rules
- Test business logic

**Example:**
```php
// tests/Unit/SalesServiceTest.php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SalesService;
use App\Models\Product;
use App\Models\Sale;

class SalesServiceTest extends TestCase
{
    public function test_can_create_sale_with_valid_data()
    {
        $product = Product::factory()->create([
            'selling_price' => 10000,
            'quantity' => 100
        ]);

        $saleData = [
            'shop_id' => $this->shop->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 10000
                ]
            ],
            'payment_methods' => [
                ['method' => 'cash', 'amount' => 20000]
            ]
        ];

        $sale = $this->salesService->createSale($saleData);

        $this->assertEquals(20000, $sale->total_amount);
        $this->assertEquals(98, $product->fresh()->quantity);
    }

    public function test_stock_deduction_fails_if_insufficient()
    {
        $this->expectException(\App\Exceptions\InsufficientStockException::class);

        $product = Product::factory()->create(['quantity' => 5]);

        $this->salesService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10]
            ]
        ]);
    }
}
```

**Frontend (React/TypeScript):**
- Framework: Vitest + React Testing Library
- Coverage target: 70%+
- Test components, hooks, and utilities
- Test user interactions
- Test edge cases

**Example:**
```typescript
// src/features/sales/components/ProductSelector.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { ProductSelector } from './ProductSelector';

describe('ProductSelector', () => {
  it('searches products by name', async () => {
    render(<ProductSelector onSelect={jest.fn()} />);
    
    const searchInput = screen.getByPlaceholderText('Search products...');
    fireEvent.change(searchInput, { target: { value: 'Samsung' } });
    
    const product = await screen.findByText('Samsung Galaxy A14');
    expect(product).toBeInTheDocument();
  });

  it('calls onSelect when product is clicked', async () => {
    const onSelect = jest.fn();
    render(<ProductSelector onSelect={onSelect} />);
    
    const product = await screen.findByText('Samsung Galaxy A14');
    fireEvent.click(product);
    
    expect(onSelect).toHaveBeenCalledWith(expect.objectContaining({
      name: 'Samsung Galaxy A14'
    }));
  });
});
```

### Integration Testing

**API Integration Tests:**
- Test complete API workflows
- Test authentication flows
- Test CRUD operations
- Test business logic integration
- Test database transactions

**Example:**
```php
// tests/Feature/SalesApiTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shop;
use App\Models\Product;

class SalesApiTest extends TestCase
{
    public function test_can_create_sale_via_api()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'selling_price' => 50000,
            'quantity' => 100
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/sales', [
                'shop_id' => $shop->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'unit_price' => 50000
                    ]
                ],
                'payment_methods' => [
                    ['method' => 'cash', 'amount' => 100000]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'sale_number',
                    'total_amount',
                    'items'
                ]
            ]);

        $this->assertDatabaseHas('sales', [
            'shop_id' => $shop->id,
            'total_amount' => 100000
        ]);
    }
}
```

### End-to-End Testing

**Framework:** Playwright or Cypress
**Coverage:** Critical user flows

**Key Scenarios to Test:**
1. Complete user registration → shop setup → first sale
2. Offline sale → sync when online
3. Credit sale → payment recording → receipt generation
4. Product search → add to cart → checkout
5. Multiple payment methods in single sale
6. Daily closing → report generation

**Example Playwright Test:**
```typescript
// e2e/complete-sale-flow.spec.ts
import { test, expect } from '@playwright/test';

test('complete sale flow', async ({ page }) => {
  // Login
  await page.goto('http://localhost:5173/login');
  await page.fill('[name="email"]', 'test@example.com');
  await page.fill('[name="password"]', 'password');
  await page.click('button[type="submit"]');
  
  await expect(page).toHaveURL('/dashboard');
  
  // Navigate to POS
  await page.click('text=New Sale');
  
  // Search and add product
  await page.fill('[placeholder="Search products..."]', 'Samsung');
  await page.click('text=Samsung Galaxy A14');
  
  // Adjust quantity
  await page.fill('[name="quantity"]', '2');
  
  // Select payment method
  await page.click('text=Cash');
  
  // Complete sale
  await page.click('button:has-text("Complete Sale")');
  
  // Verify success
  await expect(page.locator('text=Sale completed successfully')).toBeVisible();
  
  // Verify receipt
  await expect(page.locator('text=SALE-')).toBeVisible();
});
```

### Performance Testing

**Tools:** Apache JMeter, k6, Artillery

**Scenarios:**
1. 100 concurrent users creating sales
2. 1000 products loaded in POS
3. Large dataset reports (1 year of data)
4. Sync with 500 pending transactions
5. Multiple shops on single database

**Example k6 Script:**
```javascript
// performance/sales-load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 50 },   // Ramp up
    { duration: '5m', target: 50 },   // Stay at 50 users
    { duration: '2m', target: 100 },  // Ramp to 100
    { duration: '5m', target: 100 },  // Stay at 100
    { duration: '2m', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests under 500ms
    http_req_failed: ['rate<0.01'],   // Less than 1% errors
  },
};

export default function() {
  const token = 'your_auth_token';
  
  const payload = JSON.stringify({
    shop_id: 'shop-uuid',
    items: [
      {
        product_id: 'product-uuid',
        quantity: 1,
        unit_price: 50000
      }
    ],
    payment_methods: [
      { method: 'cash', amount: 50000 }
    ]
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
  };

  const res = http.post('http://api.tiwinebiz.mw/v1/sales', payload, params);
  
  check(res, {
    'status is 201': (r) => r.status === 201,
    'response time OK': (r) => r.timings.duration < 500,
  });

  sleep(1);
}
```

### Offline Testing

**Scenarios:**
1. Complete sale while offline
2. Sync after 100 offline sales
3. Conflict resolution (same product edited online and offline)
4. Network interruption during sync
5. Partial sync recovery

**Testing Approach:**
- Use Chrome DevTools Network Throttling
- Simulate offline mode programmatically
- Test with real device airplane mode
- Test with intermittent connectivity
- Verify data integrity after sync

---

## Deployment Strategy

### Development Workflow

```
Developer → Git Push → GitHub Actions → Run Tests → Build → Deploy to Staging
                                                                      ↓
                                                            Manual QA/Testing
                                                                      ↓
                                                            Approve & Deploy to Production
```

### Environments

#### 1. Local Development
- Docker containers
- Hot reload
- Debug mode enabled
- Local database

#### 2. Staging
- URL: `https://staging.tiwinebiz.mw`
- API: `https://api-staging.tiwinebiz.mw`
- Mirror of production
- Test data
- Latest features

#### 3. Production
- URL: `https://app.tiwinebiz.mw`
- API: `https://api.tiwinebiz.mw`
- Live data
- Performance monitoring
- Auto-scaling enabled

### Infrastructure Setup (DigitalOcean)

**VPS Configuration:**
```bash
# Droplet: 4GB RAM, 2 vCPUs, 80GB SSD
# Location: Cape Town (closest to Malawi)
# OS: Ubuntu 22.04 LTS

# Initial setup
apt update && apt upgrade -y
apt install -y nginx postgresql-15 redis-server php8.2-fpm \
  php8.2-pgsql php8.2-redis php8.2-mbstring php8.2-xml \
  certbot python3-certbot-nginx supervisor git

# Configure PostgreSQL
sudo -u postgres createuser tiwinebiz
sudo -u postgres createdb tiwinebiz -O tiwinebiz

# Configure Nginx
# See nginx config below

# Install Node.js 18 LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

**Nginx Configuration:**
```nginx
# /etc/nginx/sites-available/tiwinebiz-api
server {
    listen 80;
    server_name api.tiwinebiz.mw;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.tiwinebiz.mw;

    ssl_certificate /etc/letsencrypt/live/api.tiwinebiz.mw/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.tiwinebiz.mw/privkey.pem;

    root /var/www/tiwinebiz-api/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Frontend (Static files via Cloudflare)
server {
    listen 80;
    server_name app.tiwinebiz.mw;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.tiwinebiz.mw;

    ssl_certificate /etc/letsencrypt/live/app.tiwinebiz.mw/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.tiwinebiz.mw/privkey.pem;

    root /var/www/tiwinebiz-web/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### CI/CD Pipeline (GitHub Actions)

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: tiwinebiz_test
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      
      redis:
        image: redis:7-alpine
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pgsql, redis
      
      - name: Install dependencies
        run: |
          cd backend
          composer install --no-interaction --prefer-dist
      
      - name: Run tests
        run: |
          cd backend
          php artisan test --parallel
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: tiwinebiz_test
          DB_USERNAME: postgres
          DB_PASSWORD: postgres

  deploy-backend:
    needs: test
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          script: |
            cd /var/www/tiwinebiz-api
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo systemctl reload php8.2-fpm
            sudo systemctl reload nginx

  build-frontend:
    needs: test
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
          cache-dependency-path: 'frontend/package-lock.json'
      
      - name: Install dependencies
        run: |
          cd frontend
          npm ci
      
      - name: Build
        run: |
          cd frontend
          npm run build
        env:
          VITE_API_URL: https://api.tiwinebiz.mw/v1
      
      - name: Deploy to server
        uses: appleboy/scp-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          source: "frontend/dist/*"
          target: "/var/www/tiwinebiz-web/"
          strip_components: 2
```

### Database Backup Strategy

```bash
# Automated daily backups
# /etc/cron.d/tiwinebiz-backup

# Daily backup at 2 AM
0 2 * * * postgres /usr/local/bin/backup-tiwinebiz-db.sh

# Weekly full backup (Sunday 3 AM)
0 3 * * 0 postgres /usr/local/bin/backup-tiwinebiz-full.sh
```

**Backup Script:**
```bash
#!/bin/bash
# /usr/local/bin/backup-tiwinebiz-db.sh

BACKUP_DIR="/var/backups/tiwinebiz"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="tiwinebiz"

# Create backup
pg_dump $DB_NAME | gzip > $BACKUP_DIR/tiwinebiz_$DATE.sql.gz

# Upload to DigitalOcean Spaces (S3-compatible)
s3cmd put $BACKUP_DIR/tiwinebiz_$DATE.sql.gz \
  s3://tiwinebiz-backups/database/

# Keep only last 7 days locally
find $BACKUP_DIR -type f -mtime +7 -delete

# Verify backup integrity
if [ $? -eq 0 ]; then
    echo "Backup successful: tiwinebiz_$DATE.sql.gz"
else
    echo "Backup failed!" | mail -s "TiwineBiz Backup Failed" admin@tiwinebiz.mw
fi
```

### Monitoring Setup

**Server Monitoring (Uptime Robot):**
- API endpoint checks every 5 minutes
- Alert if down for >2 minutes
- Check from multiple locations

**Application Monitoring (Sentry):**
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'traces_sample_rate' => 0.2,
    'profiles_sample_rate' => 0.2,
];
```

**Performance Monitoring (New Relic/Blackfire):**
- Track slow queries
- Monitor API response times
- Identify bottlenecks

**Log Management:**
```bash
# Supervisor config for Laravel Queue Worker
# /etc/supervisor/conf.d/tiwinebiz-queue.conf

[program:tiwinebiz-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tiwinebiz-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/tiwinebiz/queue.log
stopwaitsecs=3600
```

### Rollback Strategy

```bash
# Quick rollback script
#!/bin/bash
# /usr/local/bin/rollback-tiwinebiz.sh

DEPLOY_DIR="/var/www/tiwinebiz-api"
BACKUP_DIR="/var/backups/tiwinebiz/releases"

# Get previous release
PREVIOUS=$(ls -t $BACKUP_DIR | head -n 2 | tail -n 1)

echo "Rolling back to: $PREVIOUS"

# Stop services
sudo systemctl stop php8.2-fpm
sudo supervisorctl stop tiwinebiz-queue:*

# Rollback code
cd $DEPLOY_DIR
git reset --hard $PREVIOUS

# Rollback database (if needed)
# pg_restore -d tiwinebiz $BACKUP_DIR/$PREVIOUS/database.sql

# Restart services
composer install --no-dev
php artisan migrate:rollback
php artisan config:cache
sudo systemctl start php8.2-fpm
sudo supervisorctl start tiwinebiz-queue:*

echo "Rollback complete"
```

---

## Security Implementation

### Authentication Security

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

**Implementation:**
```php
// app/Rules/StrongPassword.php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value);
    }

    public function message()
    {
        return 'The password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
    }
}
```

**JWT Token Management:**
```php
// config/sanctum.php
return [
    'expiration' => 60 * 24, // 24 hours
    'token_prefix' => 'Bearer',
];

// Refresh tokens before expiry
// app/Http/Middleware/RefreshToken.php
<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class RefreshToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->user()->currentAccessToken();
        
        // Refresh if token expires in less than 30 minutes
        if ($token->expires_at->diffInMinutes(now()) < 30) {
            $newToken = $request->user()->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;
            
            return $next($request)->header('X-New-Token', $newToken);
        }
        
        return $next($request);
    }
}
```

### API Security

**Rate Limiting:**
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

// config/sanctum.php
'limiter' => function (Request $request) {
    $user = $request->user();
    
    if (!$user) {
        return Limit::perMinute(10);
    }
    
    // Rate limit based on subscription tier
    return match($user->shop->subscription_tier) {
        'free' => Limit::perMinute(60),
        'business' => Limit::perMinute(120),
        'professional' => Limit::perMinute(300),
        'enterprise' => Limit::none(),
        default => Limit::perMinute(60),
    };
},
```

**Input Validation:**
```php
// app/Http/Requests/CreateSaleRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSaleRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create-sale', $this->shop);
    }

    public function rules()
    {
        return [
            'shop_id' => 'required|uuid|exists:shops,id',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*.method' => 'required|in:cash,airtel_money,mpamba,bank_transfer',
            'payment_methods.*.amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ];
    }
}
```

**SQL Injection Prevention:**
- Always use Eloquent ORM or Query Builder
- Never use raw queries with user input
- Use parameter binding for raw queries

**XSS Prevention:**
- Blade templating auto-escapes output
- Use `{!! !!}` only for trusted content
- Sanitize user input on frontend

**CSRF Protection:**
- Laravel's CSRF middleware enabled by default
- Include CSRF token in all forms
- API uses Sanctum tokens (no CSRF needed)

### Data Encryption

**At Rest:**
```php
// Encrypt sensitive fields
// app/Models/Customer.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $casts = [
        'phone' => 'encrypted',
        'email' => 'encrypted',
        'address' => 'encrypted',
    ];
}
```

**In Transit:**
- All API calls over HTTPS (enforced)
- TLS 1.3
- Strong cipher suites

**Payment Data:**
- Never store full card numbers
- PCI DSS compliance for payment processing
- Tokenize sensitive payment information

### Access Control

**Role-Based Access Control (RBAC):**
```php
// database/seeders/RoleSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'owner',
                'display_name' => 'Owner',
                'is_system_role' => true,
                'permissions' => ['*'], // All permissions
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'is_system_role' => true,
                'permissions' => [
                    'view_sales', 'create_sales', 'view_inventory',
                    'manage_inventory', 'view_customers', 'manage_customers',
                    'view_reports', 'manage_users'
                ],
            ],
            [
                'name' => 'cashier',
                'display_name' => 'Cashier',
                'is_system_role' => true,
                'permissions' => [
                    'view_sales', 'create_sales', 'view_inventory',
                    'view_customers'
                ],
            ],
            [
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'is_system_role' => true,
                'permissions' => [
                    'view_sales', 'view_inventory', 'view_customers',
                    'view_reports', 'export_reports'
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
```

**Permission Middleware:**
```php
// app/Http/Middleware/CheckPermission.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!$request->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}

// Usage in routes
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('permission:manage_inventory');
```

### Audit Trail

```php
// app/Observers/ProductObserver.php
<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ActivityLog;

class ProductObserver
{
    public function created(Product $product)
    {
        ActivityLog::create([
            'shop_id' => $product->shop_id,
            'user_id' => auth()->id(),
            'action' => 'product.created',
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'new_values' => $product->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function updated(Product $product)
    {
        ActivityLog::create([
            'shop_id' => $product->shop_id,
            'user_id' => auth()->id(),
            'action' => 'product.updated',
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'old_values' => $product->getOriginal(),
            'new_values' => $product->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

## Performance Optimization

### Database Optimization

**Indexes:**
```sql
-- High-impact indexes
CREATE INDEX idx_sales_shop_date ON sales(shop_id, sale_date);
CREATE INDEX idx_products_shop_active ON products(shop_id, is_active, is_deleted);
CREATE INDEX idx_stock_movements_product_date ON stock_movements(product_id, created_at);
CREATE INDEX idx_customers_shop_phone ON customers(shop_id, phone);

-- Composite indexes for common queries
CREATE INDEX idx_credits_shop_status_due ON credits(shop_id, status, due_date);
```

**Query Optimization:**
```php
// Bad: N+1 query
$sales = Sale::all();
foreach ($sales as $sale) {
    echo $sale->customer->name; // Query for each sale
}

// Good: Eager loading
$sales = Sale::with('customer', 'items.product')->get();
foreach ($sales as $sale) {
    echo $sale->customer->name; // No additional queries
}

// Pagination for large datasets
$products = Product::where('shop_id', $shopId)
    ->paginate(50);

// Count optimization
$count = Product::where('shop_id', $shopId)->count(); // Fast
// vs
$count = Product::where('shop_id', $shopId)->get()->count(); // Slow
```

**Database Connection Pooling:**
```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'tiwinebiz'),
    'username' => env('DB_USERNAME', 'postgres'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'schema' => 'public',
    'sslmode' => 'prefer',
    'pool' => [
        'min' => 2,
        'max' => 10,
    ],
],
```

### API Response Caching

```php
// app/Http/Controllers/Api/DashboardController.php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $shopId = $request->user()->current_shop_id;
        
        // Cache for 5 minutes
        return Cache::remember("dashboard.summary.{$shopId}", 300, function() use ($shopId) {
            return [
                'today_sales' => $this->getTodaySales($shopId),
                'low_stock_count' => $this->getLowStockCount($shopId),
                'pending_credits' => $this->getPendingCredits($shopId),
                'top_products' => $this->getTopProducts($shopId),
            ];
        });
    }
}
```

### Frontend Performance

**Code Splitting:**
```typescript
// src/app/routes.tsx
import { lazy } from 'react';

const Dashboard = lazy(() => import('@/features/dashboard/Dashboard'));
const Products = lazy(() => import('@/features/inventory/Products'));
const Sales = lazy(() => import('@/features/sales/Sales'));

export const routes = [
  { path: '/', element: <Dashboard /> },
  { path: '/products', element: <Products /> },
  { path: '/sales', element: <Sales /> },
];
```

**Image Optimization:**
```typescript
// Lazy load images
import { LazyLoadImage } from 'react-lazy-load-image-component';

<LazyLoadImage
  src={product.image}
  alt={product.name}
  effect="blur"
  threshold={100}
/>

// Use WebP format
<picture>
  <source srcSet={`${product.image}.webp`} type="image/webp" />
  <img src={product.image} alt={product.name} />
</picture>
```

**React Query Optimizations:**
```typescript
// src/lib/queryClient.ts
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      cacheTime: 10 * 60 * 1000, // 10 minutes
      retry: 2,
      refetchOnWindowFocus: false,
      refetchOnMount: false,
    },
  },
});
```

**Virtual Scrolling for Large Lists:**
```typescript
// Use react-virtual for large product lists
import { useVirtualizer } from '@tanstack/react-virtual';

function ProductList({ products }) {
  const parentRef = useRef<HTMLDivElement>(null);
  
  const virtualizer = useVirtualizer({
    count: products.length,
    getScrollElement: () => parentRef.current,
    estimateSize: () => 80, // Estimated row height
    overscan: 5,
  });

  return (
    <div ref={parentRef} style={{ height: '600px', overflow: 'auto' }}>
      <div style={{ height: `${virtualizer.getTotalSize()}px` }}>
        {virtualizer.getVirtualItems().map((virtualRow) => (
          <div key={virtualRow.index} style={{ height: `${virtualRow.size}px` }}>
            <ProductRow product={products[virtualRow.index]} />
          </div>
        ))}
      </div>
    </div>
  );
}
```

### Service Worker Caching Strategy

```typescript
// src/workers/service-worker.ts
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst, StaleWhileRevalidate } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';

// Precache static assets
precacheAndRoute(self.__WB_MANIFEST);

// API calls - Network first, fallback to cache
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/'),
  new NetworkFirst({
    cacheName: 'api-cache',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 100,
        maxAgeSeconds: 60 * 60, // 1 hour
      }),
    ],
  })
);

// Images - Cache first
registerRoute(
  ({ request }) => request.destination === 'image',
  new CacheFirst({
    cacheName: 'images',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 500,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
      }),
    ],
  })
);

// Static assets - Stale while revalidate
registerRoute(
  ({ request }) =>
    request.destination === 'script' ||
    request.destination === 'style' ||
    request.destination === 'font',
  new StaleWhileRevalidate({
    cacheName: 'static-resources',
  })
);
```

---

## Monitoring and Maintenance

### Application Monitoring

**Error Tracking (Sentry):**
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    
    // Capture 100% of transactions for performance monitoring
    'traces_sample_rate' => 1.0,
    
    // Sample rate for profiling
    'profiles_sample_rate' => 0.5,
    
    // Environments to track
    'environment' => env('APP_ENV', 'production'),
    
    // Custom tags
    'tags' => [
        'deployment' => env('DEPLOYMENT_ID'),
    ],
];

// Custom error context
if (app()->bound('sentry')) {
    app('sentry')->configureScope(function ($scope) {
        $scope->setUser([
            'id' => auth()->id(),
            'email' => auth()->user()?->email,
            'shop_id' => auth()->user()?->current_shop_id,
        ]);
    });
}
```

**Health Checks:**
```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => checkDatabase(),
        'redis' => checkRedis(),
        'queue' => checkQueue(),
        'storage' => checkStorage(),
    ];
    
    $allHealthy = !in_array(false, $checks);
    
    return response()->json([
        'status' => $allHealthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $allHealthy ? 200 : 503);
});

function checkDatabase()
{
    try {
        DB::connection()->getPdo();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

function checkRedis()
{
    try {
        Redis::ping();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}
```

### Performance Metrics

**Key Metrics to Track:**
1. Response Times
   - API: p50, p95, p99
   - Frontend: Time to First Byte (TTFB), First Contentful Paint (FCP), Largest Contentful Paint (LCP)

2. Error Rates
   - 4xx errors (client errors)
   - 5xx errors (server errors)
   - JavaScript errors (frontend)

3. Business Metrics
   - Active users (DAU, WAU, MAU)
   - Sales per day
   - Average sale value
   - Sync success rate
   - Offline usage percentage

4. Infrastructure Metrics
   - CPU usage
   - Memory usage
   - Disk I/O
   - Network bandwidth
   - Database connections
   - Queue job processing time

### Maintenance Tasks

**Daily:**
- Monitor error rates in Sentry
- Check server health (CPU, memory, disk)
- Review failed queue jobs
- Check backup completion

**Weekly:**
- Review slow query log
- Analyze user feedback
- Check for security updates
- Review API usage patterns
- Database maintenance (VACUUM on PostgreSQL)

**Monthly:**
- Update dependencies (security patches)
- Review and optimize database indexes
- Capacity planning review
- Performance audit
- Security audit

**Quarterly:**
- Major dependency updates
- Infrastructure review
- Disaster recovery drill
- Penetration testing
- User satisfaction survey

---

## Conclusion

This implementation plan provides a comprehensive roadmap for building TiwineBiz from ground up to a production-ready, scalable system. The phased approach allows for iterative development, testing, and refinement while delivering value at each stage.

### Key Success Factors:

1. **Offline-First Architecture**: Critical for Malawi's infrastructure
2. **Localization**: Chichewa language and cultural considerations
3. **Mobile Money Integration**: Essential for payment processing
4. **MRA Compliance**: Non-negotiable for legal operation
5. **User Experience**: Intuitive for low-tech-literacy users
6. **Performance**: Works well on low-end devices and slow networks
7. **Security**: Protects sensitive business data
8. **Scalability**: Can grow from 1 shop to 10,000+ shops

### Next Steps After Implementation:

1. Pilot program with 20-50 shops
2. Gather feedback and iterate
3. Scale marketing efforts
4. Build partnerships (mobile money providers, business associations)
5. Continuous improvement based on user needs
6. Regional expansion (Zambia, Zimbabwe)

**Remember:** This is a living document. As you build and learn, update this plan to reflect new insights, technical decisions, and market realities.

Good luck building TiwineBiz! 🇲🇼🚀
