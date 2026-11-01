#!/bin/bash
################################################################################
# Migration Script: Switch from asgrim/ofxparser to ksf_ofxparser
################################################################################
#
# PURPOSE:
#   Automates the migration from asgrim/ofxparser to ksfraser/ksf_ofxparser
#   on production Linux servers.
#
# USAGE:
#   ./migrate_to_ksf_ofxparser.sh [OPTIONS]
#
# OPTIONS:
#   --environment ENV    Target environment: dev or prod (default: prod)
#   --skip-clone        Skip git clone if ksf_ofxparser already exists
#   --git-repo URL      GitHub repository URL (default: https://github.com/ksfraser/ksf_ofxparser.git)
#   --help              Show this help message
#
# STEPS:
#   1. Clone/update ksf_ofxparser from GitHub
#   2. Update root composer.json
#   3. Update includes/composer.json  
#   4. Run composer update in root
#   5. Run composer update in includes/
#   6. Update hardcoded include statements
#   7. Verify installation
#
# EXAMPLES:
#   # Production migration
#   ./migrate_to_ksf_ofxparser.sh --environment prod
#
#   # Development migration (skip clone if already exists)
#   ./migrate_to_ksf_ofxparser.sh --environment dev --skip-clone
#
#   # Custom git repository
#   ./migrate_to_ksf_ofxparser.sh --git-repo https://github.com/user/fork.git
#
################################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default parameters
ENVIRONMENT="prod"
SKIP_CLONE=false
GIT_REPO="https://github.com/ksfraser/ksf_ofxparser.git"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --environment)
            ENVIRONMENT="$2"
            shift 2
            ;;
        --skip-clone)
            SKIP_CLONE=true
            shift
            ;;
        --git-repo)
            GIT_REPO="$2"
            shift 2
            ;;
        --help)
            sed -n '2,/^################################################################################$/p' "$0" | sed 's/^# \?//'
            exit 0
            ;;
        *)
            echo -e "${RED}ERROR: Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Validate environment
if [[ "$ENVIRONMENT" != "dev" && "$ENVIRONMENT" != "prod" ]]; then
    echo -e "${RED}ERROR: Environment must be 'dev' or 'prod'${NC}"
    exit 1
fi

# Display script header
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   OFX Parser Migration: asgrim → ksf_ofxparser                 ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Environment:${NC} $ENVIRONMENT"
echo -e "${YELLOW}Git Repository:${NC} $GIT_REPO"
echo -e "${YELLOW}Skip Clone:${NC} $SKIP_CLONE"
echo ""

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR" || exit 1

