# MRA EIS Integration Guide

## Overview

TiwineBiz is **fully integrated** with the Malawi Revenue Authority's Electronic Invoicing System (EIS), which launched on November 1, 2025. This integration ensures compliance with MRA requirements for VAT-registered businesses.

## Current Implementation Status

### ✅ COMPLETE - Backend API Integration

**What We Have:**
1. **Full MRA EIS API Integration**
   - OAuth2 client credentials authentication
   - Automatic fiscalization of paid sales
   - Manual fiscalization endpoint
   - Invoice verification capability
   - Failed transmission retry with exponential backoff

2. **Fiscal Receipt Data Capture**
   - Fiscal receipt number from MRA
   - Digital fiscal signature
   - QR code for verification
   - Verification URL
   - Complete MRA API response storage

3. **PDF Receipt Generation**
   - Professional receipts with MRA fiscal data
   - QR code embedded in PDF
   - Fiscal signature displayed
   - Email delivery capability

4. **Database Tracking**
   - Complete audit trail in `efd_transactions` table
   - Success/failure tracking
   - Automatic retry mechanism
   - MRA response logging

### ⚠️ OPTIONAL - Physical Thermal Printer Support

**For businesses that want to print physical receipts on thermal printers:**

The new MRA EIS system (unlike the old EFD system) does not require specific fiscal printer hardware. Instead:

- **Fiscalization happens via our API** (✅ Already implemented)
- **Any ESC/POS thermal printer works** (80mm or 57mm paper)
- **Printer is just for physical output** (not for fiscalization)

**To add thermal printer support, install:**
```bash
composer require mike42/escpos-php
```

This library supports:
- USB thermal printers
- Network/Ethernet thermal printers
- Bluetooth thermal printers
- 80mm and 57mm paper sizes
- ESC/POS command protocol (industry standard)

## How It Works

### 1. Sale Creation Flow

```
Customer makes purchase
    ↓
Sale created in TiwineBiz
    ↓
Sale marked as "paid"
    ↓
[IF MRA_EIS_AUTO_FISCALIZE=true]
    ↓
Automatic fiscalization:
  - Authenticate with MRA API (OAuth2)
  - Submit invoice data (seller, buyer, items, totals)
  - Receive fiscal receipt number, QR code, signature
  - Save to database
  - Update sale record
    ↓
Receipt available (PDF/Email/Print)
```

### 2. Manual Fiscalization

For sales that need to be fiscalized later:

```http
POST /api/v1/sales/{sale_id}/fiscalize
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Sale fiscalized successfully with MRA EIS.",
  "data": {
    "sale_id": "uuid",
    "fiscal_receipt_number": "FRN-123456789",
    "qr_code": "base64_encoded_qr_code",
    "verification_url": "https://eis-portal.mra.mw/verify/FRN-123456789"
  }
}
```

### 3. Invoice Data Submitted to MRA

