# PetCare Project - Local Setup Guide

## System Requirements

- **PHP**: 7.4+ (8.0+ recommended)
- **Web Server**: Apache or Nginx
- **cURL**: PHP cURL extension enabled
- **Internet Connection**: Required for Firebase/Cloudinary APIs
- **Operating System**: Windows, macOS, or Linux

## Prerequisites Setup

### 1. Install PHP (if not already installed)

**Windows:**
- Download PHP from https://www.php.net/downloads
- Extract to `C:\php\` (or your preferred location)
- Add `C:\php` to system PATH

**macOS:**
```bash
brew install php
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install php php-curl php-cli
```

### 2. Install Apache or Use PHP Built-in Server

**Option A: PHP Built-in Server (Easiest for Development)**
```bash
cd c:\Users\Altaf Shaikh\OneDrive\Desktop\Sumati-major-project\PetCare_F
php -S localhost:8000
```

**Option B: Apache (Windows)**
1. Download Apache from https://httpd.apache.org/
2. Configure to point to project directory
3. Ensure mod_rewrite and mod_dir are enabled

## Project Setup

### 1. Clone/Copy Project Files

Project files are already in:
```
c:\Users\Altaf Shaikh\OneDrive\Desktop\Sumati-major-project\PetCare_F
```

### 2. Configure Environment Variables

Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

Edit `.env` with your credentials:
```env
# Firebase Configuration
FIREBASE_API_KEY=AIzaSyB-ntkPWJ2QFTKqhoINppEXMUPn8eSN11g
FIREBASE_AUTH_DOMAIN=pawsomemart1.firebaseapp.com
FIREBASE_PROJECT_ID=pawsomemart1
FIREBASE_STORAGE_BUCKET=pawsomemart1.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=980506265424
FIREBASE_APP_ID=1:980506265424:web:25aa0a5e7ed925510e0e55
FIREBASE_MEASUREMENT_ID=G-D2FE0HYVG0

# Cloudinary Configuration
CLOUDINARY_CLOUD_NAME=dxr1vepkt
CLOUDINARY_UPLOAD_PRESET=pawsomemart_upload
CLOUDINARY_API_KEY=928276744371939

# App Settings
APP_ENV=development
APP_DEBUG=true
```

**⚠️ IMPORTANT:** 
- The `.env` file is ignored by Git (see `.gitignore`)
- Never commit `.env` to version control
- Each developer/environment has their own `.env` file

## Running the Project Locally

### Method 1: Using PHP Built-in Server (Recommended for Development)

```bash
# Navigate to project directory
cd c:\Users\Altaf Shaikh\OneDrive\Desktop\Sumati-major-project\PetCare_F

# Start PHP server
php -S localhost:8000

# Access in browser
# http://localhost:8000
```

The built-in server will automatically serve `index.php` for root requests.

### Method 2: Using Apache

1. Edit Apache `httpd.conf` to add virtual host:
```apache
<VirtualHost *:80>
    ServerName petcare.local
    DocumentRoot "c:/Users/Altaf Shaikh/OneDrive/Desktop/Sumati-major-project/PetCare_F"
    
    <Directory "c:/Users/Altaf Shaikh/OneDrive/Desktop/Sumati-major-project/PetCare_F">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. Add to `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1 petcare.local
```

3. Start Apache and visit `http://petcare.local`

## Testing the Application

### 1. User Registration & Login
- Navigate to http://localhost:8000
- Click "Create an account"
- Register with a valid email and password
- Login with your credentials

### 2. Browse Products
- Go to "Products" page
- View products from Firestore
- Add items to cart
- Proceed to checkout

### 3. Admin Panel
- Visit admin login page (admin dropdown or direct URL)
- Login with admin credentials
- Access product management
- Upload images to Cloudinary
- Manage orders and users

## Firebase Firestore Setup

If you need to set up Firestore from scratch:

