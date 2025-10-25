# Domain Migration Fix Instructions
## From smashburgertz.com → smashburger.tz

---

## 🔴 **THE PROBLEM**

When you copied the app from `smashburgertz.com` to `smashburger.tz`, the compiled JavaScript files still contain hardcoded references to the old domain. That's why payment redirects go to:
- ❌ `https://smashburgertz.com/payment/13/pay`

Instead of:
- ✅ `https://smashburger.tz/payment/13/pay`

---

## ✅ **THE SOLUTION**

You have **TWO OPTIONS**:

### **OPTION 1: Automatic Fix (Recommended)**

Upload the fix script to your server and run it:

#### On Linux Server:
```bash
# Upload fix-domain-migration.sh to your server
# Then run:
chmod +x fix-domain-migration.sh
./fix-domain-migration.sh
```

#### On Windows Server:
```bash
# Upload fix-domain-migration.bat to your server
# Then run:
fix-domain-migration.bat
```

---

### **OPTION 2: Manual Fix**

If you can't run the script, follow these steps:

#### **Step 1: Update .env File**

Edit your `.env` file on the server and replace ALL instances of `smashburgertz.com` with `smashburger.tz`:

**Find and Replace:**
```
Find:    smashburgertz.com
Replace: smashburger.tz
```

This will update:
- `APP_URL`
- All PWA icon URLs (D_72x72, D_96x96, etc.)
- All splash screen URLs

**Example - Your .env should now have:**
```env
APP_URL=https://smashburger.tz
# ...
D_72x72=https://smashburger.tz/storage/94/conversions/icon-D_72x72.png
D_96x96=https://smashburger.tz/storage/94/conversions/icon-D_96x96.png
# ... and all other image URLs
```

#### **Step 2: Clear All Laravel Caches**

Run these commands on your server:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

#### **Step 3: Rebuild Frontend Assets** ⭐ **MOST IMPORTANT**

This recompiles your JavaScript with the correct domain:

**Option A: On Your Server (if Node.js is installed)**
```bash
npm run production
```

**Option B: On Your Local Machine (if server doesn't have Node.js)**
1. Update your local `.env`:
   ```env
   APP_URL=https://smashburger.tz
   MIX_HOST=https://smashburger.tz
   ```

2. Run:
   ```bash
   npm run production
   ```

3. Upload these files to your server:
   - `public/js/app.js`
   - `public/css/app.css`
   - `public/mix-manifest.json`

#### **Step 4: Optimize Laravel (Recommended)**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### **Step 5: Set Permissions (Linux only)**

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public/js public/css
```

---

## 🧪 **VERIFICATION**

1. **Clear your browser cache** (Important!)
2. Visit: `https://smashburger.tz`
3. Open browser DevTools (F12) → Network tab
4. Add a product to cart
5. Proceed to checkout and place an order
6. Check the payment URL - it should now be:
   ✅ `https://smashburger.tz/payment/XX/pay`

---

## 🔍 **TROUBLESHOOTING**

### If payment still redirects to old domain:

1. **Check compiled JavaScript:**
   - Open: `https://smashburger.tz/js/app.js`
   - Search for "smashburgertz.com"
   - If found → You need to rebuild assets (Step 3)

2. **Check .env file:**
   ```bash
   cat .env | grep smashburgertz
   ```
   - If any results → Update .env file (Step 1)

3. **Clear browser cache:**
   - Hard refresh: `Ctrl + Shift + R` (Windows/Linux)
   - Or: `Cmd + Shift + R` (Mac)

4. **Check Laravel config cache:**
   ```bash
   php artisan config:cache
   php artisan cache:clear
   ```

---

## 📝 **WHAT HAPPENS BEHIND THE SCENES**

The issue is in this file: `resources/js/components/frontend/checkout/CheckoutComponent.vue`

**Line 903:**
```javascript
window.location.href = env.API_URL + "/payment/" + orderResponse.data.data.id + "/pay";
```

The `env.API_URL` is compiled from `process.env.MIX_HOST` during the build process. When you run `npm run production`, it reads your `.env` file and bakes the URL into the JavaScript.

**Before fix:**
```javascript
window.location.href = "https://smashburgertz.com" + "/payment/" + orderResponse.data.data.id + "/pay";
```

**After fix:**
```javascript
window.location.href = "https://smashburger.tz" + "/payment/" + orderResponse.data.data.id + "/pay";
```

---

## 🚀 **FUTURE DEPLOYMENTS**

For future domain migrations or deployments, always:

1. Update `.env` with new domain
2. Run `npm run production` to rebuild assets
3. Clear all caches
4. Upload to server

**Or use the automated script provided!**

---

## 💡 **ADDITIONAL NOTES**

- This same issue affects ALL environment-specific variables that are compiled into JavaScript
- Always rebuild assets after changing `.env` variables that start with `MIX_`
- The PWA icon URLs in `.env` also need updating for proper PWA functionality

---

Need help? Check that:
- ✅ `.env` has correct domain (no smashburgertz.com references)
- ✅ `npm run production` completed successfully
- ✅ All caches cleared
- ✅ Browser cache cleared
- ✅ Uploaded new `public/js/app.js` to server

