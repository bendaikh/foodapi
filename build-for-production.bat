@echo off
echo ================================================
echo Building Assets for Production
echo ================================================
echo.

echo Backing up current .env file...
copy .env .env.local.backup

echo.
echo Updating APP_URL and MIX_HOST to production URL (https://smashburger.tz)...
powershell -Command "$content = Get-Content .env; $content = $content -replace 'APP_URL=http://127.0.0.1:8000', 'APP_URL=https://smashburger.tz'; $content = $content -replace 'APP_URL=https://smashburgertz.com', 'APP_URL=https://smashburger.tz'; $content = $content -replace 'MIX_HOST=http://127.0.0.1:8000', 'MIX_HOST=https://smashburger.tz'; $content = $content -replace 'MIX_HOST=https://smashburgertz.com', 'MIX_HOST=https://smashburger.tz'; $content | Set-Content .env"

echo.
echo Installing dependencies...
call npm install

echo.
echo Building production assets...
call npm run production

echo.
echo Restoring local .env file...
copy .env.local.backup .env
del .env.local.backup

echo.
echo ================================================
echo Build Complete!
echo ================================================
echo.
echo The production assets are in the public/js and public/css folders.
echo Upload these files to your Hostinger server:
echo - public/js/app.js
echo - public/css/app.css
echo - public/mix-manifest.json
echo.
pause

