# KSF Bank Import Setup

## Quick Start

After cloning or pulling this repository, **always run**:

```bash
composer install
```

This ensures all dependencies are installed locally. The `vendor/` directory is not tracked in Git (as per best practices).

## Automatic Setup (Optional)

### Git Hook (Recommended)

The repository includes a `post-merge` hook that automatically runs `composer install` whenever `composer.json` or `composer.lock` changes after a pull.

**To enable it:**

On Linux/Mac:
```bash
chmod +x .git/hooks/post-merge
```

On Windows (PowerShell):
```powershell
# The .bat version will run automatically
# No additional setup needed!
```

### Manual Setup

If you prefer to run it manually after each pull:

```bash
git pull origin main && composer install
```

## First Time Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ksfraser/ksf_bank_import.git
   cd ksf_bank_import
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Verify installation:**
   ```bash
   composer test
   ```

## Why vendor/ is not in Git

Following PHP best practices, the `vendor/` directory is excluded from version control:

- **Smaller repository size** - No need to store thousands of dependency files
- **Faster clones and pulls** - Only your code is tracked
- **Consistent dependencies** - `composer.lock` ensures everyone gets the same versions
- **Security** - Dependencies are always fresh from official sources

## Dependencies

This project requires:
- **PHP >=7.4**
- **Composer** (for dependency management)

Main dependencies (auto-installed):
- `asgrim/ofxparser` ^1.2 - OFX/QFX file parsing
- `mimographix/qif-library` ^1.0 - QIF file parsing

Dev dependencies (auto-installed):
- `phpunit/phpunit` ^9.6 - Unit testing
- `codeception/codeception` ^4.2 - Integration testing

## Troubleshooting

### "Class not found" errors

**Solution:** Run `composer install`

### Outdated dependencies

**Solution:** Run `composer update`

### Clear composer cache

**Solution:** 
```bash
composer clear-cache
composer install
```

## Development Workflow

```bash
# 1. Pull latest changes
git pull origin main

# 2. Install/update dependencies (automatic with hook, or run manually)
composer install

# 3. Run tests
composer test

# 4. Make your changes...

# 5. Run tests again
composer test

# 6. Commit and push
git add .
git commit -m "Your changes"
git push origin main
```

## CI/CD Integration

For continuous integration, ensure your CI pipeline includes:

```yaml
# Example GitHub Actions
- name: Install dependencies
  run: composer install --no-dev --optimize-autoloader

# Example GitLab CI
composer:
  script:
    - composer install --no-dev --optimize-autoloader
```

## Questions?

See the main README.md or contact the maintainer.
