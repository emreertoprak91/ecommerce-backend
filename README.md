# E-Commerce Backend API

A production-ready e-commerce backend built with Laravel 11, following Domain-Driven Design (DDD) principles, SOLID patterns, and enterprise-grade architecture.

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
<img src="https://img.shields.io/badge/Redis-7.4-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis">
<img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
</p>

## 📋 Table of Contents

- [Features](#-features)
- [Architecture](#-architecture)
- [Tech Stack](#-tech-stack)
- [Quick Start](#-quick-start)
- [API Documentation](#-api-documentation)
- [Domain Structure](#-domain-structure)
- [Exception Handling](#-exception-handling)
- [Audit Logging](#-audit-logging)
- [Testing](#-testing)
- [Code Quality](#-code-quality)
- [Docker Services](#-docker-services)
- [CI/CD](#-cicd)
- [Make Commands](#-make-commands)

## ✨ Features

### Core Features
- 🛒 **Product Management** - CRUD operations with categories, filtering, pagination
- 📦 **Order Management** - Order creation, status tracking, cancellation
- 💳 **Payment Integration** - PayTR payment gateway integration
- 👤 **User Authentication** - Registration, login, email verification (Sanctum)
- ❤️ **Wishlist** - Add/remove products, toggle functionality
- 🏷️ **Category System** - Hierarchical categories with products

### Technical Features
- 🏗️ **Domain-Driven Design** - Clean separation of concerns
- 📝 **Audit Logging** - Track all model changes (who, what, when)
- 🚨 **Custom Exceptions** - Domain-specific error handling
- 📊 **API Response Standardization** - Consistent JSON responses
- 🔄 **Event-Driven Architecture** - Domain events for User, Product, Order
- 💾 **Repository Pattern** - Database abstraction layer
- 🗃️ **Redis Caching** - Performance optimization
- 📨 **Queue System** - Background job processing with Horizon
- 📧 **Email System** - Queued emails with Mailpit for testing
- 🔍 **Full-Text Search** - Product search with filters
- 📚 **API Documentation** - Swagger/OpenAPI 3.0

## 🏗️ Architecture

This project follows **Domain-Driven Design (DDD)** with **SOLID principles**:

```
app/
├── Domain/                         # Business Logic Layer
│   ├── Order/
│   │   ├── DTOs/                   # CreateOrderDTO
│   │   ├── Events/                 # OrderCreatedEvent, OrderCancelledEvent
│   │   ├── Exceptions/             # OrderNotFoundException, EmptyCartException
│   │   ├── Models/                 # Order, OrderItem
│   │   ├── Repositories/           # OrderRepositoryInterface
│   │   └── Services/               # OrderService
│   ├── Payment/
│   │   ├── Exceptions/             # PaymentFailedException
│   │   ├── Models/                 # Payment
│   │   └── Services/               # PaymentService, PayTRService
│   ├── Product/
│   │   ├── DTOs/                   # CreateProductDTO, UpdateProductDTO
│   │   ├── Events/                 # ProductCreatedEvent, ProductUpdatedEvent
│   │   ├── Exceptions/             # ProductNotFoundException, DuplicateSkuException
│   │   ├── Models/                 # Product, Category
│   │   ├── Repositories/           # ProductRepositoryInterface
│   │   └── Services/               # ProductService, CategoryService
│   ├── Shared/
│   │   ├── Exceptions/             # DomainException (base)
│   │   ├── Models/                 # AuditLog
│   │   ├── Observers/              # AuditObserver
│   │   └── Traits/                 # Auditable
│   ├── User/
│   │   ├── DTOs/                   # RegisterUserDTO, LoginUserDTO
│   │   ├── Events/                 # UserRegisteredEvent
│   │   ├── Exceptions/             # InvalidCredentialsException
│   │   └── Services/               # UserService, AuthService
│   └── Wishlist/
│       ├── Models/                 # Wishlist
│       └── Services/               # WishlistService
├── Http/
│   ├── Controllers/Api/V1/         # REST API Controllers
│   ├── Middleware/                 # ForceJsonResponse, etc.
│   ├── Requests/                   # Form Requests (Validation)
│   └── Resources/                  # API Resources (JSON Transformers)
├── Infrastructure/
│   └── Cache/                      # CacheService (Redis)
├── Providers/
│   ├── AppServiceProvider.php
│   ├── EventServiceProvider.php    # Event & Observer registration
│   └── RepositoryServiceProvider.php
└── Support/
    └── Traits/
        └── ApiResponseTrait.php    # Standardized API responses
```

## 🛠️ Tech Stack

| Category | Technology |
|----------|------------|
| **Framework** | Laravel 11.x |
| **PHP Version** | 8.3+ |
| **Database** | MySQL 8.0 |
| **Cache & Queue** | Redis 7.4 |
| **Authentication** | Laravel Sanctum |
| **Queue Dashboard** | Laravel Horizon |
| **Logging** | ELK Stack (Elasticsearch, Logstash, Kibana) |
| **API Docs** | L5 Swagger (OpenAPI 3.0) |
| **Static Analysis** | PHPStan Level 6 + Larastan |
| **Code Style** | PHP CS Fixer (PSR-12) |
| **Testing** | PHPUnit + Pest |
| **Containerization** | Docker + Docker Compose |

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Make (optional, for convenience commands)

### Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd ecommerce-backend

# 2. Copy environment file
cp .env.example .env

# 3. Start with Docker (recommended)
make setup

# Or manually:
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan l5-swagger:generate
```

### Access Points

| Service | URL | Description |
|---------|-----|-------------|
| **API** | http://localhost:8080/api/v1 | REST API |
| **Swagger Docs** | http://localhost:8080/api/documentation | API Documentation |
| **Horizon** | http://localhost:8080/horizon | Queue Dashboard |
| **Kibana** | http://localhost:5601 | Log Visualization |
| **Mailpit** | http://localhost:8025 | Email Testing |

## 📚 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/v1/auth/register` | User registration | ❌ |
| `POST` | `/api/v1/auth/login` | User login | ❌ |
| `GET` | `/api/v1/auth/verify-email/{token}` | Email verification | ❌ |
| `POST` | `/api/v1/auth/resend-verification` | Resend verification | ❌ |
| `GET` | `/api/v1/auth/me` | Get current user | ✅ |
| `POST` | `/api/v1/auth/logout` | Logout | ✅ |
| `POST` | `/api/v1/auth/logout-all` | Logout all devices | ✅ |

### Product Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/products` | List products (paginated, filterable) | ❌ |
| `GET` | `/api/v1/products/{id}` | Get product by ID | ❌ |
| `GET` | `/api/v1/products/slug/{slug}` | Get product by slug | ❌ |
| `POST` | `/api/v1/products` | Create product | ✅ |
| `PUT` | `/api/v1/products/{id}` | Update product | ✅ |
| `DELETE` | `/api/v1/products/{id}` | Delete product | ✅ |

### Category Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/categories` | List categories | ❌ |
| `GET` | `/api/v1/categories/{id}` | Get category by ID | ❌ |
| `GET` | `/api/v1/categories/slug/{slug}` | Get category by slug | ❌ |

### Order Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/orders` | List user orders | ✅ |
| `POST` | `/api/v1/orders` | Create order | ✅ |
| `GET` | `/api/v1/orders/{id}` | Get order details | ✅ |
| `POST` | `/api/v1/orders/{id}/cancel` | Cancel order | ✅ |

### Wishlist Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/wishlist` | Get wishlist | ✅ |
| `POST` | `/api/v1/wishlist` | Add to wishlist | ✅ |
| `DELETE` | `/api/v1/wishlist/{product}` | Remove from wishlist | ✅ |
| `POST` | `/api/v1/wishlist/toggle` | Toggle wishlist item | ✅ |
| `GET` | `/api/v1/wishlist/check/{product}` | Check if in wishlist | ✅ |
| `DELETE` | `/api/v1/wishlist` | Clear wishlist | ✅ |
| `GET` | `/api/v1/wishlist/count` | Get wishlist count | ✅ |

### Payment Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/v1/payments/initiate` | Initiate payment | ✅ |
| `GET` | `/api/v1/payments/{merchantOid}/status` | Get payment status | ✅ |
| `POST` | `/api/v1/payments/callback` | PayTR webhook | ❌ |

### Health Check Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/health` | Overall health status |
| `GET` | `/api/health/detailed` | Detailed health info |
| `GET` | `/api/health/liveness` | Kubernetes liveness probe |
| `GET` | `/api/health/readiness` | Kubernetes readiness probe |

### Response Format

All API responses follow a standardized format:

**Success Response:**
```json
{
    "success": true,
    "message": "Products retrieved successfully",
    "data": [...],
    "meta": {
        "timestamp": "2025-12-01T12:00:00.000000Z",
        "trace_id": "trace_674..."
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Product not found",
    "errors": null,
    "meta": {
        "timestamp": "2025-12-01T12:00:00.000000Z",
        "trace_id": "trace_674..."
    }
}
```

**Debug Mode (APP_DEBUG=true):**
```json
{
    "success": false,
    "message": "Product not found",
    "errors": null,
    "meta": {...},
    "debug": {
        "exception": "App\\Domain\\Product\\Exceptions\\ProductNotFoundException",
        "file": "/var/www/html/app/Domain/Product/Services/ProductService.php",
        "line": 60,
        "trace": [...]
    }
}
```

## 🚨 Exception Handling

### Domain Exceptions

All domain exceptions extend `DomainException` and are automatically handled:

| Exception | HTTP Code | Description |
|-----------|-----------|-------------|
| **Product Domain** |
| `ProductNotFoundException` | 404 | Product not found |
| `DuplicateSkuException` | 400 | SKU already exists |
| `CategoryNotFoundException` | 404 | Category not found |
| **Order Domain** |
| `OrderNotFoundException` | 404 | Order not found |
| `EmptyCartException` | 422 | Cart is empty |
| `InsufficientStockException` | 422 | Not enough stock |
| `OrderCannotBeCancelledException` | 422 | Order status doesn't allow cancel |
| **User Domain** |
| `UserNotFoundException` | 404 | User not found |
| `InvalidCredentialsException` | 401 | Wrong email/password |
| `EmailAlreadyExistsException` | 400 | Email taken |
| `EmailNotVerifiedException` | 403 | Email not verified |
| **Payment Domain** |
| `PaymentNotFoundException` | 404 | Payment not found |
| `PaymentFailedException` | 400 | Payment processing failed |

### Creating New Exceptions

```php
<?php

declare(strict_types=1);

namespace App\Domain\YourDomain\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class YourCustomException extends DomainException
{
    public function __construct(string $identifier)
    {
        $this->context = ['identifier' => $identifier];
        parent::__construct("Your error message: {$identifier}", 400);
    }
}
```

## 📝 Audit Logging

The system automatically tracks all changes to critical models using the Observer pattern.

### Tracked Models

- `User` - User account changes (excludes: password, tokens)
- `Product` - Product CRUD operations
- `Category` - Category changes
- `Order` - Order lifecycle
- `OrderItem` - Order item changes
- `Payment` - Payment transactions (excludes: sensitive tokens)
- `Wishlist` - Wishlist modifications

### Audit Log Structure

```php
// audit_logs table
[
    'id' => 1,
    'model_type' => 'App\Domain\Product\Models\Product',
    'model_id' => 29,
    'user_id' => 1,              // Who made the change
    'event' => 'updated',        // created, updated, deleted
    'old_values' => ['name' => 'Old Name', 'price' => 9999],
    'new_values' => ['name' => 'New Name', 'price' => 12999],
    'ip_address' => '192.168.65.1',
    'user_agent' => 'PostmanRuntime/7.49.1',
    'url' => 'http://localhost:8080/api/v1/products/29',
    'created_at' => '2025-12-01 12:53:01'
]
```

### Adding Audit to New Models

```php
<?php

use App\Domain\Shared\Traits\Auditable;

class YourModel extends Model
{
    use Auditable;

    // Optional: Exclude sensitive fields from audit
    protected array $auditExclude = ['password', 'secret_token'];
}
```

Register in `EventServiceProvider`:
```php
YourModel::observe(AuditObserver::class);
```

## 🧪 Testing

```bash
# Run all tests
make test

# Run with coverage
make test-coverage

# Run specific test suites
make test-unit
make test-feature
make test-integration

# Run in Docker
docker-compose exec app php artisan test

# Run specific test file
docker-compose exec app php artisan test --filter=ProductServiceTest
```

### Test Structure

```
tests/
├── Unit/                           # Isolated unit tests
│   └── Domain/
│       ├── Product/
│       │   └── Services/
│       │       └── ProductServiceTest.php
│       └── Shared/
│           └── Observers/
│               └── AuditObserverTest.php
├── Integration/                    # Database integration tests
│   └── Repositories/
│       └── ProductRepositoryTest.php
├── Feature/                        # API/HTTP tests
│   └── Api/V1/
│       ├── AuthControllerTest.php
│       ├── ProductControllerTest.php
│       └── OrderControllerTest.php
└── E2E/                            # End-to-end tests
    └── OrderFlowTest.php
```

### Test Coverage

Current coverage: **186 tests passing**

| Domain | Unit | Integration | Feature |
|--------|------|-------------|---------|
| Product | ✅ | ✅ | ✅ |
| Order | ✅ | ✅ | ✅ |
| User/Auth | ✅ | ✅ | ✅ |
| Payment | ✅ | ✅ | ✅ |
| Wishlist | ✅ | ✅ | ✅ |
| Audit | ✅ | - | - |

## 🔧 Code Quality

```bash
# Check code style (PSR-12)
make lint

# Fix code style automatically
make lint-fix

# Run static analysis (PHPStan Level 6)
make analyze

# Run all quality checks
make quality
```

### PHPStan Configuration

```neon
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 6
    paths:
        - app
```

## 📦 Docker Services

| Service | Port | Description |
|---------|------|-------------|
| `app` | - | PHP-FPM 8.3 with extensions |
| `nginx` | 8080 | Web server |
| `mysql` | 3306 (33061 external) | MySQL 8.0 database |
| `redis` | 6379 | Cache & queue backend |
| `elasticsearch` | 9200 | Log storage & search |
| `logstash` | 5044 | Log processing pipeline |
| `kibana` | 5601 | Log visualization |
| `mailpit` | 8025 | Email testing UI |

### Docker Commands

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f app

# Access PHP container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan <command>

# Run tinker
docker-compose exec app php artisan tinker
```

## 🔄 CI/CD

### GitHub Actions Workflows

#### CI Pipeline (`.github/workflows/ci.yml`)
- ✅ Code style check (PHP CS Fixer)
- ✅ Static analysis (PHPStan)
- ✅ Unit & Feature tests
- ✅ Code coverage report
- ✅ Security audit (composer audit)
- ✅ SonarQube analysis

#### CD Pipeline (`.github/workflows/cd.yml`)
- ✅ Docker image build
- ✅ Push to GHCR & Docker Hub
- ✅ Deploy to staging (auto on develop)
- ✅ Deploy to production (manual approval)

### Branch Strategy

| Branch | Purpose | Auto Deploy |
|--------|---------|-------------|
| `main` | Production | Manual |
| `develop` | Staging | Auto |
| `feature/*` | New features | - |
| `hotfix/*` | Production fixes | - |

## 📁 Make Commands

```bash
make help            # Show all available commands

# Setup & Management
make setup           # Full setup (build, start, migrate, seed)
make up              # Start containers
make down            # Stop containers
make restart         # Restart containers
make rebuild         # Rebuild containers

# Development
make shell           # Access PHP container bash
make tinker          # Run Laravel Tinker
make logs            # View application logs

# Database
make migrate         # Run migrations
make migrate-fresh   # Fresh migration with seeds
make seed            # Run seeders

# Testing
make test            # Run all tests
make test-unit       # Run unit tests only
make test-feature    # Run feature tests only
make test-coverage   # Run tests with coverage

# Code Quality
make lint            # Check code style
make lint-fix        # Fix code style
make analyze         # Run PHPStan
make quality         # Run all quality checks

# Documentation
make docs            # Generate Swagger docs

# Queue & Jobs
make horizon         # Start Horizon dashboard
make queue           # Start queue worker

# Health & Monitoring
make health          # Check application health
```

## 🔐 Environment Variables

```env
# Application
APP_NAME="E-Commerce API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=laravel
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Queue
QUEUE_CONNECTION=redis

# Mail (Mailpit for local)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000

# PayTR (Payment Gateway)
PAYTR_MERCHANT_ID=
PAYTR_MERCHANT_KEY=
PAYTR_MERCHANT_SALT=

# ELK Stack
ELASTICSEARCH_HOST=elasticsearch:9200
LOG_CHANNEL=stack
LOG_STACK=single,syslog
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Commit Convention

```
feat: Add new feature
fix: Bug fix
docs: Documentation changes
style: Code style changes (formatting, etc.)
refactor: Code refactoring
test: Add or update tests
chore: Maintenance tasks
```

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

<p align="center">
Built with ❤️ using Laravel
</p>