1. Go to https://firebase.google.com/
2. Create a new Firebase project
3. Enable Firestore Database
4. Set security rules (update in Firebase Console):
```json
{
  "rules": {
    "users": {
      "{userId}": {
        ".read": "request.auth.uid == userId",
        ".write": "request.auth.uid == userId"
      }
    },
    "products": {
      ".read": true,
      ".write": "request.auth.uid != null"
    },
    "orders": {
      "{orderId}": {
        ".read": "root.child('orders').child(orderId).child('user_id').val() == request.auth.uid",
        ".write": "root.child('orders').child(orderId).child('user_id').val() == request.auth.uid"
      }
    }
  }
}
```

5. Copy your Firebase config to `.env` file

## Cloudinary Setup

If you need to change Cloudinary settings:

1. Go to https://cloudinary.com/
2. Sign in or create account
3. Get your Cloud Name and credentials
4. Create an unsigned upload preset
5. Update `.env` with credentials

## Troubleshooting

### "Cannot enable SESSION cookies" error
- Ensure `session_start()` is called before any output
- Check error logs in `php_errors.log` or `apache2error.log`

### Firebase connection errors
- Verify internet connection
- Check `.env` credentials are correct
- Ensure Firebase project has Firestore enabled
- Check Firebase security rules

### Image upload fails
- Verify Cloudinary credentials
- Check file size (max 2MB)
- Ensure upload preset exists in Cloudinary
- Check file MIME types (JPG, PNG, WEBP)

### PHP cURL extension not loading
```bash
# Windows: Uncomment in php.ini
extension=curl

# Linux: Install cURL extension
sudo apt install php-curl
sudo systemctl restart apache2

# macOS: Already included in PHP
```

### Port 8000 already in use
Use a different port:
```bash
php -S localhost:8080
```

## Project Structure

```
PetCare_F/
├── .env                      # Environment variables (NOT in Git)
├── .env.example             # Configuration template
├── .gitignore               # Git ignore file
├── config.php               # Firebase & Cloudinary configuration
├── index.php                # Main router
├── styles.css               # Global styles
├── 
├── Authentication:
│   ├── login.php           # User login
│   ├── register.php        # User registration
│   ├── admin_login.php     # Admin login
│   └── reset_admin.php     # Admin password reset
│
├── Pages:
│   ├── home.php            # Homepage
│   ├── products.php        # Product listing
│   ├── products_admin.php  # Admin product management
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Checkout
│   ├── profile.php         # User profile
│   ├── user_orders.php     # User orders
│   ├── orders_admin.php    # Admin order management
│   └── users_admin.php     # Admin user management
│
├── Components:
│   ├── header.php          # Page header
│   ├── footer.php          # Page footer
│   ├── navbar_user.php     # User navigation
│   ├── navbar_admin.php    # Admin navigation
│   └── navbar_dynamic.php  # Dynamic navigation
│
└── Uploads:
    ├── images/             # Local images
    └── uploads/products/   # Product uploads (legacy)
```

## Database Structure (Firestore)

### Collections:

**users**
```
{
  email: string,
  role: string (user|admin),
  phone: string,
  address: string,
  created_at: timestamp,
  updated_at: timestamp
}
```

**products**
```
{
  name: string,
  category: string,
  price: number,
  stock: number,
  description: string,
  images: array[url],
  created_at: timestamp,
  updated_at: timestamp
}
```

**orders**
```
{
  user_id: string,
  user_name: string,
  total: number,
  status: string,
  items: array[{id, name, price, qty, image_url}],
  created_at: timestamp,
  updated_at: timestamp
}
```

## Development Tips

1. **Enable debug mode** in `.env`:
   ```
   APP_DEBUG=true
   ```

2. **Check logs** for errors:
   - PHP error logs: `php_errors.log`
   - Firebase logs: Browser console network tab

3. **Use browser DevTools** to inspect API calls

4. **Test Firebase offline** using emulator:
   ```bash
   firebase emulators:start
   ```

5. **Monitor Firestore** via Firebase Console

## Deployment

For production deployment:

1. Set `APP_DEBUG=false` in `.env`
2. Use proper SSL certificates
3. Set strong Firebase security rules
4. Restrict Cloudinary API keys by domain
5. Use environment variables on hosting platform (don't commit `.env`)
6. Configure CORS properly

## Support

For Firebase issues: https://firebase.google.com/docs
For Cloudinary issues: https://cloudinary.com/documentation
For PHP issues: https://www.php.net/docs.php
