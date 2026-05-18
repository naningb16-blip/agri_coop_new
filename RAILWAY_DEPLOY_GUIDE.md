# Railway Deployment Guide - No GitHub Needed

Deploy your app in 10 minutes without GitHub access.

---

## 🚀 Quick Start

### Step 1: Install Railway CLI (2 minutes)

**Windows:**
```powershell
# Open PowerShell as Administrator
iwr https://railway.app/install.ps1 | iex
```

**Alternative (if above fails):**
Download installer from: https://railway.app/cli

### Step 2: Create Account & Login (1 minute)

```bash
railway login
```
- Opens browser
- Sign up with email (no GitHub required)
- Authorize CLI

### Step 3: Initialize Project (1 minute)

```bash
# Go to your project folder
cd path/to/your/project

# Initialize Railway project
railway init
```
- Enter project name when asked
- Creates new Railway project

### Step 4: Set Environment Variables (3 minutes)

Copy and paste these commands **one by one**, replacing the values:

```bash
railway variables set DB_HOST=your-aiven-host.aivencloud.com

railway variables set DB_PORT=your-port-number

railway variables set DB_NAME=your-database-name

railway variables set DB_USER=your-database-user

railway variables set DB_PASSWORD=your-database-password

railway variables set DB_SSL_CA=/app/config/aiven-ca.pem

railway variables set APP_NAME="Your App Name"
```

**Get your Aiven credentials:**
1. Go to Aiven console
2. Click your MySQL service
3. Copy connection details

### Step 5: Deploy (2 minutes)

```bash
railway up
```
- Uploads all your files
- Builds and deploys
- Shows progress

### Step 6: Get Your URL (30 seconds)

```bash
railway domain
```
- Shows your app URL
- Format: `your-app.up.railway.app`

### Step 7: Open Your App (30 seconds)

```bash
railway open
```
- Opens app in browser
- Or visit the URL from step 6

---

## ✅ Verify Deployment

### Test Basic Access
1. Visit your Railway URL
2. Should see login page
3. Try logging in

### Run System Check
Visit: `https://your-app.up.railway.app/system_status_check.php`

### Run Setup Scripts (if needed)
```
https://your-app.up.railway.app/fix_sales_order_approval_chain.php
https://your-app.up.railway.app/fix_stock_return_gm_approval.php
https://your-app.up.railway.app/create_missing_stock_return_approvals.php
```

---

## 🔄 Update Your App Later

When you make code changes:

```bash
# Make your changes
# Then deploy again
railway up
```

That's it! Railway uploads and deploys your changes.

---

## 📊 Useful Commands

```bash
# View logs
railway logs

# Open dashboard
railway open

# Check status
railway status

# List environment variables
railway variables

# Add custom domain (optional)
railway domain add yourdomain.com

# Link to existing project
railway link
```

---

## 💰 Pricing

**Free Tier:**
- $5 credit per month
- Enough for small apps
- No credit card required

**Paid Plans:**
- Pay as you go
- ~$5-10/month for typical app
- Only pay for what you use

---

## 🔧 Troubleshooting

### "Command not found: railway"
**Fix:** Restart your terminal after installation

### "No project found"
**Fix:** Run `railway init` first

### "Database connection failed"
**Fix:** 
1. Check environment variables: `railway variables`
2. Verify Aiven credentials are correct
3. Make sure Aiven allows connections from anywhere

### "SSL certificate error"
**Fix:**
1. Verify `config/aiven-ca.pem` exists in your project
2. Check `DB_SSL_CA` path is `/app/config/aiven-ca.pem`

### "App not loading"
**Fix:**
1. Check logs: `railway logs`
2. Look for PHP errors
3. Verify all files uploaded

---

## 🎯 Complete Example

Here's a complete session from start to finish:

```bash
# 1. Install (PowerShell as Admin)
iwr https://railway.app/install.ps1 | iex

# 2. Login
railway login
# Browser opens, sign up, authorize

# 3. Go to project
cd C:\Users\YourName\Projects\erp-system

# 4. Initialize
railway init
# Enter name: erp-system

# 5. Set variables (replace with your values)
railway variables set DB_HOST=mysql-abc123.aivencloud.com
railway variables set DB_PORT=12345
railway variables set DB_NAME=defaultdb
railway variables set DB_USER=avnadmin
railway variables set DB_PASSWORD=your-password-here
railway variables set DB_SSL_CA=/app/config/aiven-ca.pem
railway variables set APP_NAME="ERP System"

# 6. Deploy
railway up
# Wait for upload and deployment

# 7. Get URL
railway domain
# Output: https://erp-system-production.up.railway.app

# 8. Open
railway open
# Browser opens to your app

# 9. Test
# Visit: https://erp-system-production.up.railway.app/system_status_check.php
```

---

## 📋 Checklist

- [ ] Railway CLI installed
- [ ] Logged in to Railway
- [ ] Project initialized
- [ ] All environment variables set
- [ ] Deployed with `railway up`
- [ ] Got URL with `railway domain`
- [ ] App loads in browser
- [ ] Can login successfully
- [ ] Ran system_status_check.php
- [ ] All features working

---

## 🆚 Railway vs Render

| Feature | Railway | Render |
|---------|---------|--------|
| GitHub Required | ❌ No | ✅ Yes |
| Deploy Method | CLI upload | Git push |
| Setup Time | 10 min | 15 min |
| Auto-deploy | ❌ No | ✅ Yes |
| Free Tier | $5 credit/month | 750 hours/month |
| Ease of Use | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

---

## 🎉 Success!

Once deployed, you'll have:
- ✅ Working app at Railway URL
- ✅ All code fixes deployed
- ✅ Mark as Paid button working
- ✅ GM stock return approval working
- ✅ No GitHub needed

---

## 🔗 Resources

- **Railway Docs:** https://docs.railway.app
- **Railway Discord:** https://discord.gg/railway
- **Railway Status:** https://status.railway.app
- **CLI Reference:** https://docs.railway.app/develop/cli

---

## 💡 Pro Tips

1. **Save your Railway URL** - You'll need it
2. **Bookmark the dashboard** - Easy access to logs
3. **Set up custom domain** - More professional
4. **Monitor usage** - Check your $5 credit
5. **Keep local backups** - Railway deploys from local files

---

**Total Time:** 10-15 minutes

**Difficulty:** Easy

**Cost:** Free (with $5 monthly credit)

**GitHub Required:** No ❌

---

Ready to deploy? Start with Step 1! 🚀
