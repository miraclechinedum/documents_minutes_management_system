# Document Workflow & Minutes Management System

A comprehensive Laravel 10 application for managing document workflows with minutes, annotations, and routing capabilities. Built for local development with Laragon/phpMyAdmin/MySQL.

## Features

### Core Functionality
- **Document Upload & Management**: Upload PDFs and images with virus scanning and OCR text extraction
- **Minutes & Annotations**: Add minutes with overlay coordinates for precise document annotation
- **Document Routing**: Forward documents between users and departments with full audit trail
- **PDF Export**: Generate compiled PDFs with original document + appended minutes or overlay annotations
- **Advanced Search**: Full-text search across documents and minutes using Laravel Scout
- **Role-Based Access Control**: Comprehensive RBAC using Spatie permissions
- **Audit Logging**: Complete activity logging with Spatie Activity Log
- **Notifications**: In-app and email notifications for document events

### Technical Features
- **Virus Scanning**: Optional ClamAV integration for uploaded files
- **OCR Processing**: Tesseract integration for text extraction from images/PDFs  
- **Queue System**: Database-driven queues for background processing
- **File Storage**: Configurable storage (local/S3) with secure file access
- **PDF Viewer**: In-browser PDF viewing with PDF.js and annotation support
- **Responsive Design**: Mobile-first design with Tailwind CSS
- **API Ready**: Laravel Sanctum for API authentication

## Requirements

- PHP 8.1+
- MySQL 8.0+
- Node.js 18+
- Composer
- Laragon (for local development)

## Local Setup Instructions

### Step 1: Clone and Install Dependencies

```bash
# Clone the repository
git clone <repository-url> document-workflow
cd document-workflow

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm ci
```

### Step 2: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Database Setup

1. **Open phpMyAdmin** (via Laragon)
2. **Create database**: `cac_documents_minutes_db`
3. **Update .env** with these database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cac_documents_minutes_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 4: Database Migration and Seeding

```bash
# Run migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolePermissionSeeder

# Seed initial data (departments and users)
php artisan db:seed --class=InitialSeeder
```

### Step 5: Storage Setup

```bash
# Create storage symlink
php artisan storage:link

# Create required directories
mkdir -p storage/app/scout
mkdir -p storage/app/documents
mkdir -p storage/app/thumbnails
mkdir -p storage/app/quarantine
```

### Step 6: Build Assets

```bash
# Build frontend assets
npm run build

# Or for development with hot reload
npm run dev
```

### Step 7: Start Queue Worker

Open a **new terminal** and run:

```bash
# Start queue worker (keep this running)
php artisan queue:work --tries=3
```

### Step 8: Start Application

**Option A: Using Laravel's built-in server**
```bash
php artisan serve
```
Access at: http://localhost:8000

**Option B: Using Laragon virtual host**
1. Move project to Laragon's `www` directory
2. Access at: http://document-workflow.test

## Default Login Credentials

| Role | Email | Password | Department |
|------|-------|----------|------------|
| Admin | admin@example.com | password | Administration |
| Department Head | md@example.com | password | Medical Department |
| User | proc@example.com | password | Procurement |

**⚠️ Important**: Change passwords on first login (users are flagged to change password).

## Optional Features Configuration

### Enable Virus Scanning (ClamAV)
```env
DW_ENABLE_VIRUS_SCAN=true
DW_CLAMSCAN_PATH="/usr/bin/clamscan"
```

### Enable OCR Text Extraction (Tesseract)
```env
DW_ENABLE_OCR=true
DW_TESSERACT_PATH="/usr/bin/tesseract"
```

### Switch to Redis Queue (Production)
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Enable Meilisearch (Production Search)
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=
```

## Testing

### Run PHP Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Feature/DocumentUploadTest.php
```

### Run E2E Tests
```bash
# Install Playwright
npx playwright install

# Run E2E tests
npm run e2e
```

## Verification Steps

