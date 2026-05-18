# Complete Render Deployment Guide

## Prerequisites
- GitHub account with your code pushed to: `https://github.com/naningb16-blip/agri_coop_new.git`
- Render account (free tier works)
- Aiven MySQL database credentials

## Step 1: Delete/Suspend Old Services

**IMPORTANT:** If you have old Render services running, stop them first to avoid conflicts.

1. Go to https://dashboard.render.com
2. Find any old services (like `agri-coop` at `https://agri-coop.onrender.com`)
3. Click on the service
4. Go to "Settings" tab (bottom left)
5. Scroll down and click **"Suspend Service"** or **"Delete Service"**

## Step 2: Create New Web Service

1. Go to https://dashboard.render.com
2. Click **"New +"** button (top right)
3. Select **"Web Service"**

### Connect Repository
4. Click **"Connect account"** if GitHub isn't connected
5. Find and select: `naningb16-blip/agri_coop_new`
6. Click **"Connect"**

### Configure Service
7. Fill in these settings:

**Basic Settings:**
- **Name:** `agri-coop-production` (or any name you want)
- **Region:** Singapore (closest to Philippines)
- **Branch:** `main`
- **Root Directory:** (leave blank)
- **Runtime:** `Docker`
- **Instance Type:** `Free`

### Environment Variables
8. Scroll down to **"Environment Variables"** section
9. Click **"Add Environment Variable"** and add these **ONE BY ONE**:

```
Key: BASE_URL
Value: https://agri-coop-production.onrender.com
```
*(Replace `agri-coop-production` with whatever name you chose in step 7)*

```
Key: DB_HOST
Value: mysql-1e56207f-dsuanzon2004-8667.e.aivencloud.com
```

```
Key: DB_PORT
Value: 15304
```

```
Key: DB_USER
Value: avnadmin
```

```
Key: DB_PASS
Value: AVNS_vjJM9OSnzunmQELaqjg
```

```
Key: DB_NAME
Value: defaultdb
```

```
Key: DB_SSL_CA
Value: /var/www/html/config/aiven-ca.pem
```

### Deploy
10. Click **"Create Web Service"** button at the bottom
11. Wait for deployment (5-10 minutes for first deploy)
12. Watch the logs - you should see Docker building and starting

## Step 3: Verify Deployment

Once deployment shows "Live" (green):

### Test 1: Check Configuration
Visit: `https://your-service-name.onrender.com/check_base_url.php`

You should see JSON output like:
```json
{
    "success": true,
    "message": "BASE_URL Configuration Check",
    "base_url_constant": "https://your-service-name.onrender.com",
    "base_url_env": "https://your-service-name.onrender.com",
    "db_host_constant": "mysql-1e56207f-dsuanzon2004-8667.e.aivencloud.com",
    ...
}
```

✅ If you see this, configuration is correct!
❌ If you see redirect or error, check environment variables

### Test 2: Login
Visit: `https://your-service-name.onrender.com`

- Should show login page
- Try logging in with your admin account
- Should redirect to dashboard

## Step 4: Run Setup Scripts

After successful login, run these scripts to fix approval workflows:

### Fix Stock Return Approval
Visit: `https://your-service-name.onrender.com/fix_stock_return_gm_approval.php`

Expected output:
```
✅ Approval chain configured for stock_return module
✅ GM-only approval chain is active
```

### Create Missing Approval Requests
Visit: `https://your-service-name.onrender.com/create_missing_stock_return_approvals.php`

Expected output:
```
✅ Created approval requests for X pending stock returns
```

### Fix Sales Order Approval
Visit: `https://your-service-name.onrender.com/fix_sales_order_approval_chain.php`

Expected output:
```
✅ Sales order approval chain configured
✅ GM-only approval is active
```

## Step 5: Test Features

### Test Sales "Mark as Paid" Button
1. Login as admin or sales user
2. Go to Sales module
3. Find an approved order that's unpaid
4. You should see green **"Paid"** button
5. Click it to mark as paid
6. Verify payment status updates

### Test Stock Return GM Approval
1. Login as GM
2. Go to Approvals section
3. Find a pending stock return approval
4. Click to view details
5. You should see **Approve** and **Reject** buttons
6. Test approving/rejecting

## Troubleshooting

### Issue: Site redirects to old URL
**Solution:** 
- Make sure old services are suspended/deleted
- Check BASE_URL environment variable is set correctly
- Clear browser cache

### Issue: Database connection error
**Solution:**
- Verify all DB_* environment variables are set
- Check Aiven database is running
- Verify SSL certificate path: `/var/www/html/config/aiven-ca.pem`

### Issue: "Missing Backfil URL" error
**Solution:**
- This is from old service - suspend/delete it
- Clear browser cache
- Make sure you're visiting the NEW service URL

### Issue: Changes not appearing
**Solution:**
- Check deployment logs in Render dashboard
- Make sure latest code is pushed to GitHub
- Trigger manual deploy: Settings → Manual Deploy → "Deploy latest commit"

### Issue: Buttons still not showing
**Solution:**
- Run the setup scripts (Step 4)
- Clear browser cache (Ctrl+Shift+Delete)
- Check user role (must be admin/sales for mark paid, GM for approvals)

## Updating Your Application

When you make code changes:

1. Commit and push to GitHub:
```bash
git add -A
git commit -m "Your change description"
git push origin main
```

2. Render will automatically detect and deploy (takes 5-10 minutes)

3. Or manually deploy:
   - Go to Render dashboard
   - Click your service
   - Click "Manual Deploy" → "Deploy latest commit"

## Important Notes

- **Free tier sleeps after 15 minutes of inactivity** - first request after sleep takes 30-60 seconds
- **Upgrade to paid tier ($7/month)** to keep service always running
- **Database is on Aiven** - both old and new services use same database
- **Environment variables are secure** - not visible in logs or code
- **SSL is automatic** - Render provides free HTTPS

## Your Service URLs

After deployment, save these URLs:

- **Main Site:** `https://your-service-name.onrender.com`
- **Login:** `https://your-service-name.onrender.com/login`
- **Dashboard:** `https://your-service-name.onrender.com/dashboard`
- **Diagnostic:** `https://your-service-name.onrender.com/check_base_url.php`

## Support

If you encounter issues:
1. Check Render logs: Dashboard → Your Service → Logs tab
2. Check browser console: F12 → Console tab
3. Run diagnostic script: `/check_base_url.php`
4. Verify environment variables: Dashboard → Environment tab

---

## Quick Checklist

- [ ] Old services suspended/deleted
- [ ] New service created with correct name
- [ ] All 7 environment variables added
- [ ] Deployment completed successfully (green "Live" status)
- [ ] Diagnostic script shows correct configuration
- [ ] Login works
- [ ] Setup scripts run successfully
- [ ] Mark as Paid button appears for sales
- [ ] GM can approve stock returns
- [ ] All features working

**Deployment Date:** _________________
**Service Name:** _________________
**Service URL:** _________________
