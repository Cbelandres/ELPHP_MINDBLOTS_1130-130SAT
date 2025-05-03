# CROWDFARM Project

A Laravel-based investment management system that helps users track and manage their investments efficiently. 

## 🚀 Features

### Core Features
- User Authentication & Authorization
- Investment Portfolio Management
- Real-time Investment Tracking
- Secure API Endpoints


## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- mysQL

## 🛠️ Installation

1. Clone the repository:
```bash
git clone [your-repository-url]
cd INVESTMENT
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install NPM dependencies:
```bash
npm install
```

4. Create environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Create database:
```bash
touch database/database.sqlite
```

7. Run migrations:
```bash
php artisan migrate
```

8. Seed the database (optional):
```bash
php artisan db:seed
```

9. Start the development server:
```bash
php artisan serve
```

10. In a separate terminal, start Vite:
```bash
npm run dev
```

## 🔧 Configuration

### Environment Variables
Update `.env` file with:
- Database credentials
- Mail server settings
- Cache configuration
- API keys for market data
- Queue configuration
- Session settings

### Mail Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
```

### Cache Configuration
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 📊 API Documentation

The API documentation is available at `/api/documentation` when running the application locally. Key endpoints include:

- `/api/login/` - Login endpoints
- `/api/farmers/register` - Register Farmer
- `/api/admin/register` - Register Admin
- `/api/investors/register` - Register Investor
- `/api/campaigns` - Campaign Creation by Farmer
- `/api/campaigns/{campaign_id}/approve` - Approve the Campaign ID by Admin
- `/api/campaigns/{campaign_id}/fund` - Investor to fund the Campaign
- `/api/admin/dashboard` - Admin Reports
- `/api/farmer/campaigns/report` - Farmer Campaigns Reports
- `/api/investor/dashboard` - Investor Reports


php artisan test --coverage
```

## 📦 Dependencies

### Backend
- Laravel Framework ^12.0
- Laravel Sanctum ^4.1
- Laravel Tinker ^2.10.1
- Guzzle HTTP Client (for API integrations)
- Laravel Excel (for data export)

### Development
- Laravel Sail ^1.41
- Laravel Pint ^1.13
- PHPUnit ^11.5.3
- FakerPHP ^1.23
- Laravel Telescope (for debugging)

