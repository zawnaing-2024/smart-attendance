# Smart Attendance System - GitHub Actions & Scripts

This directory contains GitHub Actions workflows and deployment scripts for automated deployment and management of the Smart Attendance System.

## 📁 Directory Structure

```
.github/
├── workflows/
│   ├── deploy.yml          # Main deployment workflow
│   ├── migrate.yml         # Database migration workflow
│   └── security.yml       # Security scanning workflow
└── scripts/
    ├── deploy.sh           # Zero-downtime deployment script
    ├── health-check.sh     # System health monitoring
    ├── auto-update.sh      # Automated update checking
    ├── rollback.sh         # Rollback to previous version
    └── setup-server.sh    # Initial server setup
```

## 🚀 GitHub Actions Workflows

### 1. Deploy Workflow (`deploy.yml`)

**Triggers:**
- Push to `main` branch
- Manual workflow dispatch

**Features:**
- Zero-downtime deployment
- Automatic health checks
- Environment selection (production/staging)
- Success/failure notifications

**Required Secrets:**
- `SERVER_SSH_KEY`: Private SSH key for server access
- `SERVER_USER`: Server username
- `SERVER_HOST`: Server hostname/IP
- `DB_PASSWORD`: Database password

### 2. Database Migration (`migrate.yml`)

**Triggers:**
- Manual workflow dispatch

**Features:**
- Database backup before migration
- Safe migration execution
- Rollback capability

### 3. Security Scan (`security.yml`)

**Triggers:**
- Daily at 2 AM
- Push to `main` branch
- Pull requests

**Features:**
- PHP security vulnerability scanning
- CodeQL analysis
- SQL injection detection
- XSS vulnerability checks

## 🛠️ Deployment Scripts

### 1. Deploy Script (`deploy.sh`)

**Usage:**
```bash
./deploy.sh [environment]
```

**Features:**
- Zero-downtime deployment
- Automatic backups
- Configuration preservation
- Database migration support
- Atomic file replacement
- Health verification
- Automatic rollback on failure

### 2. Health Check Script (`health-check.sh`)

**Usage:**
```bash
./health-check.sh
```

**Checks:**
- Application responsiveness
- Database connectivity
- Disk space usage
- Memory usage
- Apache service status
- MySQL service status

### 3. Auto Update Script (`auto-update.sh`)

**Usage:**
```bash
./auto-update.sh
```

**Features:**
- Automatic update detection
- Safe deployment execution
- Notification system
- Logging and monitoring

### 4. Rollback Script (`rollback.sh`)

**Usage:**
```bash
./rollback.sh [backup_timestamp]
```

**Features:**
- List available backups
- Rollback to specific backup
- Database restoration
- Configuration restoration
- Health verification

### 5. Server Setup Script (`setup-server.sh`)

**Usage:**
```bash
sudo ./setup-server.sh
```

**Features:**
- Complete server environment setup
- Package installation
- Service configuration
- Security hardening
- Cron job setup
- Firewall configuration

## 🔧 Setup Instructions

### 1. Server Preparation

Run the server setup script on a fresh Ubuntu server:

```bash
# Download and run setup script
curl -o setup-server.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/setup-server.sh
chmod +x setup-server.sh
sudo ./setup-server.sh
```

### 2. GitHub Secrets Configuration

Configure the following secrets in your GitHub repository:

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Add the following repository secrets:

```
SERVER_SSH_KEY     # Private SSH key for server access
SERVER_USER        # Server username (e.g., ubuntu)
SERVER_HOST        # Server IP address or hostname
DB_PASSWORD        # Database password
```

### 3. SSH Key Setup

Generate SSH key pair for GitHub Actions:

```bash
# On your local machine
ssh-keygen -t rsa -b 4096 -C "github-actions" -f ~/.ssh/github_actions

# Copy public key to server
ssh-copy-id -i ~/.ssh/github_actions.pub user@your-server

# Add private key to GitHub secrets
cat ~/.ssh/github_actions
```

