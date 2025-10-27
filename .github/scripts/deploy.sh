#!/bin/bash

# Smart Attendance System - Zero Downtime Deployment Script
# Usage: ./deploy.sh [environment]

set -e

# Configuration
APP_NAME="smart_attendance"
PRODUCTION_DIR="/var/www/html/smart_attendance"
BACKUP_DIR="/opt/backups/smart_attendance"
TEMP_DIR="/tmp/smart_attendance_update"
GIT_REPO="https://github.com/zawnaing-2024/smart_attendance.git"
DB_NAME="smart_attendance"
DB_USER="attendance_user"
DB_PASS="${DB_PASSWORD:-attendance_password}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
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
if [[ $EUID -eq 0 ]]; then
   error "This script should not be run as root for security reasons"
fi

# Check if required directories exist
if [ ! -d "$PRODUCTION_DIR" ]; then
    error "Production directory $PRODUCTION_DIR does not exist"
fi

log "Starting zero-downtime deployment..."

# Step 1: Create backup
log "Creating backup of current production..."
BACKUP_TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="$BACKUP_DIR/backup_$BACKUP_TIMESTAMP"

sudo mkdir -p "$BACKUP_PATH"
sudo cp -r "$PRODUCTION_DIR" "$BACKUP_PATH/"
sudo mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/database_backup.sql"

log "Backup created at: $BACKUP_PATH"

# Step 2: Clone new version to temporary directory
log "Cloning latest version to temporary directory..."
rm -rf "$TEMP_DIR"
git clone "$GIT_REPO" "$TEMP_DIR"

# Step 3: Preserve production configuration
log "Preserving production configuration..."
cp "$PRODUCTION_DIR/config/database.php" "$TEMP_DIR/config/"
cp -r "$PRODUCTION_DIR/uploads" "$TEMP_DIR/"

# Step 4: Set proper permissions
log "Setting proper permissions..."
sudo chown -R www-data:www-data "$TEMP_DIR"
sudo chmod -R 755 "$TEMP_DIR"
sudo chmod -R 777 "$TEMP_DIR/uploads"

# Step 5: Run database migrations (if any)
log "Checking for database migrations..."
if [ -f "$TEMP_DIR/database/migrations.sql" ]; then
    log "Running database migrations..."
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$TEMP_DIR/database/migrations.sql"
fi

# Step 6: Atomic deployment (zero downtime)
log "Performing atomic deployment..."

# Create maintenance flag
sudo touch "$PRODUCTION_DIR/maintenance.flag"

# Quick atomic move
sudo mv "$PRODUCTION_DIR" "$PRODUCTION_DIR.old"
sudo mv "$TEMP_DIR" "$PRODUCTION_DIR"

# Remove maintenance flag
sudo rm -f "$PRODUCTION_DIR/maintenance.flag"

# Step 7: Verify deployment
log "Verifying deployment..."
if [ -f "$PRODUCTION_DIR/index.php" ]; then
    log "Deployment successful!"
    
    # Clean up old version after 5 minutes
    log "Old version will be cleaned up in 5 minutes..."
    (sleep 300 && sudo rm -rf "$PRODUCTION_DIR.old") &
    
    # Test database connection
    if php -r "
        require_once '$PRODUCTION_DIR/config/database.php';
        echo 'Database connection: OK\n';
    "; then
        log "Database connection verified!"
    else
        error "Database connection failed! Rolling back..."
        sudo mv "$PRODUCTION_DIR" "$PRODUCTION_DIR.failed"
        sudo mv "$PRODUCTION_DIR.old" "$PRODUCTION_DIR"
        sudo rm -f "$PRODUCTION_DIR/maintenance.flag"
    fi
    
else
    error "Deployment failed! Rolling back..."
    sudo mv "$PRODUCTION_DIR" "$PRODUCTION_DIR.failed"
    sudo mv "$PRODUCTION_DIR.old" "$PRODUCTION_DIR"
    sudo rm -f "$PRODUCTION_DIR/maintenance.flag"
fi

log "Deployment completed successfully!"
log "Application is now running the latest version"

# Step 8: Run post-deployment tasks
log "Running post-deployment tasks..."

# Clear any caches
if [ -f "$PRODUCTION_DIR/cache" ]; then
    sudo rm -rf "$PRODUCTION_DIR/cache/*"
fi

# Restart services if needed
sudo systemctl reload apache2

log "Post-deployment tasks completed!"
