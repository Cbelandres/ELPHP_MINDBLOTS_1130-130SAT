# INVESTMENT Project

A Laravel-based investment management system that helps users track and manage their investments efficiently. This platform provides comprehensive tools for monitoring portfolios, analyzing performance, and making informed investment decisions.

## 🚀 Features

### Core Features
- User Authentication & Authorization
- Investment Portfolio Management
- Real-time Investment Tracking
- Secure API Endpoints
- Modern and Responsive UI

### Investment Management
- Portfolio Performance Analytics
- Asset Allocation Tracking
- Investment Transaction History
- Dividend and Interest Tracking
- Risk Assessment Tools
- Investment Goal Setting
- Market Data Integration
- Performance Reports and Charts

### User Experience
- Dashboard with Key Metrics
- Customizable Watchlists
- Investment Alerts and Notifications
- Exportable Reports
- Mobile-Responsive Design

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite (or your preferred database)
- Redis (for caching and queues)
- Mail Server (for notifications)

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

- `/api/auth/*` - Authentication endpoints
- `/api/portfolio/*` - Portfolio management
- `/api/investments/*` - Investment operations
- `/api/analytics/*` - Performance analytics

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

Run specific test types:
```bash
# Run feature tests
php artisan test --testsuite=Feature

# Run unit tests
php artisan test --testsuite=Unit

# Run with coverage
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

## 🔐 Security

- CSRF Protection
- XSS Protection
- SQL Injection Prevention
- Secure Authentication with Sanctum
- Input Validation
- Rate Limiting
- Two-Factor Authentication (optional)
- API Token Management
- Secure Password Policies

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Workflow
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation as needed
- Use conventional commits
- Keep the codebase clean and maintainable

## 📞 Support

For support, please:
1. Check the [documentation](docs/)
2. Search existing issues
3. Open a new issue with:
   - Detailed description
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details

## 🙏 Acknowledgments

- Laravel Framework
- All contributors who have helped shape this project
- Open-source community
- Financial data providers

## 📈 Roadmap

- [ ] Enhanced Portfolio Analytics
- [ ] Mobile Application
- [ ] Advanced Risk Analysis
- [ ] AI-powered Investment Recommendations
- [ ] Social Investment Features
- [ ] Multi-currency Support