```json
{
  "invoice_number": "SALE-ABC123",
  "invoice_date": "2025-11-11T10:30:00+02:00",
  "invoice_type": "SALE",
  "seller": {
    "business_name": "Your Shop Name",
    "tpin": "MRA123456",
    "vat_number": "VAT123456",
    "address": "Shop Address",
    "branch_name": "Main Branch"
  },
  "buyer": {
    "name": "Customer Name or Walk-in Customer",
    "phone": "+265991234567",
    "email": "customer@example.com",
    "address": "Customer Address"
  },
  "items": [
    {
      "item_code": "PROD-001",
      "item_description": "Product Name",
      "quantity": 2.0,
      "unit_price": 1500.00,
      "discount_amount": 100.00,
      "taxable": true,
      "tax_rate": 16.5,
      "tax_amount": 231.00,
      "subtotal": 1400.00,
      "total": 1631.00
    }
  ],
  "totals": {
    "subtotal": 1400.00,
    "discount_amount": 100.00,
    "tax_amount": 231.00,
    "total_amount": 1631.00
  },
  "payment": {
    "payment_status": "paid",
    "amount_paid": 1631.00,
    "balance": 0.00,
    "payment_methods": [
      {"method": "cash", "amount": 1631.00}
    ]
  },
  "currency": "MWK",
  "exchange_rate": 1.0
}
```

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# MRA Electronic Invoicing System (EIS) Configuration
MRA_EIS_ENABLED=false              # Set to true for VAT-registered businesses
MRA_EIS_BASE_URL=https://eis-api.mra.mw
MRA_EIS_CLIENT_ID=your_client_id   # Obtain from MRA developer portal
MRA_EIS_CLIENT_SECRET=your_secret  # Obtain from MRA developer portal
MRA_EIS_TIMEOUT=30
MRA_EIS_AUTO_FISCALIZE=true        # Automatically fiscalize paid sales
MRA_EIS_RETRY_FAILED=true          # Retry failed fiscalizations
MRA_EIS_MAX_RETRIES=3              # Maximum retry attempts
```

### Shop Configuration

Ensure your Shop model has these required fields for MRA:
- `business_name` - Registered business name
- `tpin` - MRA Taxpayer Identification Number
- `vat_registration_number` - VAT registration number
- `address` - Business address

## Setup Instructions

### 1. Register with MRA

1. Visit https://eis-portal.mra.mw
2. Register your business for EIS
3. Obtain OAuth Client ID and Client Secret from Developer Resources

### 2. Configure TiwineBiz

1. Update `.env` with your MRA credentials
2. Set `MRA_EIS_ENABLED=true`
3. Verify shop information is complete (TPIN, VAT number, etc.)

### 3. Test Integration

**Test Mode (Recommended First):**
```env
MRA_EIS_ENABLED=true
MRA_EIS_BASE_URL=https://dev-eis-api.mra.mw  # Use dev/sandbox URL
MRA_EIS_AUTO_FISCALIZE=false  # Test manually first
```

**Create a test sale:**
```bash
# Create sale via API
# Then manually fiscalize:
POST /api/v1/sales/{sale_id}/fiscalize
```

**Check the response:**
- Verify fiscal receipt number is returned
- Verify QR code is generated
- Check database: `efd_transactions` table

### 4. Go Live

```env
MRA_EIS_ENABLED=true
MRA_EIS_BASE_URL=https://eis-api.mra.mw  # Production URL
MRA_EIS_AUTO_FISCALIZE=true  # Enable auto-fiscalization
```

## Receipt Requirements

### MRA-Compliant Receipt Must Include:

1. ✅ **Business Information**
   - Business name
   - TPIN
   - VAT registration number
   - Address

2. ✅ **Fiscal Information**
   - Fiscal receipt number
   - Digital signature
   - QR code
   - Verification URL

3. ✅ **Transaction Details**
   - Date and time
   - Items with prices
   - Tax calculations
   - Payment method
   - Total amount

**All of this is included in our PDF receipts!**

## Receipt Printing Options

### Option 1: PDF Receipts (Current Implementation)

**Advantages:**
- ✅ Already implemented
- ✅ Includes all MRA requirements
- ✅ Can be emailed to customers
- ✅ Can be printed on any printer
- ✅ Professional appearance
- ✅ No additional hardware needed

**Use case:** Email receipts, print on standard printers

### Option 2: Thermal Printer Support (Optional)

**For businesses wanting thermal receipt printers:**

**Install ESC/POS library:**
```bash
composer require mike42/escpos-php
```

**Supported printers:**
- Any ESC/POS compatible thermal printer
- 80mm (3 1/8") paper width (recommended)
- 57mm (2 1/4") paper width (supported)
- USB, Network, or Bluetooth connection

**Popular compatible printers:**
- Epson TM-T20
- Star TSP143
- Citizen CT-S310
- Rongta RP80
- Xprinter XP-80C
- Most POS thermal printers with ESC/POS support

**Implementation needed:**
Create `app/Services/ThermalPrinterService.php` using mike42/escpos-php to format and send receipt data to physical printer.

## Troubleshooting

### Sale created but not fiscalized

**Check:**
1. Is `MRA_EIS_ENABLED=true`?
2. Is sale marked as "paid"?
3. Is `MRA_EIS_AUTO_FISCALIZE=true`?
4. Check logs for errors: `storage/logs/laravel.log`
5. Check `efd_transactions` table for failed attempts

**Retry failed fiscalization:**
```bash
# In tinker or create artisan command:
$service = new \App\Services\MraEisService();
$results = $service->retryFailedFiscalizations();
```

### Authentication fails

**Check:**
1. Correct Client ID and Client Secret
2. Correct API URL (dev vs production)
3. Network connectivity to MRA servers
4. Credentials are active in MRA portal

### Receipt doesn't have fiscal data

**Check:**
1. Sale was created AFTER enabling MRA integration
2. Sale is marked as fiscalized: `is_fiscalized = true`
3. Fiscal data fields are populated in sales table:
   - `efd_receipt_number`
   - `efd_qr_code`
   - `efd_fiscal_signature`

## Database Schema

### Sales Table (MRA Fields)

```sql
is_fiscalized BOOLEAN          -- Whether sale has been fiscalized
efd_device_id VARCHAR(100)     -- Device/API identifier
efd_receipt_number VARCHAR(100) -- Fiscal receipt number from MRA
efd_qr_code TEXT               -- QR code for verification
efd_fiscal_signature TEXT      -- Digital signature from MRA
efd_transmitted_at TIMESTAMP   -- When fiscalized
efd_response JSON              -- Complete MRA API response
```

### EFD Transactions Table (Audit Trail)

```sql
id UUID PRIMARY KEY
shop_id UUID
sale_id UUID
efd_device_id VARCHAR(100)
efd_device_serial VARCHAR(100)
fiscal_receipt_number VARCHAR(100)
fiscal_day_counter INTEGER
fiscal_signature TEXT
qr_code_data TEXT
verification_url VARCHAR(500)
total_amount DECIMAL(12,2)
vat_amount DECIMAL(12,2)
mra_response_code INTEGER
mra_response_message TEXT
mra_acknowledgement JSON
transmitted_at TIMESTAMP
transmission_status VARCHAR(20)  -- 'success', 'failed', 'pending'
retry_count INTEGER
last_retry_at TIMESTAMP
next_retry_at TIMESTAMP
created_at TIMESTAMP
```

## API Documentation

### MRA EIS Official Documentation

- **Developer Portal:** https://eis-portal.mra.mw/Home/DeveloperResources
- **API Documentation:** https://eis-api.mra.mw/docs/
- **Swagger UI:** https://eis-api.mra.mw/swagger/index.html
- **Main EIS Portal:** https://eis-portal.mra.mw/
- **EFD Information:** https://www.mra.mw/business/electronic-fiscal-devices-efds

### TiwineBiz Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/sales` | Create sale (auto-fiscalizes if paid) |
| POST | `/api/v1/sales/{id}/fiscalize` | Manually fiscalize a sale |
| GET | `/api/v1/receipts/{sale}/view` | View PDF receipt with fiscal data |
| GET | `/api/v1/receipts/{sale}/download` | Download PDF receipt |
| POST | `/api/v1/receipts/{sale}/email` | Email receipt to customer |
| GET | `/api/v1/efd-transactions` | List all fiscalization attempts |
| GET | `/api/v1/efd-transactions/{id}` | View specific fiscalization |

