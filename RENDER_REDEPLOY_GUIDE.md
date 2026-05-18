# How to Redeploy on New Render Account

This guide will help you deploy your application on a fresh Render account, which will fix the deployment issues you're experiencing.

## Prerequisites

- GitHub account with your repository
- Aiven MySQL database (keep your existing one)
- New Render account (or use existing one)

---

## Step 1: Prepare Your GitHub Repository

### 1.1 Verify Your Code is Pushed
```bash
git status
git add .
git commit -m "Final code before redeployment"
git push origin main
```

### 1.2 Note Your Repository URL
Example: `https://github.com/yourusername/your-repo-name`

---

## Step 2: Create New Render Web Service

### 2.1 Login to Render
1. Go to https://render.com
2. Login or create new account
3. Click **"New +"** button
4. Select **"Web Service"**

### 2.2 Connect GitHub Repository
1. Click **"Connect account"** if not connected
2. Authorize Render to access your GitHub
3. Select your repository from the list
4. Click **"Connect"**

### 2.3 Configure Web Service

**Basic Settings:**
- **Name:** `your-app-name` (e.g., `erp-system`)
- **Region:** Choose closest to your users
- **Branch:** `main` (or your default branch)
- **Root Directory:** Leave blank (unless your code is in a subdirectory)
- **Runtime:** `PHP`
- **Build Command:** Leave blank
- **Start Command:** Leave blank (Render auto-detects PHP)

**Instance Type:**
- Select **Free** or **Starter** (recommended for production)

---

## Step 3: Configure Environment Variables

Click **"Advanced"** and add these environment variables:

### Required Variables

```
DB_HOST=your-aiven-host.aivencloud.com
DB_PORT=your-port-number
DB_NAME=your-database-name
DB_USER=your-database-user
DB_PASSWORD=your-database-password
DB_SSL_CA=/opt/render/project/src/config/aiven-ca.pem
APP_NAME=Your App Name
BASE_URL=https://your-app-name.onrender.com
```

### How to Get Database Credentials

From your **Aiven Console**:
1. Go to your MySQL service
2. Click **"Overview"**
3. Find connection details:
   - **Host:** Service URI hostname
   - **Port:** Usually 3306 or custom port
   - **Database:** Default database name
   - **User:** avnadmin (or your user)
   - **Password:** Click "Show" to reveal

### Important Notes
- Replace `your-app-name` with your actual Render service name
- Keep your existing Aiven database - don't create a new one
- The SSL certificate path is correct for Render's file system

---

## Step 4: Deploy

1. Click **"Create Web Service"**
2. Render will start building and deploying
3. Watch the logs for any errors
4. Wait for "Live" status (usually 2-5 minutes)

---

## Step 5: Verify Deployment

### 5.1 Check Your New URL
Your app will be at: `https://your-app-name.onrender.com`

### 5.2 Test Basic Access
1. Visit your URL
2. You should see the login page
3. Try logging in

### 5.3 Run System Status Check
Visit: `https://your-app-name.onrender.com/system_status_check.php`

This will verify:
- All code fixes are deployed
- Database connection works
- All features are functional

---

## Step 6: Update DNS (Optional)

If you have a custom domain:

### 6.1 In Render Dashboard
1. Go to your web service
2. Click **"Settings"**
3. Scroll to **"Custom Domain"**
4. Click **"Add Custom Domain"**
5. Enter your domain (e.g., `erp.yourdomain.com`)

### 6.2 In Your DNS Provider
Add a CNAME record:
- **Type:** CNAME
- **Name:** erp (or your subdomain)
- **Value:** your-app-name.onrender.com
- **TTL:** 3600 (or default)

Wait 5-60 minutes for DNS propagation.

---

## Step 7: Post-Deployment Setup

### 7.1 Run Setup Scripts (if needed)

Visit these URLs to set up approval chains:

```
https://your-app-name.onrender.com/fix_sales_order_approval_chain.php
https://your-app-name.onrender.com/fix_stock_return_gm_approval.php
https://your-app-name.onrender.com/create_missing_stock_return_approvals.php
```

### 7.2 Verify All Features

Run the test page:
```
https://your-app-name.onrender.com/test_gm_stock_return_approval.php
```

### 7.3 Check Mark as Paid Button

1. Login as admin or sales user
2. Go to Sales section
3. Verify "Mark as Paid" button appears for unpaid orders

---

## Step 8: Update Your Old Service (Optional)

If you want to keep the old Render service as backup:
1. Go to old service settings
2. Suspend it (to avoid charges)
3. Keep it for rollback if needed

Or delete it completely:
1. Go to service settings
2. Scroll to bottom
3. Click "Delete Web Service"

---

## Troubleshooting

