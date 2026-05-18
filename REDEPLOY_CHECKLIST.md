# Render Redeployment Checklist

Use this checklist to track your progress when redeploying to a new Render account.

---

## 📋 Pre-Deployment

- [ ] **Code is up to date**
  - [ ] All changes committed
  - [ ] Pushed to GitHub main branch
  - [ ] Verified on GitHub website

- [ ] **Database credentials ready**
  - [ ] Aiven host address
  - [ ] Port number
  - [ ] Database name
  - [ ] Username
  - [ ] Password
  - [ ] SSL certificate file exists in repo

- [ ] **Render account ready**
  - [ ] Logged into Render.com
  - [ ] GitHub account connected
  - [ ] Can see your repository

---

## 🚀 Deployment Steps

- [ ] **Create Web Service**
  - [ ] Clicked "New +" → "Web Service"
  - [ ] Selected correct GitHub repository
  - [ ] Chose correct branch (main)
  - [ ] Set service name
  - [ ] Selected region
  - [ ] Chose instance type (Free or Starter)

- [ ] **Configure Environment Variables**
  - [ ] Added DB_HOST
  - [ ] Added DB_PORT
  - [ ] Added DB_NAME
  - [ ] Added DB_USER
  - [ ] Added DB_PASSWORD
  - [ ] Added DB_SSL_CA (path: /opt/render/project/src/config/aiven-ca.pem)
  - [ ] Added APP_NAME
  - [ ] Added BASE_URL (https://your-service-name.onrender.com)

- [ ] **Deploy**
  - [ ] Clicked "Create Web Service"
  - [ ] Watched deployment logs
  - [ ] Waited for "Live" status
  - [ ] No errors in logs

---

## ✅ Post-Deployment Verification

- [ ] **Basic Access**
  - [ ] App loads at new URL
  - [ ] Login page appears
  - [ ] Can login successfully
  - [ ] Dashboard loads

- [ ] **Run Diagnostics**
  - [ ] Visited `/system_status_check.php`
  - [ ] All checks show green/success
  - [ ] No critical errors

- [ ] **Test Mark as Paid Feature**
  - [ ] Logged in as admin or sales user
  - [ ] Went to Sales section
  - [ ] "Mark as Paid" button visible for unpaid orders
  - [ ] Button works correctly

- [ ] **Test Stock Return Approval**
  - [ ] Logged in as GM user
  - [ ] Went to Approvals section
  - [ ] Can see stock return approval requests
  - [ ] Approve/Reject buttons visible
  - [ ] Can successfully approve/reject

---

## 🔧 Setup Scripts (If Needed)

- [ ] **Sales Order Approvals**
  - [ ] Ran `/fix_sales_order_approval_chain.php`
  - [ ] Verified approval chain created

- [ ] **Stock Return Approvals**
  - [ ] Ran `/fix_stock_return_gm_approval.php`
  - [ ] Verified approval chain created
  - [ ] Ran `/create_missing_stock_return_approvals.php` (if needed)
  - [ ] Verified approval requests created

- [ ] **Test Stock Return Approval**
  - [ ] Ran `/test_gm_stock_return_approval.php`
  - [ ] All steps show green/success

---

## 🌐 DNS Update (If Using Custom Domain)

- [ ] **In Render Dashboard**
  - [ ] Added custom domain
  - [ ] Noted CNAME target

- [ ] **In DNS Provider**
  - [ ] Added CNAME record
  - [ ] Pointed to Render service
  - [ ] Waited for propagation (5-60 minutes)
  - [ ] Verified custom domain works

---

## 🧹 Cleanup

- [ ] **Old Render Service**
  - [ ] Verified new service works completely
  - [ ] Suspended or deleted old service
  - [ ] Updated any bookmarks/links

- [ ] **Documentation**
  - [ ] Updated team with new URL
  - [ ] Updated any API integrations
  - [ ] Updated monitoring tools

---

## 📊 Success Criteria

All of these should be TRUE:

- [ ] ✅ New URL loads correctly
- [ ] ✅ All users can login
- [ ] ✅ Mark as Paid button appears in Sales
- [ ] ✅ GM can approve stock returns
- [ ] ✅ All approval chains configured
- [ ] ✅ No errors in Render logs
- [ ] ✅ system_status_check.php shows all green
- [ ] ✅ Database connection working
- [ ] ✅ All features functional

---

## 🚨 Rollback Plan (If Needed)

If new deployment has issues:

- [ ] **Keep old service running**
- [ ] **Investigate issues in new service**
- [ ] **Check Render logs for errors**
- [ ] **Verify environment variables**
- [ ] **Test database connection**
- [ ] **Contact Render support if needed**

---

## 📝 Notes

Use this space to track any issues or special configurations:

```
Date deployed: _______________
New URL: _______________
Old URL: _______________
Issues encountered: _______________
_______________
_______________
Resolution: _______________
_______________
_______________
```

---

## 🎉 Deployment Complete!

When all checkboxes are checked, your redeployment is complete and successful!

**Next Steps:**
1. Monitor the new deployment for 24 hours
2. Delete or suspend old service
3. Update documentation with new URL
4. Celebrate! 🎊

---

**Estimated Time:** 15-30 minutes

**Difficulty:** Easy

**Risk Level:** Low (can keep old service as backup)
