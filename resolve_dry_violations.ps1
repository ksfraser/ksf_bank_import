# DRY Violation Resolution Script
# SAFE MODE: Moves duplicates to backup before deletion
# Execute this from repository root

Write-Host "=== DRY VIOLATION RESOLUTION ===" -ForegroundColor Cyan
Write-Host "Date: $(Get-Date)" -ForegroundColor Cyan
Write-Host ""

# Create backup directory
$backupDir = ".\duplicates_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Write-Host "==> Created backup directory: $backupDir" -ForegroundColor Green
Write-Host ""

# CRITICAL: Files are DIFFERENT between root and src/
# ROOT versions are actively used (require_once paths point to root)
# Before deleting, backup src/ versions for comparison

Write-Host "PHASE 1: Backup src/ duplicates of module-specific files" -ForegroundColor Yellow
Write-Host "(These will be deleted after backup)" -ForegroundColor Yellow
Write-Host ""

$moduleSpecificInSrc = @(
    "src/Ksfraser/FaBankImport/process_statements.php",
    "src/Ksfraser/FaBankImport/import_statements.php",
    "src/Ksfraser/FaBankImport/view_statements.php",
    "src/Ksfraser/FaBankImport/class.transactions_table.php",
    "src/Ksfraser/FaBankImport/class.ViewBiLineItems.php",
    "src/Ksfraser/FaBankImport/class.bi_lineitem.php",
    "src/Ksfraser/FaBankImport/class.bank_import_controller.php",
    "src/Ksfraser/FaBankImport/class.bi_transactions.php",
    "src/Ksfraser/FaBankImport/class.bi_statements.php",
    "src/Ksfraser/FaBankImport/class.bi_counterparty_model.php",
    "src/Ksfraser/FaBankImport/class.bi_transactionTitle_model.php",
    "src/Ksfraser/FaBankImport/class.bi_partners_data.php"
)

