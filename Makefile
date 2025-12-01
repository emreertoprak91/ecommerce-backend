# E-Commerce Backend - Makefile
# Docker-based development commands

.PHONY: help build up down restart logs shell mysql redis test lint analyze docs horizon health migrate seed fresh

# Colors for output
BLUE := \033[34m
GREEN := \033[32m
YELLOW := \033[33m
RED := \033[31m
NC := \033[0m

# Docker Compose files
DC := docker-compose
DC_PROD := docker-compose -f docker-compose.prod.yml

help: ## Show this help message
	@echo "$(BLUE)E-Commerce Backend - Available Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ==================== DOCKER COMMANDS ====================

build: ## Build all Docker containers
	@echo "$(BLUE)Building Docker containers...$(NC)"
	$(DC) build --no-cache

build-prod: ## Build production Docker containers
	@echo "$(BLUE)Building production containers...$(NC)"
	$(DC_PROD) build --no-cache

up: ## Start all containers
	@echo "$(GREEN)Starting containers...$(NC)"
	$(DC) up -d
	@echo "$(GREEN)Containers started!$(NC)"
	@echo "  App:          http://localhost:8080"
	@echo "  Kibana:       http://localhost:5601"
	@echo "  Horizon:      http://localhost:8080/horizon"
	@echo "  Swagger:      http://localhost:8080/api/documentation"
	@echo "  Mailpit:      http://localhost:8025"

down: ## Stop all containers
	@echo "$(YELLOW)Stopping containers...$(NC)"
	$(DC) down

restart: down up ## Restart all containers

logs: ## View all container logs
	$(DC) logs -f

logs-app: ## View PHP app logs
	$(DC) logs -f app

logs-nginx: ## View Nginx logs
	$(DC) logs -f nginx

logs-mysql: ## View MySQL logs
	$(DC) logs -f mysql

logs-redis: ## View Redis logs
	$(DC) logs -f redis

logs-elk: ## View ELK stack logs
	$(DC) logs -f elasticsearch logstash kibana

# ==================== SHELL ACCESS ====================

shell: ## Access PHP container shell
	$(DC) exec app bash

shell-mysql: ## Access MySQL shell
	$(DC) exec mysql mysql -u laravel -psecret ecommerce

shell-redis: ## Access Redis CLI
	$(DC) exec redis redis-cli

# ==================== LARAVEL COMMANDS ====================

artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	$(DC) exec app php artisan $(cmd)

migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	$(DC) exec app php artisan migrate --force

migrate-fresh: ## Fresh migration with seeders
	@echo "$(YELLOW)Fresh migration (drops all tables)...$(NC)"
	$(DC) exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	$(DC) exec app php artisan db:seed

cache-clear: ## Clear all caches
	$(DC) exec app php artisan cache:clear
	$(DC) exec app php artisan config:clear
	$(DC) exec app php artisan route:clear
	$(DC) exec app php artisan view:clear
	@echo "$(GREEN)All caches cleared!$(NC)"

optimize: ## Optimize the application
	$(DC) exec app php artisan optimize
	$(DC) exec app php artisan view:cache
	@echo "$(GREEN)Application optimized!$(NC)"

# ==================== QUEUE & HORIZON ====================

horizon: ## Start Laravel Horizon
	$(DC) exec app php artisan horizon

horizon-pause: ## Pause Horizon
	$(DC) exec app php artisan horizon:pause

horizon-continue: ## Continue Horizon
	$(DC) exec app php artisan horizon:continue

horizon-terminate: ## Terminate Horizon
	$(DC) exec app php artisan horizon:terminate

queue-work: ## Start queue worker
	$(DC) exec app php artisan queue:work

queue-failed: ## List failed jobs
	$(DC) exec app php artisan queue:failed

queue-retry: ## Retry all failed jobs
	$(DC) exec app php artisan queue:retry all

# ==================== TESTING & QUALITY ====================

test: ## Run all tests
	@echo "$(BLUE)Running all tests...$(NC)"
	$(DC) exec app php artisan test --stop-on-failure

test-coverage: ## Run tests with coverage report
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	$(DC) exec app php artisan test --coverage --coverage-html=coverage-report

test-unit: ## Run unit tests only
	@echo "$(BLUE)Running unit tests...$(NC)"
	$(DC) exec app php artisan test --testsuite=Unit --stop-on-failure

test-feature: ## Run feature tests only
	@echo "$(BLUE)Running feature tests...$(NC)"
	$(DC) exec app php artisan test --testsuite=Feature --stop-on-failure

test-integration: ## Run integration tests only
	@echo "$(BLUE)Running integration tests...$(NC)"
	$(DC) exec app php artisan test --testsuite=Integration --stop-on-failure

test-parallel: ## Run tests in parallel (faster)
	@echo "$(BLUE)Running tests in parallel...$(NC)"
	$(DC) exec app php artisan test --parallel --processes=4

test-filter: ## Run specific test (usage: make test-filter FILTER=testMethodName)
	@echo "$(BLUE)Running filtered test: $(FILTER)$(NC)"
	$(DC) exec app php artisan test --filter=$(FILTER)

ci: ## Run CI checks locally (lint + analyze + test)
	@echo "$(BLUE)Running CI pipeline locally...$(NC)"
	@echo "$(YELLOW)Step 1/3: Code Style Check$(NC)"
	$(DC) exec app ./vendor/bin/pint --test
	@echo "$(YELLOW)Step 2/3: Static Analysis$(NC)"
	$(DC) exec app ./vendor/bin/phpstan analyse --memory-limit=2G
	@echo "$(YELLOW)Step 3/3: Running Tests$(NC)"
	$(DC) exec app php artisan test --stop-on-failure
	@echo "$(GREEN)✅ All CI checks passed!$(NC)"

lint: ## Run PHP-CS-Fixer (check)
	@echo "$(BLUE)Checking code style...$(NC)"
	$(DC) exec app ./vendor/bin/php-cs-fixer fix --dry-run --diff

lint-fix: ## Run PHP-CS-Fixer (fix)
	@echo "$(BLUE)Fixing code style...$(NC)"
	$(DC) exec app ./vendor/bin/php-cs-fixer fix

pint: ## Run Laravel Pint (check)
	@echo "$(BLUE)Checking code style with Pint...$(NC)"
	$(DC) exec app ./vendor/bin/pint --test

pint-fix: ## Run Laravel Pint (fix)
	@echo "$(BLUE)Fixing code style with Pint...$(NC)"
	$(DC) exec app ./vendor/bin/pint

analyze: ## Run PHPStan static analysis
	@echo "$(BLUE)Running static analysis...$(NC)"
	$(DC) exec app ./vendor/bin/phpstan analyse --memory-limit=2G

# ==================== DOCUMENTATION ====================

docs: ## Generate Swagger documentation
	@echo "$(BLUE)Generating API documentation...$(NC)"
	$(DC) exec app php artisan l5-swagger:generate
	@echo "$(GREEN)Documentation available at: http://localhost:8080/api/documentation$(NC)"

# ==================== HEALTH & MONITORING ====================

health: ## Check application health
	@echo "$(BLUE)Checking application health...$(NC)"
	@curl -s http://localhost:8080/api/health | jq .

health-detail: ## Detailed health check
	@curl -s http://localhost:8080/api/health/readiness | jq .

# ==================== DATABASE ====================

db-backup: ## Backup database
	@echo "$(BLUE)Backing up database...$(NC)"
	$(DC) exec mysql mysqldump -u laravel -psecret ecommerce > backups/db_backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Backup created!$(NC)"

db-restore: ## Restore database (usage: make db-restore file=backup.sql)
	@echo "$(YELLOW)Restoring database from $(file)...$(NC)"
	$(DC) exec -T mysql mysql -u laravel -psecret ecommerce < $(file)
	@echo "$(GREEN)Database restored!$(NC)"

# ==================== UTILITY ====================

install: ## Install dependencies
	$(DC) exec app composer install

update: ## Update dependencies
	$(DC) exec app composer update

keygen: ## Generate application key
	$(DC) exec app php artisan key:generate

storage-link: ## Create storage symlink
	$(DC) exec app php artisan storage:link

permissions: ## Fix storage permissions
	$(DC) exec app chmod -R 775 storage bootstrap/cache
	$(DC) exec app chown -R www-data:www-data storage bootstrap/cache

# ==================== SETUP ====================

setup: build up install migrate seed storage-link docs ## Full setup (build, start, install, migrate, seed)
	@echo "$(GREEN)====================================$(NC)"
	@echo "$(GREEN)Setup complete!$(NC)"
	@echo "$(GREEN)====================================$(NC)"
	@echo ""
	@echo "Available services:"
	@echo "  $(BLUE)App:$(NC)          http://localhost:8080"
	@echo "  $(BLUE)API Docs:$(NC)     http://localhost:8080/api/documentation"
	@echo "  $(BLUE)Horizon:$(NC)      http://localhost:8080/horizon"
	@echo "  $(BLUE)Kibana:$(NC)       http://localhost:5601"
	@echo "  $(BLUE)Mailpit:$(NC)      http://localhost:8025"
	@echo ""
	@echo "Run $(YELLOW)make help$(NC) to see all available commands"

# ==================== CLEANUP ====================

clean: ## Remove all containers, volumes, and images
	@echo "$(RED)Cleaning up Docker resources...$(NC)"
	$(DC) down -v --rmi all --remove-orphans
	@echo "$(GREEN)Cleanup complete!$(NC)"

prune: ## Prune Docker system
	docker system prune -af
	docker volume prune -f