################################################################################
# STEP 1: Clone/Update ksf_ofxparser Repository
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 1: Clone/Update ksf_ofxparser Repository${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

LIB_DIR="$SCRIPT_DIR/lib"
KSF_DIR="$LIB_DIR/ksf_ofxparser"

# Create lib directory if it doesn't exist
if [[ ! -d "$LIB_DIR" ]]; then
    echo -e "${YELLOW}Creating lib directory...${NC}"
    mkdir -p "$LIB_DIR" || {
        echo -e "${RED}ERROR: Failed to create lib directory${NC}"
        exit 1
    }
fi

# Clone or update repository
if [[ -d "$KSF_DIR" ]]; then
    if $SKIP_CLONE; then
        echo -e "${GREEN}✓ Using existing ksf_ofxparser directory (skip-clone enabled)${NC}"
    else
        echo -e "${YELLOW}Updating existing ksf_ofxparser repository...${NC}"
        cd "$KSF_DIR" || exit 1
        git pull origin main || {
            echo -e "${RED}ERROR: Failed to update repository${NC}"
            exit 1
        }
        cd "$SCRIPT_DIR" || exit 1
        echo -e "${GREEN}✓ Repository updated successfully${NC}"
    fi
else
    echo -e "${YELLOW}Cloning ksf_ofxparser from GitHub...${NC}"
    git clone "$GIT_REPO" "$KSF_DIR" || {
        echo -e "${RED}ERROR: Failed to clone repository${NC}"
        echo -e "${YELLOW}Verify git is installed and you have network access${NC}"
        exit 1
    }
    echo -e "${GREEN}✓ Repository cloned successfully${NC}"
fi

echo ""

################################################################################
# STEP 2: Update Root composer.json
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 2: Update Root composer.json${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

ROOT_COMPOSER="$SCRIPT_DIR/composer.json"

if [[ ! -f "$ROOT_COMPOSER" ]]; then
    echo -e "${RED}ERROR: Root composer.json not found${NC}"
    exit 1
fi

echo -e "${YELLOW}Backing up root composer.json...${NC}"
cp "$ROOT_COMPOSER" "$ROOT_COMPOSER.backup" || {
    echo -e "${RED}ERROR: Failed to backup composer.json${NC}"
    exit 1
}

# Check if jq is available for JSON manipulation
if command -v jq &> /dev/null; then
    echo -e "${YELLOW}Using jq to update composer.json...${NC}"
    
    # Add path repository if not exists
    jq '.repositories |= if . then . else [] end | 
        .repositories |= if any(.url == "./lib/ksf_ofxparser") then . 
        else . + [{"type": "path", "url": "./lib/ksf_ofxparser", "options": {"symlink": false}}] end' \
        "$ROOT_COMPOSER" > "$ROOT_COMPOSER.tmp" || {
        echo -e "${RED}ERROR: Failed to add repository${NC}"
        exit 1
    }
    mv "$ROOT_COMPOSER.tmp" "$ROOT_COMPOSER"
    
    # Replace asgrim with ksf_ofxparser
    jq 'if .require["asgrim/ofxparser"] then 
            .require["ksfraser/ksf_ofxparser"] = "@dev" | 
            del(.require["asgrim/ofxparser"]) 
        else 
            .require["ksfraser/ksf_ofxparser"] = "@dev" 
        end' \
        "$ROOT_COMPOSER" > "$ROOT_COMPOSER.tmp" || {
        echo -e "${RED}ERROR: Failed to update require section${NC}"
        exit 1
    }
    mv "$ROOT_COMPOSER.tmp" "$ROOT_COMPOSER"
    
    echo -e "${GREEN}✓ Root composer.json updated successfully${NC}"
else
    echo -e "${YELLOW}WARNING: jq not found, using manual JSON editing${NC}"
    echo -e "${YELLOW}Please manually verify composer.json contains:${NC}"
    echo -e "  ${BLUE}repositories: {\"type\": \"path\", \"url\": \"./lib/ksf_ofxparser\"}${NC}"
    echo -e "  ${BLUE}require: {\"ksfraser/ksf_ofxparser\": \"@dev\"}${NC}"
    
    # Basic sed replacement (less reliable)
    if grep -q "asgrim/ofxparser" "$ROOT_COMPOSER"; then
        sed -i.bak 's/"asgrim\/ofxparser"/"ksfraser\/ksf_ofxparser"/g' "$ROOT_COMPOSER"
        echo -e "${YELLOW}Replaced asgrim/ofxparser with ksfraser/ksf_ofxparser${NC}"
    fi
fi

echo ""

################################################################################
# STEP 3: Update includes/composer.json
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 3: Update includes/composer.json${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

INCLUDES_COMPOSER="$SCRIPT_DIR/includes/composer.json"

if [[ ! -f "$INCLUDES_COMPOSER" ]]; then
    echo -e "${YELLOW}WARNING: includes/composer.json not found (skipping)${NC}"
else
    echo -e "${YELLOW}Backing up includes/composer.json...${NC}"
    cp "$INCLUDES_COMPOSER" "$INCLUDES_COMPOSER.backup" || {
        echo -e "${RED}ERROR: Failed to backup includes/composer.json${NC}"
        exit 1
    }
    
    if command -v jq &> /dev/null; then
        echo -e "${YELLOW}Using jq to update includes/composer.json...${NC}"
        
        # Add path repository (note: ../lib/ksf_ofxparser from includes/)
        jq '.repositories |= if . then . else [] end | 
            .repositories |= if any(.url == "../lib/ksf_ofxparser") then . 
            else . + [{"type": "path", "url": "../lib/ksf_ofxparser", "options": {"symlink": false}}] end' \
            "$INCLUDES_COMPOSER" > "$INCLUDES_COMPOSER.tmp" || {
            echo -e "${RED}ERROR: Failed to add repository${NC}"
            exit 1
        }
        mv "$INCLUDES_COMPOSER.tmp" "$INCLUDES_COMPOSER"
        
        # Replace asgrim with ksf_ofxparser
        jq 'if .require["asgrim/ofxparser"] then 
                .require["ksfraser/ksf_ofxparser"] = "@dev" | 
                del(.require["asgrim/ofxparser"]) 
            else 
                .require["ksfraser/ksf_ofxparser"] = "@dev" 
            end' \
            "$INCLUDES_COMPOSER" > "$INCLUDES_COMPOSER.tmp" || {
            echo -e "${RED}ERROR: Failed to update require section${NC}"
            exit 1
        }
        mv "$INCLUDES_COMPOSER.tmp" "$INCLUDES_COMPOSER"
        
        echo -e "${GREEN}✓ includes/composer.json updated successfully${NC}"
    else
        # Basic sed replacement
        if grep -q "asgrim/ofxparser" "$INCLUDES_COMPOSER"; then
            sed -i.bak 's/"asgrim\/ofxparser"/"ksfraser\/ksf_ofxparser"/g' "$INCLUDES_COMPOSER"
            echo -e "${YELLOW}Replaced asgrim/ofxparser with ksfraser/ksf_ofxparser${NC}"
        fi
    fi
