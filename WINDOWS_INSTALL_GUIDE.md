# Windows Installation Guide

## How to Install Distributed Chat System on Windows (From Scratch)

---

## Prerequisites to Download

Before starting, download and install these software:

### 1. Download PHP (XAMPP - Easiest)

**Option A: XAMPP (Recommended - Includes PHP + MySQL)**
- Download: https://www.apachefriends.org/download.html
- Choose: XAMPP for Windows (PHP 8.2+)
- Install to: `C:\xampp`

**Option B: Standalone PHP**
- Download: https://windows.php.net/download/
- Choose: VS16 x64 Thread Safe ZIP
- Extract to: `C:\php`

### 2. Download Composer

- Download: https://getcomposer.org/Composer-Setup.exe
- Run the installer
- It will auto-detect PHP from XAMPP

### 3. Download Node.js

- Download: https://nodejs.org/
- Choose: LTS version (recommended)
- Run the installer (include npm)

### 4. Download Git (Optional but Recommended)

- Download: https://git-scm.com/download/win
- Run the installer with default settings

---

## Step-by-Step Installation

### Step 1: Extract the Project

1. Receive the `distributed-chat-system.zip` file
2. Extract it to a location like:
   ```
   C:\Projects\distributed-chat-system
   ```

Your folder structure should look like:
```
C:\Projects\distributed-chat-system\
├── backend\
├── frontend\
├── README.md
├── DOCUMENTATION.md
└── ...
```

---

### Step 2: Verify Installations

Open **Command Prompt** (cmd) or **PowerShell** and run:

```cmd
php -v
```
Expected output: `PHP 8.x.x`

```cmd
composer -V
```
Expected output: `Composer version 2.x.x`

```cmd
node -v
```
Expected output: `v20.x.x` or similar

```cmd
npm -v
```
Expected output: `10.x.x` or similar

If any command fails, the software isn't installed correctly.

---

### Step 3: Set Up Backend (Laravel)

Open **Command Prompt** and navigate to the backend folder:

```cmd
cd C:\Projects\distributed-chat-system\backend
```

#### 3.1 Install PHP Dependencies

```cmd
composer install
```

Wait for it to complete (may take 2-5 minutes).

#### 3.2 Create Environment File

```cmd
copy .env.example .env
```

Or if `.env` already exists, skip this step.

#### 3.3 Generate Application Key

```cmd
php artisan key:generate
```

#### 3.4 Run Database Migrations

```cmd
php artisan migrate
```

When prompted, type `yes` to create the SQLite database.

#### 3.5 Create Test Users

```cmd
php artisan tinker
```

Then paste this code:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create(['name' => 'David Mensah', 'email' => 'david@gctu.edu.gh', 'password' => Hash::make('password123')]);
User::create(['name' => 'Kofi Asante', 'email' => 'kofi@gctu.edu.gh', 'password' => Hash::make('password123')]);

