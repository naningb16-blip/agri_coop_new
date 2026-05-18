# GitHub Account Recovery Guide

Quick guide to recover access to your GitHub account.

---

## 🔍 Find Your GitHub Email

Your git config has your GitHub email:

```bash
git config user.email
```

This shows the email you used for GitHub.

---

## 🔑 Password Reset

### If you know your email:

1. Go to: https://github.com/password_reset
2. Enter your email address
3. Click "Send password reset email"
4. Check your email inbox
5. Click the reset link
6. Create new password

### If you don't receive the email:

1. Check spam/junk folder
2. Wait 5-10 minutes
3. Try again with different email addresses
4. Check all your email accounts

---

## 📧 Find Your GitHub Username

Check your git remote URL:

```bash
git remote -v
```

Output will show:
```
origin  https://github.com/YOUR-USERNAME/repo-name.git
```

Your username is in the URL.

---

## 🆘 Account Locked or Suspended

### If your account is locked:

1. Go to: https://github.com/contact
2. Select "Account recovery"
3. Fill out the form:
   - Your email
   - Username (if you know it)
   - Explain you can't access account
4. GitHub support will respond in 24-48 hours

### Common reasons for locks:

- Too many failed login attempts
- Suspicious activity detected
- Terms of service violation
- Payment issue (if on paid plan)

---

## 🔐 Two-Factor Authentication Issues

### If you have 2FA enabled but lost access:

1. Use recovery codes (if you saved them)
2. Go to: https://github.com/login
3. Enter username and password
4. Click "Use a recovery code"
5. Enter one of your recovery codes

### If you lost recovery codes:

1. Contact GitHub support: https://github.com/contact
2. Select "Account recovery"
3. Explain you lost 2FA access
4. They'll help you recover (may take 1-2 days)

---

## 📱 Check Your Email Accounts

Try these email addresses:

```bash
# Check what email git is using
git config user.email

# Check global git config
git config --global user.email

# Check local repo config
git config --local user.email
```

Common email patterns:
- yourname@gmail.com
- yourname@yahoo.com
- yourname@outlook.com
- work email
- school email

---

## 🆕 Create New GitHub Account (Last Resort)

If you can't recover your account:

### Step 1: Create New Account
1. Go to: https://github.com/signup
2. Use different email
3. Choose username
4. Complete verification

### Step 2: Update Git Remote

```bash
# Remove old remote
git remote remove origin

# Add new remote (replace with your new username and repo)
git remote add origin https://github.com/NEW-USERNAME/NEW-REPO.git

# Push code
git push -u origin main
```

### Step 3: Create New Repository

On GitHub website:
1. Click "+" → "New repository"
2. Name it (e.g., "erp-system")
3. Make it private
4. Don't initialize with README
5. Click "Create repository"

---

## 🔄 Alternative: Deploy Without GitHub

You don't need GitHub to deploy! See:
- `DEPLOY_WITHOUT_GITHUB.md`
- `RAILWAY_DEPLOY_GUIDE.md`

These show how to deploy directly from your local files.

---

## 📞 GitHub Support Contacts

**Email Support:**
- https://support.github.com/contact

**Account Recovery:**
- https://github.com/contact
- Select "Account recovery"

**Response Time:**
- Usually 24-48 hours
- Faster for paid accounts

**What to Include:**
- Your email address
- Username (if you know it)
- Repository names (if you remember)
- When you last accessed account
- Any error messages

---

## ✅ Quick Checklist

Try these in order:

- [ ] Check git config for email: `git config user.email`
- [ ] Try password reset with that email
- [ ] Check spam folder for reset email
- [ ] Try all your email addresses
- [ ] Check git remote for username: `git remote -v`
- [ ] Try logging in with username
- [ ] Check if account is locked
- [ ] Contact GitHub support
- [ ] Consider creating new account
- [ ] Or deploy without GitHub (Railway/Fly.io)

---

## 💡 Prevention for Future

Once you regain access:

1. **Save your credentials securely**
   - Use password manager
   - Save recovery codes

2. **Add backup email**
   - GitHub Settings → Emails
   - Add secondary email

3. **Enable 2FA properly**
   - Save recovery codes
   - Use authenticator app

4. **Document your setup**
   - Write down username
   - Save email used
   - Keep recovery codes safe

---

## 🎯 Recommended Action

**Right Now:**

1. Try password reset: https://github.com/password_reset
2. Use email from: `git config user.email`

**If that fails (15 minutes):**

1. Deploy without GitHub using Railway
2. Follow: `RAILWAY_DEPLOY_GUIDE.md`
3. Your app will be live while you recover GitHub

**Long term:**

1. Keep trying to recover GitHub
2. Contact GitHub support
3. Or create new account

---

## 🚀 Meanwhile, Deploy Your App

Don't let GitHub access block you:

**Option 1: Railway (Recommended)**
```bash
# Install
iwr https://railway.app/install.ps1 | iex

# Login (no GitHub needed)
railway login

# Deploy
cd your-project
railway init
railway up
```

**Option 2: Fly.io**
```bash
# Install
iwr https://fly.io/install.ps1 -useb | iex

# Deploy
fly launch
fly deploy
```

Both work without GitHub!

---

**Bottom Line:** Try password reset first. If it fails, deploy with Railway while you recover your account.
