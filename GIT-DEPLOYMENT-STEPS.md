# Git Deployment Steps for Production

## What We Fixed:
1. ✅ Removed `/public/js/*` and `/public/mix-manifest.json` from `.gitignore`
2. ✅ Compiled assets with production URL (`https://smashburgertz.com`)
3. ✅ Now you can push these files to GitHub and pull on your server

---

## Step-by-Step Deployment:

### 1. Add and Commit the Files
```bash
git add .gitignore
git add public/js/app.js
git add public/css/app.css
git add public/mix-manifest.json
git add public/serviceworker.js
git add public/storage/31/fav-icon.png
git commit -m "Build production assets with correct API URL"
```

### 2. Push to GitHub
```bash
git push origin master
```

### 3. On Your Hostinger Server (via SSH)
```bash
cd /path/to/your/website
git pull origin master
```

### 4. Update Server Environment
Make sure your server's `.env` file has:
```bash
APP_URL=https://smashburgertz.com
```

### 5. Clear Laravel Caches on Server
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 6. Set Permissions (if needed)
```bash
chmod -R 755 storage/
chmod -R 755 public/storage/
```

### 7. Test Your Website
1. Clear your browser cache (Ctrl + Shift + Delete)
2. Hard refresh (Ctrl + F5)
3. Check browser console - API calls should now go to `https://smashburgertz.com`

---

## Important Notes:

- **The compiled `app.js` file is now trackable by Git** (removed from .gitignore)
- **This is the recommended approach** for production deployments
- **Your `/public/storage/` symlink might need to be recreated** after git pull:
  ```bash
  php artisan storage:link
  ```

---

## What This Will Fix:

✅ All API calls will use `https://smashburgertz.com` instead of `http://127.0.0.1:8000`
✅ "Cannot read properties of undefined (reading 'length')" errors will disappear
✅ Service worker errors will be fixed
✅ Favicon will load correctly
✅ All data (sliders, items, categories, etc.) will load properly

---

## Troubleshooting:

If after pulling you still see errors:
1. Make sure git pull actually updated the files (check file timestamps)
2. Clear browser cache completely
3. Try incognito/private browsing mode
4. Check if `public/storage` symlink exists on server
5. Verify `.env` file has correct APP_URL on server