### Test Core Workflows

1. **Login as Admin**
   - Navigate to http://localhost:8000
   - Login with `admin@example.com` / `password`

2. **Upload Document**
   - Go to Documents → Upload Document
   - Upload a PDF/image file
   - Assign to MD (md@example.com)
   - Observe status changes: Quarantined → Scanning → Received

3. **Add Minutes with Annotations**
   - Login as MD (`md@example.com` / `password`)
   - Open the uploaded document
   - Click "Add Minute"
   - Add minute text and forward to Procurement department
   - Observe document routing and notifications

4. **View Routing History**
   - Check document details page for routing history
   - Verify audit logs in admin panel

5. **Export Document**
   - Click "Export PDF" on any document
   - Verify compiled PDF includes original + minutes appendix

6. **Test Search**
   - Go to Search page
   - Search for document content or minute text
   - Verify results appear with proper permissions

7. **Admin Functions**
   - Access Admin → Dashboard
   - Manage users and departments
   - View audit logs

## File Structure

```
├── app/
│   ├── Http/Controllers/     # Web controllers
│   ├── Jobs/                 # Queue jobs (virus scan, OCR)
│   ├── Models/              # Eloquent models
│   ├── Notifications/       # Email/database notifications
│   └── Policies/            # Authorization policies
├── database/
│   ├── migrations/          # Database schema migrations
│   └── seeders/            # Database seeders
├── resources/
│   ├── js/                 # Frontend JavaScript (Vite)
│   ├── css/                # Tailwind CSS styles
│   └── views/              # Blade templates
├── routes/
│   └── web.php             # Web routes
├── tests/
│   ├── Feature/            # Feature tests
│   ├── Unit/               # Unit tests
│   └── e2e/               # E2E tests (Playwright)
└── storage/
    └── app/
        ├── documents/      # Uploaded documents
        ├── thumbnails/     # Generated thumbnails
        ├── quarantine/     # Quarantined files
        └── scout/          # Search index files
```

## Troubleshooting

### Common Issues

**Queue jobs not processing:**
- Ensure `php artisan queue:work` is running
- Check `.env` has `QUEUE_CONNECTION=database`

**File upload errors:**
- Check `storage/` permissions: `chmod -R 755 storage`
- Verify `upload_max_filesize` and `post_max_size` in PHP.ini

**PDF viewer not loading:**
- Ensure `npm run build` completed successfully
- Check browser console for JavaScript errors

**Search not working:**
- Run `php artisan scout:import "App\Models\Document"`
- Verify TNTSearch index files created in `storage/app/scout/`

**Database connection issues:**
- Verify MySQL is running in Laragon
- Check database credentials in `.env`
- Ensure `cac_documents_minutes_db` database exists

**Permissions errors:**
- Run `php artisan db:seed --class=RolePermissionSeeder`
- Clear cache: `php artisan cache:clear`

### Windows/Laragon Specific

**File path issues:**
- Use forward slashes in configuration
- Ensure Laragon has proper permissions

**ClamAV/Tesseract not found:**
- Install via Windows package managers or disable in `.env`
- Set correct paths in configuration

## Production Deployment Notes

### Storage Configuration
```php
// config/filesystems.php - Switch to S3 for production
'default' => env('FILESYSTEM_DISK', 's3'),
```

### Queue Configuration
```env
# Use Redis for production queues
QUEUE_CONNECTION=redis
```

### Search Configuration
```env
# Use Meilisearch for production search
SCOUT_DRIVER=meilisearch
```

### Security Headers
The application includes CSP and security headers middleware for production use.

## Support & Documentation

- **Laravel Documentation**: https://laravel.com/docs/10.x
- **Spatie Packages**: https://spatie.be/docs
- **PDF.js Documentation**: https://mozilla.github.io/pdf.js/
- **Tailwind CSS**: https://tailwindcss.com/docs

## License

This project is licensed under the MIT License.