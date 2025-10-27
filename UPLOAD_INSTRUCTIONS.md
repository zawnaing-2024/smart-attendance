# Smart Attendance System - Upload Instructions

## 🚀 Quick Upload to GitHub

### Method 1: Using the Upload Script (Recommended)

1. **Make the script executable:**
   ```bash
   chmod +x upload-to-github.sh
   ```

2. **Run the upload script:**
   ```bash
   ./upload-to-github.sh
   ```

### Method 2: Manual Git Commands

If you prefer to do it manually:

```bash
# Initialize git repository
git init
git branch -M main

# Add remote repository
git remote add origin https://github.com/zawnaing-2024/smart_attendance.git

# Add all files
git add .

# Commit changes
git commit -m "Initial commit: Smart Attendance System with complete features"

# Push to GitHub
git push -u origin main
```

## 📋 What Will Be Uploaded

The upload script will push all the following components to your GitHub repository:

### **Core Application Files**
- `index.php` - Main dashboard
- `login.php` - Authentication system
- `logout.php` - Logout handler
- `config/database.php` - Database configuration
- `includes/functions.php` - Common functions
- `database/schema.sql` - Database schema

### **Admin Portal**
- `admin/dashboard.php` - Admin dashboard
- `admin/cameras.php` - CCTV management
- `admin/students.php` - Student management
- `admin/grades.php` - Grade management
- `admin/teachers.php` - Teacher management
- `admin/sms.php` - SMS settings
- `admin/includes/header.php` - Admin header
- `admin/includes/footer.php` - Admin footer

### **Teacher Portal**
- `teacher/dashboard.php` - Teacher dashboard
- `teacher/students.php` - Student management
- `teacher/attendance.php` - Attendance tracking
- `teacher/reports.php` - Reports & analytics
- `teacher/includes/header.php` - Teacher header
- `teacher/includes/footer.php` - Teacher footer
- `teacher/ajax/` - AJAX endpoints

### **Live Portal**
- `live/live.php` - Live face detection portal

### **Deployment & Automation**
- `.github/workflows/deploy.yml` - Deployment workflow
- `.github/workflows/migrate.yml` - Database migration
- `.github/workflows/security.yml` - Security scanning
- `.github/scripts/deploy.sh` - Deployment script
- `.github/scripts/health-check.sh` - Health monitoring
- `.github/scripts/auto-update.sh` - Auto-update script
- `.github/scripts/rollback.sh` - Rollback script
- `.github/scripts/setup-server.sh` - Server setup

### **Assets & Configuration**
- `assets/css/style.css` - Custom styling
- `env.example` - Environment variables template
- `README.md` - Complete documentation
- `.gitignore` - Git ignore rules

## 🔧 Post-Upload Setup

After uploading, you'll need to configure GitHub Secrets for automated deployment:

### **1. GitHub Secrets Configuration**

Go to your repository → **Settings** → **Secrets and variables** → **Actions**

Add these secrets:
```
SERVER_SSH_KEY     # Private SSH key for server access
SERVER_USER        # Server username (e.g., ubuntu)
SERVER_HOST        # Server IP address or hostname
DB_PASSWORD        # Database password
```

### **2. SSH Key Setup**

Generate SSH key pair for GitHub Actions:

```bash
# Generate SSH key
ssh-keygen -t rsa -b 4096 -C "github-actions" -f ~/.ssh/github_actions

# Copy public key to server
ssh-copy-id -i ~/.ssh/github_actions.pub user@your-server

# Add private key to GitHub secrets
cat ~/.ssh/github_actions
```

### **3. Server Setup**

On your production server:

```bash
# Download and run setup script
curl -o setup-server.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/setup-server.sh
chmod +x setup-server.sh
sudo ./setup-server.sh
```

### **4. Initial Deployment**

```bash
# Deploy the application
/opt/deployments/deploy.sh production
```

## 🎯 Features After Upload

Once uploaded, your repository will have:

### **GitHub Actions Workflows**
- **Automatic deployment** on push to main branch
- **Security scanning** with vulnerability detection
- **Database migration** capabilities
- **Health monitoring** and alerts

### **Zero-Downtime Deployment**
- **Atomic deployment** with maintenance page
- **Automatic backups** before each update
- **Health verification** after deployment
- **Instant rollback** if issues occur

### **Production-Ready Features**
- **Comprehensive logging** and monitoring
- **Security hardening** and firewall configuration
- **SSL/TLS support** ready
- **Automated updates** and maintenance

## 🔒 Security Considerations

The upload includes:
- **Proper .gitignore** to exclude sensitive files
- **Environment variable templates** for configuration
- **Security scanning workflows** for vulnerability detection
- **Production-ready deployment** scripts

## 📊 Monitoring & Maintenance

After upload, you'll have:
- **Automated health checks** every 5 minutes
- **Daily security scans** at 2 AM
- **Automatic log rotation** and cleanup
- **Comprehensive monitoring** dashboard

## 🚨 Troubleshooting

If upload fails:

1. **Check Git credentials:**
   ```bash
   git config --global user.name "Your Name"
   git config --global user.email "your.email@example.com"
   ```

2. **Verify repository access:**
   ```bash
   git remote -v
   ```

3. **Check for conflicts:**
   ```bash
   git status
   git pull origin main
   ```

4. **Force push if needed:**
   ```bash
   git push -u origin main --force
   ```

## 📝 Next Steps

After successful upload:

1. **Configure GitHub Secrets** for automated deployment
2. **Set up production server** using the setup script
3. **Deploy the application** using deployment scripts
4. **Configure SMS provider** in admin panel
5. **Add CCTV cameras** and test face detection
6. **Create students and teachers** through admin interface
7. **Test the complete system** end-to-end

Your Smart Attendance System will be fully operational with automated deployment, monitoring, and maintenance capabilities!
