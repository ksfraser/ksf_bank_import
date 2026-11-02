@echo off
REM Systematic File Replacement Script for Windows
REM Run this from the project root directory

set WORKING_DIR=C:\Users\prote\Documents\bank_import
set CURRENT_DIR=%CD%

echo Systematic File Replacement Tool
echo ================================
echo Working directory: %WORKING_DIR%
echo Current directory: %CURRENT_DIR%
echo.

if "%1"=="list" goto list_files
if "%1"=="diff" goto show_diff
if "%1"=="replace" goto replace_file
if "%1"=="commit" goto commit_changes
goto show_help

:list_files
echo Files that differ between working and current versions:
echo ======================================================
for /r %%f in (*.php) do (
    set "file=%%f"
    setlocal enabledelayedexpansion
    set "rel_file=!file:%CD%\=!"
    if exist "%WORKING_DIR%\!rel_file!" (
        fc /b "!file!" "%WORKING_DIR%\!rel_file!" >nul 2>&1
        if errorlevel 1 echo DIFF: !rel_file!
    ) else (
        echo NEW:  !rel_file!
    )
    endlocal
)
goto end

:show_diff
if "%2"=="" (
    echo Usage: %0 diff ^<file^>
    goto end
)
if exist "%WORKING_DIR%\%2" if exist "%2" (
    echo === DIFF for %2 ===
    git diff --no-index "%2" "%WORKING_DIR%\%2" | head -50
    echo === END DIFF ===
) else (
    echo Cannot show diff for %2 - missing file(s)
)
goto end

:replace_file
if "%2"=="" goto replace_usage
if "%3"=="" goto replace_usage

echo Replacing %2 (Category: %3)
echo Reason: %4

REM Backup current version
if exist "%2" (
    copy "%2" "%2.backup" >nul
    echo Backup created: %2.backup
)

REM Copy working version
if exist "%WORKING_DIR%\%2" (
    copy "%WORKING_DIR%\%2" "%2" >nul
    echo Replaced with working version
    git add "%2"

    REM Log the replacement
    echo ## %DATE% %TIME% >> systematic_replacement\REPLACEMENT_TRACKER.md
    echo - **%2**: %3 - %4 >> systematic_replacement\REPLACEMENT_TRACKER.md
    echo. >> systematic_replacement\REPLACEMENT_TRACKER.md
) else (
    echo ERROR: Working version of %2 not found
)
goto end

:replace_usage
echo Usage: %0 replace ^<file^> ^<category^> [reason]
echo Categories: BROKEN_FUNCTIONALITY, COMPATIBILITY_ISSUES, REFACTORING_IMPROVEMENTS, NEW_FEATURES
goto end

:commit_changes
echo Committing current replacements...
for /f %%c in ('git diff --cached --name-only ^| find /c /v ""') do set FILE_COUNT=%%c
git diff --cached --name-only > temp_files.txt
git commit -m "Systematic file replacement: %FILE_COUNT% files\n\n$(type temp_files.txt)\n\nSee systematic_replacement/REPLACEMENT_TRACKER.md for details"
del temp_files.txt
goto end

:show_help
echo Systematic File Replacement Tool
echo ================================
echo.
echo Usage:
echo   %0 list                    - List all files that differ
echo   %0 diff ^<file^>           - Show diff for a specific file
echo   %0 replace ^<file^> ^<cat^> - Replace file with working version
echo   %0 commit                  - Commit current replacements
echo.
echo Categories:
echo   BROKEN_FUNCTIONALITY     - Bugs preventing operation
echo   COMPATIBILITY_ISSUES     - PHP/environment issues
echo   REFACTORING_IMPROVEMENTS - Better structure (keep if working)
echo   NEW_FEATURES             - Additional functionality
echo.
echo First, edit WORKING_DIR variable to point to your working version directory
goto end

:end