fi

echo ""

################################################################################
# STEP 4: Run Composer Update (Root)
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 4: Run Composer Update (Root)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

if ! command -v composer &> /dev/null; then
    echo -e "${RED}ERROR: Composer is not installed or not in PATH${NC}"
    exit 1
fi

echo -e "${YELLOW}Clearing composer cache...${NC}"
composer clear-cache

echo -e "${YELLOW}Running composer update in root directory...${NC}"
composer update ksfraser/ksf_ofxparser --with-dependencies || {
    echo -e "${RED}ERROR: Composer update failed${NC}"
    echo -e "${YELLOW}Restoring backup...${NC}"
    mv "$ROOT_COMPOSER.backup" "$ROOT_COMPOSER"
    exit 1
}

echo -e "${GREEN}✓ Root composer update completed successfully${NC}"
echo ""

################################################################################
# STEP 5: Run Composer Update (includes/)
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 5: Run Composer Update (includes/)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

if [[ -f "$INCLUDES_COMPOSER" ]]; then
    echo -e "${YELLOW}Running composer update in includes/ directory...${NC}"
    cd "$SCRIPT_DIR/includes" || exit 1
    
    composer update ksfraser/ksf_ofxparser --with-dependencies || {
        echo -e "${RED}ERROR: Composer update failed in includes/${NC}"
        echo -e "${YELLOW}Restoring backup...${NC}"
        cd "$SCRIPT_DIR" || exit 1
        mv "$INCLUDES_COMPOSER.backup" "$INCLUDES_COMPOSER"
        exit 1
    }
    
    cd "$SCRIPT_DIR" || exit 1
    echo -e "${GREEN}✓ includes/ composer update completed successfully${NC}"
else
    echo -e "${YELLOW}No includes/composer.json found (skipping)${NC}"
fi

echo ""

################################################################################
# STEP 6: Update Hardcoded Include Statements
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 6: Update Hardcoded Include Statements${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}Searching for hardcoded 'includes/vendor/autoload.php' references...${NC}"

# Find PHP files with includes/vendor/autoload.php
FILES_TO_UPDATE=$(grep -rl "includes/vendor/autoload\.php" --include="*.php" "$SCRIPT_DIR" 2>/dev/null | grep -v ".backup")

if [[ -z "$FILES_TO_UPDATE" ]]; then
    echo -e "${GREEN}✓ No hardcoded includes/vendor paths found${NC}"
else
    echo -e "${YELLOW}Found files to update:${NC}"
    echo "$FILES_TO_UPDATE"
    echo ""
    
    # Update each file
    while IFS= read -r file; do
        echo -e "${YELLOW}Updating: $file${NC}"
        
        # Backup file
        cp "$file" "$file.backup" || {
            echo -e "${RED}ERROR: Failed to backup $file${NC}"
            continue
        }
        
        # Replace includes/vendor with vendor
        sed -i.tmp 's|includes/vendor/autoload\.php|vendor/autoload.php|g' "$file"
        rm -f "$file.tmp"
        
        echo -e "${GREEN}✓ Updated: $file${NC}"
    done <<< "$FILES_TO_UPDATE"
fi

echo ""

################################################################################
# STEP 7: Verify Installation
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}STEP 7: Verify Installation${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}Creating verification script...${NC}"

