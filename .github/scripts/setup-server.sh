#!/bin/bash

# Server setup script for Smart Attendance System
# Run this script on a fresh Ubuntu server to set up the environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   error "This script must be run as root"
fi

# Update system packages
update_system() {
    log "Updating system packages..."
    apt update && apt upgrade -y
}

# Install required packages
install_packages() {
    log "Installing required packages..."
    apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-cli php8.1-common git unzip curl wget
}

# Configure Apache
configure_apache() {
    log "Configuring Apache..."
    
    # Enable required modules
    a2enmod rewrite
    a2enmod ssl
    
    # Create Apache configuration
    cat > /etc/apache2/sites-available/smart-attendance.conf << 'EOF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/smart_attendance
    
    <Directory /var/www/html/smart_attendance>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/smart_attendance_error.log
    CustomLog ${APACHE_LOG_DIR}/smart_attendance_access.log combined
</VirtualHost>
EOF

    # Enable site
    a2ensite smart-attendance.conf
    a2dissite 000-default
    
    # Restart Apache
    systemctl restart apache2
    systemctl enable apache2
}

# Configure MySQL
configure_mysql() {
    log "Configuring MySQL..."
    
    # Start MySQL service
    systemctl start mysql
    systemctl enable mysql
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS smart_attendance;"
    mysql -e "CREATE USER IF NOT EXISTS 'attendance_user'@'localhost' IDENTIFIED BY 'attendance_password';"
    mysql -e "GRANT ALL PRIVILEGES ON smart_attendance.* TO 'attendance_user'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    log "MySQL configured successfully"
}

# Create deployment directories
create_directories() {
    log "Creating deployment directories..."
    
    mkdir -p /opt/deployments
    mkdir -p /opt/backups/smart_attendance
    mkdir -p /var/log
    
    # Set permissions
    chmod 755 /opt/deployments
    chmod 755 /opt/backups
    chmod 755 /var/log
}

# Download and setup deployment scripts
setup_deployment_scripts() {
    log "Setting up deployment scripts..."
    
    # Download deployment script
    curl -o /opt/deployments/deploy.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/deploy.sh
    chmod +x /opt/deployments/deploy.sh
    
    # Download health check script
    curl -o /opt/deployments/health-check.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/health-check.sh
    chmod +x /opt/deployments/health-check.sh
    
    # Download auto-update script
    curl -o /opt/deployments/auto-update.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/auto-update.sh
    chmod +x /opt/deployments/auto-update.sh
    
    # Download rollback script
    curl -o /opt/deployments/rollback.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/rollback.sh
    chmod +x /opt/deployments/rollback.sh
}

# Setup cron jobs
setup_cron() {
    log "Setting up cron jobs..."
    
    # Create cron jobs file
    cat > /etc/cron.d/smart-attendance << 'EOF'
# Health check every 5 minutes
*/5 * * * * root /opt/deployments/health-check.sh

# Auto-update check daily at 2 AM
0 2 * * * root /opt/deployments/auto-update.sh

# Log rotation weekly
0 0 * * 0 root find /var/log -name "smart_attendance_*.log" -mtime +7 -delete
EOF

    # Set proper permissions
    chmod 644 /etc/cron.d/smart-attendance
}

# Configure firewall
configure_firewall() {
    log "Configuring firewall..."
    
    # Install UFW if not present
    apt install -y ufw
    
    # Configure firewall rules
    ufw --force enable
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw deny 3306/tcp
    
    log "Firewall configured successfully"
}

# Create maintenance page
create_maintenance_page() {
    log "Creating maintenance page..."
    
    cat > /var/www/html/maintenance.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: white;
        }
        .maintenance-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        h1 {
            margin-bottom: 20px;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="icon">🔧</div>
        <h1>System Maintenance</h1>
        <p>We're currently updating the system.</p>
        <p>Please check back in a few minutes.</p>
        <p>Thank you for your patience!</p>
    </div>
</body>
</html>
EOF
}

# Setup environment variables
setup_environment() {
    log "Setting up environment variables..."
    
    # Create environment file
    cat > /opt/deployments/.env << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_USER=attendance_user
DB_PASS=attendance_password
DB_NAME=smart_attendance

# Application Configuration
APP_NAME=smart_attendance
PRODUCTION_DIR=/var/www/html/smart_attendance
BACKUP_DIR=/opt/backups/smart_attendance

# Git Configuration
GIT_REPO=https://github.com/zawnaing-2024/smart_attendance.git
EOF

    # Set permissions
    chmod 600 /opt/deployments/.env
}

# Main setup function
main() {
    log "Starting Smart Attendance System server setup..."
    
    update_system
    install_packages
    configure_apache
    configure_mysql
    create_directories
    setup_deployment_scripts
    setup_cron
    configure_firewall
    create_maintenance_page
    setup_environment
    
    log "Server setup completed successfully!"
    log "You can now deploy the application using:"
    log "  /opt/deployments/deploy.sh"
    log ""
    log "Default database credentials:"
    log "  Database: smart_attendance"
    log "  Username: attendance_user"
    log "  Password: attendance_password"
    log ""
    log "Please change the default password for security!"
}

# Run main function
main "$@"