### 4. Initial Deployment

Deploy the application for the first time:

```bash
# On the server
/opt/deployments/deploy.sh production
```

## 🔄 Deployment Process

### Automatic Deployment

1. **Push to main branch** → Triggers automatic deployment
2. **Manual deployment** → Use GitHub Actions "Run workflow" button
3. **Scheduled updates** → Daily check at 2 AM (configurable)

### Manual Deployment

```bash
# On the server
/opt/deployments/deploy.sh production
```

### Rollback Process

```bash
# List available backups
/opt/deployments/rollback.sh

# Rollback to specific backup
/opt/deployments/rollback.sh 20241201_143022

# Rollback to latest backup
/opt/deployments/rollback.sh
```

## 📊 Monitoring & Logs

### Health Monitoring

- **Automatic health checks** every 5 minutes
- **Logs location**: `/var/log/smart_attendance_health.log`
- **Manual health check**: `/opt/deployments/health-check.sh`

### Deployment Logs

- **Deployment logs**: `/var/log/smart_attendance_updates.log`
- **Apache logs**: `/var/log/apache2/smart_attendance_*.log`
- **MySQL logs**: `/var/log/mysql/error.log`

### Log Rotation

- **Automatic cleanup** of logs older than 7 days
- **Weekly rotation** via cron job

## 🔒 Security Features

### GitHub Actions Security

- **CodeQL analysis** for vulnerability detection
- **Dependency scanning** for security issues
- **Secret scanning** for exposed credentials
- **Branch protection** rules

### Server Security

- **Firewall configuration** (UFW)
- **SSL/TLS support** (Let's Encrypt)
- **Database security** (local binding)
- **File permissions** (proper ownership)

## 🚨 Troubleshooting

### Common Issues

1. **Deployment fails**
   ```bash
   # Check logs
   tail -f /var/log/smart_attendance_updates.log
   
   # Manual rollback
   /opt/deployments/rollback.sh
   ```

2. **Health check fails**
   ```bash
   # Run manual health check
   /opt/deployments/health-check.sh
   
   # Check service status
   systemctl status apache2 mysql
   ```

3. **GitHub Actions fails**
   - Check SSH key configuration
   - Verify server connectivity
   - Review workflow logs in GitHub

### Support Commands

```bash
# Check deployment status
systemctl status apache2 mysql

# View recent logs
tail -f /var/log/smart_attendance_*.log

# Test database connection
mysql -u attendance_user -p smart_attendance

# Test application
curl -I http://localhost/smart_attendance
```

## 📝 Customization

### Environment Variables

Edit `/opt/deployments/.env` to customize:

```bash
# Database Configuration
DB_HOST=localhost
DB_USER=attendance_user
DB_PASS=your_secure_password
DB_NAME=smart_attendance

# Application Configuration
APP_NAME=smart_attendance
PRODUCTION_DIR=/var/www/html/smart_attendance
BACKUP_DIR=/opt/backups/smart_attendance

# Git Configuration
GIT_REPO=https://github.com/zawnaing-2024/smart_attendance.git
```

### Cron Jobs

Modify `/etc/cron.d/smart-attendance` for custom scheduling:

```cron
# Health check every 5 minutes
*/5 * * * * root /opt/deployments/health-check.sh

# Auto-update check daily at 2 AM
0 2 * * * root /opt/deployments/auto-update.sh

# Custom backup schedule
0 1 * * * root /opt/deployments/backup.sh
```

## 🎯 Best Practices

1. **Always test** deployments in staging environment first
2. **Monitor logs** regularly for issues
3. **Keep backups** for at least 30 days
4. **Update dependencies** regularly
5. **Review security scans** and fix vulnerabilities
6. **Use strong passwords** and rotate them regularly
7. **Enable SSL/TLS** for production environments

---

For more information, visit the [main repository](https://github.com/zawnaing-2024/smart_attendance) or check the [README.md](../README.md) file.