cat > /tmp/verify_ksf_ofxparser.php << 'EOPHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to load autoloader
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    echo "ERROR: Autoloader not found at: $autoload\n";
    exit(1);
}

require_once $autoload;

echo "Verification Tests:\n";
echo "==================\n\n";

// Test 1: Check if Parser class exists
echo "1. OfxParser\\Parser class: ";
if (class_exists('OfxParser\\Parser')) {
    echo "✓ FOUND\n";
} else {
    echo "✗ NOT FOUND\n";
    exit(1);
}

// Test 2: Check if CurrencyElement exists (ksf_ofxparser specific)
echo "2. OfxParser\\Sgml\\Elements\\CurrencyElement class: ";
if (class_exists('OfxParser\\Sgml\\Elements\\CurrencyElement')) {
    echo "✓ FOUND\n";
} else {
    echo "✗ NOT FOUND\n";
    exit(1);
}

// Test 3: Check installed packages
echo "3. Installed packages:\n";
$packages = [];
$installed_file = __DIR__ . '/../vendor/composer/installed.json';
if (file_exists($installed_file)) {
    $installed = json_decode(file_get_contents($installed_file), true);
    if (isset($installed['packages'])) {
        foreach ($installed['packages'] as $package) {
            if (strpos($package['name'], 'ofxparser') !== false) {
                $packages[] = "   - {$package['name']} ({$package['version']})";
            }
        }
    }
}
echo implode("\n", $packages) . "\n\n";

// Test 4: Verify it's NOT asgrim
echo "4. Checking for asgrim/ofxparser: ";
$found_asgrim = false;
foreach ($packages as $pkg) {
    if (strpos($pkg, 'asgrim/ofxparser') !== false) {
        $found_asgrim = true;
        break;
    }
}
if (!$found_asgrim) {
    echo "✓ NOT FOUND (correct)\n";
} else {
    echo "✗ STILL INSTALLED (should be removed)\n";
    exit(1);
}

echo "\n✓ All verification tests passed!\n";
exit(0);
EOPHP

chmod +x /tmp/verify_ksf_ofxparser.php

echo -e "${YELLOW}Running verification script...${NC}"
echo ""

cd "$SCRIPT_DIR" || exit 1
php /tmp/verify_ksf_ofxparser.php

VERIFY_EXIT=$?

if [[ $VERIFY_EXIT -eq 0 ]]; then
    echo ""
    echo -e "${GREEN}✓ Installation verified successfully!${NC}"
else
    echo ""
    echo -e "${RED}✗ Verification failed!${NC}"
    exit 1
fi

################################################################################
# COMPLETION SUMMARY
################################################################################

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    MIGRATION COMPLETE                          ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Successfully migrated from asgrim/ofxparser to ksf_ofxparser!${NC}"
echo ""
echo -e "${YELLOW}Summary:${NC}"
echo -e "  ✓ Step 1: Repository cloned/updated"
echo -e "  ✓ Step 2: Root composer.json updated"
echo -e "  ✓ Step 3: includes/composer.json updated"
echo -e "  ✓ Step 4: Root composer packages installed"
echo -e "  ✓ Step 5: includes/ composer packages installed"
echo -e "  ✓ Step 6: Hardcoded paths updated"
echo -e "  ✓ Step 7: Installation verified"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo -e "  1. Run your PHPUnit tests: ${BLUE}vendor/bin/phpunit${NC}"
echo -e "  2. Test OFX file parsing with production files"
echo -e "  3. Monitor application for any issues"
echo ""
echo -e "${YELLOW}Backup files created:${NC}"
echo -e "  - composer.json.backup"
if [[ -f "$INCLUDES_COMPOSER.backup" ]]; then
    echo -e "  - includes/composer.json.backup"
fi

# List any .backup files created for PHP files
BACKUP_FILES=$(find "$SCRIPT_DIR" -name "*.php.backup" -type f 2>/dev/null)
if [[ -n "$BACKUP_FILES" ]]; then
    echo -e "  - $(echo "$BACKUP_FILES" | wc -l) PHP file backups"
fi

echo ""
echo -e "${YELLOW}To rollback if needed:${NC}"
echo -e "  mv composer.json.backup composer.json"
echo -e "  composer update"
echo ""

# Cleanup temp file
rm -f /tmp/verify_ksf_ofxparser.php

exit 0
