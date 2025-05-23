# Bank Import System

A modern PHP application for processing bank transactions using clean architecture principles.

## Architecture

The application follows these design principles:
- Single Responsibility Principle (SRP)
- Dependency Injection
- Command Query Responsibility Segregation (CQRS)
- Repository Pattern
- Factory Pattern
- Middleware Pattern

## Structure

```
src/
├── Commands/          # Command objects for CQRS
├── Controllers/       # Application controllers
├── Database/         # Database access layer
├── Events/          # Event objects and handlers
├── Exceptions/      # Custom exceptions
├── Handlers/        # Command and event handlers
├── Http/            # Request/Response handling
├── Interfaces/      # Interfaces for dependency injection
├── Middleware/      # HTTP middleware components
├── Models/          # Domain models
├── Repositories/    # Data access repositories
├── Services/        # Business logic services
└── Views/           # View templates
```

## Setup

1. Copy `.env.example` to `.env` and configure your environment
2. Install dependencies:
   ```bash
   composer install
   ```
3. Run database migrations:
   ```bash
   php sql/migrations/001_create_transactions_table.sql
   ```

## Testing

Run the test suite:
```bash
composer test
```

## Contributing

1. Create a feature branch
2. Write tests
3. Implement changes
4. Submit pull request
