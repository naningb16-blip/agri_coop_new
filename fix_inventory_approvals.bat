@echo off
echo ============================================================
echo Inventory Release Approval Fix Script
echo ============================================================
echo.
echo This script will fix the inventory release approval workflow
echo by ensuring the approval chain exists and creating missing
echo approval requests for pending release requests.
echo.
echo Press any key to continue or Ctrl+C to cancel...
pause > nul

echo.
echo Running fix script...
echo.

REM Check if mysql is available
where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: MySQL command line tool not found in PATH
    echo.
    echo Please either:
    echo 1. Add MySQL to your PATH environment variable, or
    echo 2. Access the fix via web browser:
    echo    http://localhost/fix_inventory_approvals.php
    echo.
    pause
    exit /b 1
)

REM Prompt for database credentials
set /p DB_USER="Enter MySQL username (default: root): "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_NAME="Enter database name (default: agri_coop): "
if "%DB_NAME%"=="" set DB_NAME=agri_coop

echo.
echo Connecting to database %DB_NAME% as %DB_USER%...
echo You will be prompted for the password.
echo.

mysql -u %DB_USER% -p %DB_NAME% < database\fix_inventory_approval_workflow.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================================
    echo SUCCESS! Inventory approval workflow has been fixed.
    echo ============================================================
    echo.
    echo Next steps:
    echo 1. Test creating a new release request from Inventory module
    echo 2. Verify it appears in GM's pending approvals
    echo 3. Test the approval workflow end-to-end
    echo.
    echo You can also run the diagnostic tool:
    echo http://localhost/check_inventory_approval.php
    echo.
) else (
    echo.
    echo ============================================================
    echo ERROR: Failed to execute fix script
    echo ============================================================
    echo.
    echo Please check:
    echo 1. Database credentials are correct
    echo 2. Database exists and is accessible
    echo 3. User has sufficient privileges
    echo.
    echo Alternatively, access the fix via web browser:
    echo http://localhost/fix_inventory_approvals.php
    echo.
)

pause
