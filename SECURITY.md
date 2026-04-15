# Security Setup Guide

## Environment Variables

Your Firebase credentials are now stored securely in the `.env` file.

### Setup Instructions

1. **For Development:**
   - Copy `.env.example` to `.env`
   - Add your actual Firebase credentials to `.env`
   - `.env` is in `.gitignore` and will NOT be committed to version control

2. **For Production/Deployment:**
   - Set environment variables on your hosting platform instead of using `.env`
   - Most hosting platforms provide a dashboard to set environment variables
   - Examples: Heroku Config Vars, Vercel Environment Variables, etc.

### Important Security Notes

✅ **DO:**
- Keep `.env` file out of version control (it's in `.gitignore`)
- Use environment variables for all sensitive data
- Rotate API keys periodically
- Enable Firebase Security Rules to restrict database access

❌ **DON'T:**
- Commit `.env` to Git
- Share `.env` file via email or chat
- Hardcode credentials in PHP files
- Use the same keys in development and production

### Firebase Security Rules

Set up proper Firebase Realtime Database rules to restrict access:

```json
{
  "rules": {
    ".read": "auth != null",
    ".write": "auth != null"
  }
}
```

### Restricting API Keys

In Firebase Console:
1. Go to Settings → Project Settings
2. Click on the API Keys
3. Restrict each key to specific APIs and HTTP referrers
4. Use different keys for web, mobile, and server applications