foreach ($file in $moduleSpecificInSrc) {
    if (Test-Path $file) {
        $backupPath = Join-Path $backupDir (Split-Path $file -Leaf)
        Copy-Item $file $backupPath
        Write-Host "  [BACKUP] Backed up: $file" -ForegroundColor Cyan
        
        # Now safe to delete
        Remove-Item $file
        Write-Host "  [DELETE] Deleted: $file (ROOT version is active)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "PHASE 2: Delete views/ duplicate" -ForegroundColor Yellow
Write-Host ""

if (Test-Path "views/class.bi_lineitem.php") {
    $backupPath = Join-Path $backupDir "views_class.bi_lineitem.php"
    Copy-Item "views/class.bi_lineitem.php" $backupPath
    Write-Host "  [BACKUP] Backed up: views/class.bi_lineitem.php" -ForegroundColor Cyan
    
    Remove-Item "views/class.bi_lineitem.php"
    Write-Host "  [DELETE] Deleted: views/class.bi_lineitem.php (ROOT version is active)" -ForegroundColor Red
}

Write-Host ""
Write-Host "PHASE 3: Handle library code duplicates" -ForegroundColor Yellow
Write-Host "(Parsers should be in src/, but need to verify if root is different)" -ForegroundColor Yellow
Write-Host ""

$libraryInRoot = @(
    "class.AbstractQfxParser.php",
    "class.CibcQfxParser.php",
    "class.ManuQfxParser.php",
    "class.PcmcQfxParser.php",
    "class.QfxParserFactory.php"
)

foreach ($file in $libraryInRoot) {
    $rootPath = ".\$file"
    $srcPath = ".\src\Ksfraser\FaBankImport\$file"
    
    if ((Test-Path $rootPath) -and (Test-Path $srcPath)) {
        $rootHash = (Get-FileHash $rootPath).Hash
        $srcHash = (Get-FileHash $srcPath).Hash
        
        if ($rootHash -eq $srcHash) {
            # Identical - safe to delete root
            Write-Host "  [OK] $file - IDENTICAL" -ForegroundColor Green
            Write-Host "     Deleting root version (keeping src/ for library)" -ForegroundColor Gray
            Remove-Item $rootPath
        } else {
            # Different - need manual review
            Write-Host "  [!] $file - DIFFERENT!" -ForegroundColor Red
            Write-Host "     Backing up BOTH versions for manual review" -ForegroundColor Yellow
            
            $backupPathRoot = Join-Path $backupDir "ROOT_$file"
            $backupPathSrc = Join-Path $backupDir "SRC_$file"
            Copy-Item $rootPath $backupPathRoot
            Copy-Item $srcPath $backupPathSrc
            
            Write-Host "     [BACKUP] Backed up both - MANUAL REVIEW REQUIRED" -ForegroundColor Yellow
            Write-Host "     NOT deleting automatically - user decision needed" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "PHASE 4: Clean backup/dev files" -ForegroundColor Yellow
Write-Host ""

# Backup files (*.php~)
Get-ChildItem -Recurse -Filter "*.php~" | Where-Object { $_.FullName -notmatch "vendor" } | ForEach-Object {
    $backupPath = Join-Path $backupDir $_.Name
    Copy-Item $_.FullName $backupPath
    Write-Host "  [BACKUP] Backed up: $($_.Name)" -ForegroundColor Cyan
    
    Remove-Item $_.FullName
    Write-Host "  [DELETE] Deleted: $($_.FullName)" -ForegroundColor Red
}

# Dev files (*.copilot.php)
Get-ChildItem -Recurse -Filter "*.copilot.php" | Where-Object { $_.FullName -notmatch "vendor" } | ForEach-Object {
    $backupPath = Join-Path $backupDir $_.Name
    Copy-Item $_.FullName $backupPath
    Write-Host "  [BACKUP] Backed up: $($_.Name)" -ForegroundColor Cyan
    
    Remove-Item $_.FullName
    Write-Host "  [DELETE] Deleted: $($_.FullName)" -ForegroundColor Red
}

Write-Host ""
Write-Host "PHASE 5: Clean legacy/old files" -ForegroundColor Yellow
Write-Host ""

$legacyFiles = @(
    "import_statements-old.php",
    "src/Ksfraser/FaBankImport/import_statements-old.php",
    "process_statements_preclean.php",
    "src/Ksfraser/FaBankImport/process_statements_preclean.php",
    "process_statements.copilot_refactored.php",
    "src/Ksfraser/FaBankImport/process_statements.copilot_refactored.php",
    "process_statements.php~",
    "src/Ksfraser/FaBankImport/process_statements.php~"
)

foreach ($file in $legacyFiles) {
    if (Test-Path $file) {
        $backupPath = Join-Path $backupDir (Split-Path $file -Leaf)
        Copy-Item $file $backupPath
        Write-Host "  [BACKUP] Backed up: $file" -ForegroundColor Cyan
        
        Remove-Item $file
        Write-Host "  [DELETE] Deleted: $file (legacy/unused)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "PHASE 6: Update .gitignore" -ForegroundColor Yellow
Write-Host ""

$gitignoreEntries = @"

# Backup and development files (added by DRY cleanup)
*.php~
*.copilot.php
*-old.php
*.bak
"@

Add-Content -Path .gitignore -Value $gitignoreEntries
Write-Host "[OK] Added backup/dev file patterns to .gitignore" -ForegroundColor Green

Write-Host ""
Write-Host "=== CLEANUP SUMMARY ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "[OK] Backup created: $backupDir" -ForegroundColor Green
Write-Host "[OK] Module-specific duplicates removed from src/" -ForegroundColor Green
Write-Host "[OK] views/ duplicate removed" -ForegroundColor Green
Write-Host "[OK] Backup/dev files removed" -ForegroundColor Green
Write-Host "[OK] Legacy files removed" -ForegroundColor Green
Write-Host "[OK] .gitignore updated" -ForegroundColor Green
Write-Host ""
Write-Host "[!] MANUAL REVIEW NEEDED:" -ForegroundColor Yellow
Write-Host "   - Parser files (class.*QfxParser.php) may have differences" -ForegroundColor Yellow
Write-Host "   - Check $backupDir for any ROOT_* and SRC_* files" -ForegroundColor Yellow
Write-Host "   - Compare and decide which version to keep" -ForegroundColor Yellow
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Review backup directory for any differences" -ForegroundColor White
Write-Host "2. Run regression tests: composer exec phpunit" -ForegroundColor White
Write-Host "3. Test module functionality in FA" -ForegroundColor White
Write-Host "4. If all tests pass, commit changes: git add -A && git commit -m 'Fix DRY violations - remove duplicate files'" -ForegroundColor White
Write-Host "5. If issues found, restore from: $backupDir" -ForegroundColor White
Write-Host ""
Write-Host "Files remaining after cleanup:" -ForegroundColor Cyan
Write-Host ""
Write-Host "ROOT (Module-specific):" -ForegroundColor Green
Get-ChildItem -Filter "*.php" | Where-Object { $_.Name -match "^(class\.|process_|import_|view_|hooks\.)" } | ForEach-Object {
    Write-Host "  + $($_.Name)" -ForegroundColor Gray
}
Write-Host ""
Write-Host "SRC (Library code):" -ForegroundColor Green
Get-ChildItem -Path "src/Ksfraser/FaBankImport" -Filter "*.php" | Where-Object { $_.Name -match "^class\." } | ForEach-Object {
    Write-Host "  + $($_.Name)" -ForegroundColor Gray
}
Write-Host ""
Write-Host "==> DRY Resolution Complete!" -ForegroundColor Green
