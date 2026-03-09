# Temporarily Disabled Dependencies

## OFX Parser Dependency

The `asgrim/ofxparser` dependency has been temporarily removed from `composer.json` due to PHP version compatibility issues.

**Removed dependency:**
```json
"asgrim/ofxparser": "^1.2"
```

**Reason:** The package only supports PHP 5.6-7.0, but the current environment uses PHP 8.4.

**To restore for UAT/production:**
1. Add the dependency back to the `require` section in `composer.json`
2. Change the platform config back to PHP 7.3:
   ```json
   "config": {
       "platform": {
           "php": "7.3.0"
       }
   }
   ```
3. Run `composer install`

**Note:** This dependency is used for parsing OFX bank statement files.