### Build Fails
**Error:** "No such file or directory"
- **Fix:** Check that `config/aiven-ca.pem` exists in your repository
- Verify file is committed: `git ls-files config/aiven-ca.pem`

### Database Connection Fails
**Error:** "Connection refused" or "Access denied"
- **Fix:** Verify environment variables are correct
- Check Aiven firewall allows Render's IP addresses
- In Aiven console, go to "Allowed IP Addresses" and add `0.0.0.0/0` (or Render's IPs)

### SSL Certificate Error
**Error:** "SSL connection error"
- **Fix:** Verify `DB_SSL_CA` path is `/opt/render/project/src/config/aiven-ca.pem`
- Check that `aiven-ca.pem` file exists in your repo

### Page Shows Old Code
**Error:** Changes don't appear
- **Fix:** This was the original problem - new deployment should fix it
- Clear browser cache: Ctrl+Shift+R
- Check page source to verify new code is there

### Environment Variables Not Working
**Error:** "Undefined constant DB_HOST"
- **Fix:** Make sure all environment variables are set in Render dashboard
- Restart the service after adding variables

---

## Render Configuration Files

Your repository already has the necessary files:

### `render.yaml` (Optional)
If you want to use Infrastructure as Code, you can use this file. Check if it exists in your repo.

### `.gitignore`
Make sure sensitive files are ignored:
```
.env
config/database.php (if it contains credentials)
```

---

## Comparison: Old vs New Deployment

| Aspect | Old Deployment | New Deployment |
|--------|---------------|----------------|
| Code Updates | Not deploying | Will deploy automatically |
| Build Cache | Corrupted | Fresh and clean |
| Environment | Possibly misconfigured | Freshly configured |
| Database | Same (keep it) | Same (keep it) |
| URL | old-name.onrender.com | new-name.onrender.com |

---

## After Successful Deployment

### ✅ What Should Work Now

1. **Mark as Paid Button** - Visible in sales section
2. **GM Stock Return Approval** - Approve/Reject buttons visible
3. **All Code Fixes** - Deployed and functional
4. **Automatic Deployments** - Future git pushes will deploy automatically

### 🔄 Automatic Deployments

From now on:
1. Make code changes locally
2. Commit and push to GitHub
3. Render automatically detects and deploys
4. Changes appear on live site within 2-5 minutes

### 📊 Monitor Deployments

In Render dashboard:
1. Go to your web service
2. Click **"Events"** tab
3. See all deployments and their status
4. Click on any deployment to see logs

---

## Cost Considerations

### Free Tier
- **Pros:** No cost
- **Cons:** 
  - Spins down after 15 minutes of inactivity
  - First request after spin-down takes 30-60 seconds
  - 750 hours/month limit

### Starter Tier ($7/month)
- **Pros:**
  - Always on (no spin-down)
  - Faster performance
  - Better for production
- **Cons:** Monthly cost

**Recommendation:** Start with Free tier for testing, upgrade to Starter for production.

---

## Alternative: Fix Existing Deployment

If you don't want to create a new service, try these steps on your existing one:

### Option A: Clear Everything and Redeploy
1. Go to service settings
2. Click **"Manual Deploy"** → **"Clear build cache & deploy"**
3. Wait for deployment to complete

### Option B: Trigger Fresh Build
1. Make a small change in your code (add a comment)
2. Commit and push to GitHub
3. Watch Render logs to see if it deploys

### Option C: Recreate from Same Repo
1. Delete the existing service
2. Create new service from same repository
3. Use same environment variables

---

## Quick Start Checklist

- [ ] Code pushed to GitHub
- [ ] Render account ready
- [ ] Aiven database credentials available
- [ ] Create new web service on Render
- [ ] Connect GitHub repository
- [ ] Configure environment variables
- [ ] Deploy and wait for "Live" status
- [ ] Visit app URL and test login
- [ ] Run system_status_check.php
- [ ] Run setup scripts if needed
- [ ] Verify all features work
- [ ] Update DNS if using custom domain

---

## Support

If you encounter issues:

1. **Check Render Logs:**
   - Dashboard → Your Service → Logs
   - Look for error messages

2. **Check Database Connection:**
   - Visit: `https://your-app-name.onrender.com/diagnostic.php`

3. **Run System Status:**
   - Visit: `https://your-app-name.onrender.com/system_status_check.php`

4. **Render Support:**
   - Dashboard → Help → Contact Support
   - They respond within 24 hours

---

## Summary

**The Problem:** Your current Render deployment is broken and not deploying code changes.

**The Solution:** Create a fresh deployment on Render with the same code and database.

**The Result:** All your code fixes will be deployed and functional, including:
- Mark as Paid button in sales
- GM approval for stock returns
- All other fixes

**Time Required:** 15-30 minutes

**Downtime:** None if you keep old service running until new one is ready

---

**Last Updated:** May 19, 2026