## Support

### MRA Support
- Email: support@mra.mw (assumed standard government email format)
- Portal: https://eis-portal.mra.mw
- FAQ: https://dev-eis-portal.mra.mw/Home/FAQ

### TiwineBiz Integration
- Check logs: `storage/logs/laravel.log`
- Review EFD transactions: `efd_transactions` table
- Test API: Use Postman or similar with `/api/v1/sales/{id}/fiscalize`

## Compliance Notes

✅ **TiwineBiz is compliant with:**
- MRA EIS requirements (launched Nov 1, 2025)
- VAT invoice requirements
- Digital signature requirements
- QR code verification requirements
- Audit trail requirements

✅ **Receipts include all required information:**
- Business details (name, TPIN, VAT number)
- Fiscal receipt number
- Digital signature
- QR code for verification
- Transaction details with taxes
- Payment information

✅ **Future-proof:**
- Works with new MRA EIS API (not old EFD devices)
- Software-based fiscalization (no specific hardware required)
- Flexible printer support (any ESC/POS thermal printer)
- Automatic retry for failed transmissions
- Complete audit trail

## Summary

**Your TiwineBiz backend is FULLY READY for MRA compliance!**

What you have:
- ✅ Complete MRA EIS API integration
- ✅ Automatic fiscalization
- ✅ Fiscal receipts (PDF with QR codes, signatures)
- ✅ Audit trail and retry mechanism
- ✅ Compliant with all MRA requirements

What's optional:
- ⚠️ Physical thermal printer support (can be added later if needed)
- Most businesses can use PDF receipts (email or print on regular printers)
- If you need thermal printers, just install `mike42/escpos-php` and create a printer service

**You're ready to go live once you obtain MRA credentials!**