exit
```

---

### Step 4: Set Up Frontend (React)

Open a **new Command Prompt window** and navigate to frontend:

```cmd
cd C:\Projects\distributed-chat-system\frontend
```

#### 4.1 Install Node Dependencies

```cmd
npm install
```

Wait for it to complete (may take 2-5 minutes).

---

### Step 5: Start the Application

You need **3 separate Command Prompt windows** running:

#### Window 1: Backend API Server

```cmd
cd C:\Projects\distributed-chat-system\backend
php artisan serve --port=8000
```

Keep this running. You should see:
```
INFO  Server running on [http://127.0.0.1:8000].
```

#### Window 2: WebSocket Server (Reverb)

```cmd
cd C:\Projects\distributed-chat-system\backend
php artisan reverb:start
```

Keep this running. You should see:
```
INFO  Starting server on 0.0.0.0:8080
```

#### Window 3: Frontend Dev Server

```cmd
cd C:\Projects\distributed-chat-system\frontend
npm run dev
```

Keep this running. You should see:
```
VITE v7.x.x  ready

➜  Local:   http://localhost:1500/
```

---

### Step 6: Access the Application

Open your web browser and go to:

**http://localhost:1500**

---

### Step 7: Test Login

Use these credentials:

| User | Email | Password |
|------|-------|----------|
| User 1 | `david@gctu.edu.gh` | `password123` |
| User 2 | `kofi@gctu.edu.gh` | `password123` |

To test real-time messaging:
1. Open http://localhost:1500 in Chrome
2. Open http://localhost:1500 in Firefox (or Incognito)
3. Login with different users in each browser
4. Send messages - they appear instantly in both!

---

## Quick Start Script (Create a Batch File)

Create a file called `start-chat.bat` in the project root:

```batch
@echo off
echo Starting Distributed Chat System...
echo.

:: Start Backend API
start "Backend API" cmd /k "cd /d C:\Projects\distributed-chat-system\backend && php artisan serve --port=8000"

:: Wait 2 seconds
timeout /t 2 /nobreak >nul

:: Start WebSocket Server
start "WebSocket Server" cmd /k "cd /d C:\Projects\distributed-chat-system\backend && php artisan reverb:start"

:: Wait 2 seconds
timeout /t 2 /nobreak >nul

:: Start Frontend
start "Frontend" cmd /k "cd /d C:\Projects\distributed-chat-system\frontend && npm run dev"

:: Wait for servers to start
timeout /t 5 /nobreak >nul

:: Open browser
start http://localhost:1500

echo.
echo All servers started!
echo Frontend: http://localhost:1500
echo Backend API: http://localhost:8000
echo WebSocket: ws://localhost:8080
echo.
echo Close all command windows to stop the servers.
pause
```

**To use:** Double-click `start-chat.bat` and everything starts automatically!

---

## Troubleshooting

### Error: 'php' is not recognized

**Solution:** Add PHP to Windows PATH

1. Search "Environment Variables" in Windows
2. Click "Environment Variables"
3. Under "System Variables", find "Path"
4. Click "Edit" → "New"
5. Add: `C:\xampp\php` (or wherever PHP is installed)
6. Click OK and restart Command Prompt

### Error: 'composer' is not recognized

**Solution:** Reinstall Composer
- Download: https://getcomposer.org/Composer-Setup.exe
- Run installer - it will find PHP automatically

### Error: 'npm' is not recognized

**Solution:** Reinstall Node.js
- Download: https://nodejs.org/
- Run installer with default options
- Restart Command Prompt

### Error: SQLSTATE - database not found

**Solution:** Create the database file

```cmd
cd C:\Projects\distributed-chat-system\backend
type nul > database\database.sqlite
php artisan migrate
```

### Error: Port already in use

**Solution:** Use different ports

```cmd
:: Backend on port 8001
php artisan serve --port=8001

:: Update frontend .env
VITE_API_URL=http://localhost:8001
```

### Error: WebSocket connection failed

**Solution:** Make sure Reverb is running

```cmd
cd C:\Projects\distributed-chat-system\backend
php artisan reverb:start
```

Check that port 8080 is not blocked by firewall.

### Error: CORS / Cross-Origin errors

**Solution:** Check backend CORS config

Open `backend\config\cors.php` and ensure:
```php
'allowed_origins' => ['http://localhost:1500', 'http://127.0.0.1:1500'],
'supports_credentials' => true,
```

Then clear cache:
```cmd
php artisan config:clear
```

---

## Stopping the Application

To stop all servers:
1. Go to each Command Prompt window
2. Press `Ctrl + C`
3. Close the window

---

## Summary of Commands

```cmd
:: ONE-TIME SETUP (only do once)
cd C:\Projects\distributed-chat-system\backend
composer install
php artisan key:generate
php artisan migrate

cd C:\Projects\distributed-chat-system\frontend
npm install

:: START SERVERS (every time you want to run)
:: Terminal 1
cd C:\Projects\distributed-chat-system\backend
php artisan serve --port=8000

:: Terminal 2
cd C:\Projects\distributed-chat-system\backend
php artisan reverb:start

:: Terminal 3
cd C:\Projects\distributed-chat-system\frontend
npm run dev

:: Then open: http://localhost:1500
```

---

## Test Credentials

| Email | Password |
|-------|----------|
| david@gctu.edu.gh | password123 |
| kofi@gctu.edu.gh | password123 |

---

## System Requirements

| Requirement | Minimum |
|-------------|---------|
| Windows | Windows 10/11 |
| RAM | 4 GB |
| Disk Space | 500 MB |
| PHP | 8.1+ |
| Node.js | 18+ |

---

## Support

If you encounter issues:
1. Check the troubleshooting section above
2. Make sure all 3 servers are running
3. Check Command Prompt windows for error messages
4. Try restarting all servers

---

*This guide is for the CSBC 311 Distributed Systems Project - GCTU*
