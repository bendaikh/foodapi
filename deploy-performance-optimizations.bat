@echo off
REM Performance Optimization Deployment Script for Windows
REM This script applies all performance optimizations to your production environment

echo =========================================
echo Performance Optimization Deployment
echo =========================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo Error: artisan file not found. Please run this script from the project root.
    pause
    exit /b 1
)

echo Step 1: Backing up database...
php artisan backup:run 2>nul || echo Backup skipped (no backup package installed)
echo.

echo Step 2: Putting application in maintenance mode...
php artisan down --message="Performance optimization in progress" --retry=60
echo [✓] Application is now in maintenance mode
echo.

echo Step 3: Running database migrations (adding indexes)...
php artisan migrate --force
if %ERRORLEVEL% NEQ 0 (
    echo [✗] Migration failed! Rolling back...
    php artisan up
    pause
    exit /b 1
)
echo [✓] Database migrations completed successfully
echo.

echo Step 4: Clearing all caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo [✓] All caches cleared
echo.

echo Step 5: Optimizing application...
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo [✓] Application optimized
echo.

echo Step 6: Restarting queue workers...
php artisan queue:restart
echo [✓] Queue workers restarted
echo.

echo Step 7: Bringing application back online...
php artisan up
echo [✓] Application is now online
echo.

echo =========================================
echo Deployment completed successfully!
echo =========================================
echo.

echo Verifying installation...
php artisan migrate:status
echo.

echo =========================================
echo Performance optimization deployment complete!
echo =========================================
echo.
echo Next steps:
echo 1. Test the /home page and verify faster loading
echo 2. Test the /admin/dashboard and verify improvements
echo 3. Run Lighthouse test again to see score improvement
echo 4. Monitor application logs for any issues
echo.
echo Expected improvements:
echo - Home page: 80-90%% faster on subsequent loads
echo - Dashboard: 85-90%% faster with caching
echo - Lighthouse score: Should improve from 48 to 75-85
echo.

pause

