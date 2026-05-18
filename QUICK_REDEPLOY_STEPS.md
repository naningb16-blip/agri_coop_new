# Quick Redeploy Steps - Essential Guide

## 🚀 5-Minute Setup

### Step 1: Push Your Code (30 seconds)
```bash
git add .
git commit -m "Ready for redeployment"
git push origin main
```

### Step 2: Create Render Service (2 minutes)
1. Go to https://render.com
2. Click **"New +"** → **"Web Service"**
3. Connect your GitHub repository
4. Configure:
   - **Name:** your-app-name
   - **Runtime:** PHP
   - **Branch:** main

### Step 3: Add Environment Variables (2 minutes)
Click **"Advanced"** and add:

```
DB_HOST=your-aiven-host.aivencloud.com
DB_PORT=your-port
DB_NAME=your-database
DB_USER=your-user
DB_PASSWORD=your-password
DB_SSL_CA=/opt/render/project/src/config/aiven-ca.pem
APP_NAME=Your App Name
BASE_URL=https://your-app-name.onrender.com
```

### Step 4: Deploy (30 seconds)
1. Click **"Create Web Service"**
2. Wait for "Live" status (2-5 minutes)

### Step 5: Verify (1 minute)
Visit: `https://your-app-name.onrender.com/system_status_check.php`

---

## ✅ What You Need

### From Aiven Console
- Host (e.g., mysql-xxx.aivencloud.com)
- Port (usually 3306)
- Database name
- Username (usually avnadmin)
- Password

### From GitHub
- Repository URL
- Make sure latest code is pushed

---

## 🎯 After Deployment

### Run These Scripts (Optional)
Only if you have pending approvals:

1. `https://your-app-name.onrender.com/fix_sales_order_approval_chain.php`
2. `https://your-app-name.onrender.com/fix_stock_return_gm_approval.php`
3. `https://your-app-name.onrender.com/create_missing_stock_return_approvals.php`

### Test Everything
1. Login to your app
2. Check Sales → Mark as Paid button appears
3. Check Approvals → GM can approve stock returns
4. Run `test_gm_stock_return_approval.php`

---

## 🔧 Common Issues

### "Database connection failed"
- Check environment variables are correct
- Verify Aiven allows connections from anywhere (0.0.0.0/0)

### "SSL certificate error"
- Verify `aiven-ca.pem` file exists in `config/` folder
- Check `DB_SSL_CA` path is correct

### "Page not found"
- Wait 2-5 minutes for deployment to complete
- Check Render logs for errors

---

## 💡 Pro Tips

1. **Keep old service running** until new one is verified
2. **Use same database** - don't create a new one
3. **Update BASE_URL** to match your new Render URL
4. **Test thoroughly** before switching DNS

---

## 📞 Need Help?

1. Check Render logs: Dashboard → Your Service → Logs
2. Run diagnostic: `https://your-app-name.onrender.com/diagnostic.php`
3. Check system status: `https://your-app-name.onrender.com/system_status_check.php`

---

## 🎉 Success Indicators

- ✅ App loads at new URL
- ✅ Can login successfully
- ✅ Mark as Paid button visible in Sales
- ✅ GM can approve stock returns
- ✅ system_status_check.php shows all green

---

**Total Time:** 15-30 minutes including deployment wait time

**Downtime:** Zero (keep old service until new one works)

**Cost:** Free tier available, $7/month for always-on
