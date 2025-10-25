# Build and Upload Guide for smashburger.tz
## (When Server Doesn't Have Node.js)

---

## 🎯 **THE SIMPLE STEPS:**

### **STEP 1: Build Assets on Your Local Computer**

Run this command in your project folder:

```bash
build-for-smashburger-tz.bat
```

This will:
- ✅ Temporarily update your .env to use `smashburger.tz`
- ✅ Build production assets with correct domain
- ✅ Restore your original .env afterward

**Files created:**
- `public/js/app.js` (your compiled JavaScript)
- `public/css/app.css` (your compiled CSS)
- `public/mix-manifest.json` (asset manifest)

---

### **STEP 2: Upload to Server**

Upload these **3 files** to your server at `smashburger.tz`:

```
Local                        →  Server
----------------------------------------
public/js/app.js            →  public/js/app.js
public/css/app.css          →  public/css/app.css
public/mix-manifest.json    →  public/mix-manifest.json
```

**Upload using:**
- FileZilla (SFTP/FTP)
- cPanel File Manager
- WinSCP
- Or any FTP client

---

### **STEP 3: Update Server .env and Clear Caches**

SSH into your server and run:

```bash
# Go to project directory
cd /home/your-username/public_html
# (or wherever your Laravel project is)

# Update .env file
sed -i 's/smashburgertz\.com/smashburger.tz/g' .env

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache for performance
php artisan config:cache
php artisan route:cache
```

---

### **STEP 4: Test!**

1. **Clear your browser cache** (Ctrl+Shift+Delete)
2. Visit: `https://smashburger.tz`
3. Add product to cart
4. Checkout and place order
5. Payment should redirect to: `https://smashburger.tz/payment/XX/pay` ✅

---

## 🔍 **TROUBLESHOOTING:**

### **If payment still goes to old domain:**

1. Check if you uploaded the files to the correct location
2. Clear browser cache (hard refresh: Ctrl+Shift+R)
3. Check `public/js/app.js` on server - search for "smashburgertz" - should NOT be there

### **If build fails locally:**

Make sure you have Node.js installed:
```bash
node --version
npm --version
```

If not installed, download from: https://nodejs.org/

Then run:
```bash
npm install
npm run production
```

---

## 💡 **WHAT'S HAPPENING:**

The issue is in: `resources/js/components/frontend/checkout/CheckoutComponent.vue`

Line 903:
```javascript
window.location.href = env.API_URL + "/payment/" + orderResponse.data.data.id + "/pay";
```

When you run `npm run production`, it reads `MIX_HOST` from your `.env` file and compiles it into the JavaScript.

**Before build:**
- `.env` has: `MIX_HOST=https://smashburgertz.com`
- Result: Payment goes to `smashburgertz.com` ❌

**After build with correct domain:**
- `.env` has: `MIX_HOST=https://smashburger.tz`
- Result: Payment goes to `smashburger.tz` ✅

---

## 📝 **ALTERNATIVE: Manual Build**

If you prefer not to use the script:

```bash
# 1. Temporarily update your local .env
#    Change: APP_URL=https://smashburger.tz
#    Replace all: smashburgertz.com → smashburger.tz

# 2. Build
npm run production

# 3. Upload the 3 files mentioned above

# 4. Restore your local .env if needed
```

---

## ✅ **YOU'RE DONE!**

After these steps, your payment redirects will work correctly on `smashburger.tz`.

