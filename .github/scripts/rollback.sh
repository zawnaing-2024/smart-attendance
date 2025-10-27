#!/bin/bash

# Rollback script for Smart Attendance System
# Usage: ./rollback.sh [backup_timestamp]

set -e

# Configuration
APP_NAME="smart_attendance"
PRODUCTION_DIR="/var/www/html/smart_attendance"
BACKUP_DIR="/opt/backups/smart_attendance"
DB_NAME="smart_attendance"
DB_USER="attendance_user"
DB_PASS="${DB_PASSWORD:-attendance_password}"

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

# List available backups
list_backups() {
    log "Available backups:"
    if [ -d "$BACKUP_DIR" ]; then
        ls -la "$BACKUP_DIR" | grep "backup_" | awk '{print $9, $6, $7, $8}' | while read backup_name date time; do
            if [ -n "$backup_name" ]; then
                echo "  - $backup_name (created: $date $time)"
            fi
        done
    else
        warning "No backup directory found at $BACKUP_DIR"
    fi
}

# Rollback to specific backup
rollback_to_backup() {
    local backup_timestamp=$1
    local backup_path="$BACKUP_DIR/backup_$backup_timestamp"
    
    if [ ! -d "$backup_path" ]; then
        error "Backup not found: $backup_path"
    fi
    
    log "Rolling back to backup: $backup_timestamp"
    
    # Create maintenance flag
    sudo touch "$PRODUCTION_DIR/maintenance.flag"
    
    # Backup current version
    local current_backup="/tmp/smart_attendance_current_$(date +%Y%m%d_%H%M%S)"
    sudo cp -r "$PRODUCTION_DIR" "$current_backup"
    log "Current version backed up to: $current_backup"
    
    # Restore from backup
    sudo rm -rf "$PRODUCTION_DIR"
    sudo cp -r "$backup_path/smart_attendance" "$PRODUCTION_DIR"
    
    # Restore database
    if [ -f "$backup_path/database_backup.sql" ]; then
        log "Restoring database..."
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$backup_path/database_backup.sql"
    else
        warning "Database backup not found, skipping database restore"
    fi
    
    # Set proper permissions
    sudo chown -R www-data:www-data "$PRODUCTION_DIR"
    sudo chmod -R 755 "$PRODUCTION_DIR"
    sudo chmod -R 777 "$PRODUCTION_DIR/uploads"
    
    # Remove maintenance flag
    sudo rm -f "$PRODUCTION_DIR/maintenance.flag"
    
    # Verify rollback
    if [ -f "$PRODUCTION_DIR/index.php" ]; then
        log "Rollback completed successfully!"
        
        # Test database connection
        if php -r "
            require_once '$PRODUCTION_DIR/config/database.php';
            echo 'Database connection: OK\n';
        "; then
            log "Database connection verified!"
        else
            error "Database connection failed after rollback!"
        fi
    else
        error "Rollback failed!"
    fi
}

# Rollback to latest backup
rollback_to_latest() {
    local latest_backup=$(ls -t "$BACKUP_DIR"/backup_* 2>/dev/null | head -1 | xargs basename)
    
    if [ -z "$latest_backup" ]; then
        error "No backups found in $BACKUP_DIR"
    fi
    
    local backup_timestamp=$(echo "$latest_backup" | sed 's/backup_//')
    log "Latest backup found: $backup_timestamp"
    
    rollback_to_backup "$backup_timestamp"
}

# Main function
main() {
    log "Starting rollback process..."
    
    # Check if running as root
    if [[ $EUID -eq 0 ]]; then
       error "This script should not be run as root for security reasons"
    fi
    
    # Check if production directory exists
    if [ ! -d "$PRODUCTION_DIR" ]; then
        error "Production directory $PRODUCTION_DIR does not exist"
    fi
    
    # Check if backup directory exists
    if [ ! -d "$BACKUP_DIR" ]; then
        error "Backup directory $BACKUP_DIR does not exist"
    fi
    
    if [ $# -eq 0 ]; then
        # No arguments provided, show available backups and ask for confirmation
        list_backups
        echo ""
        read -p "Do you want to rollback to the latest backup? (y/N): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rollback_to_latest
        else
            log "Rollback cancelled by user"
            exit 0
        fi
    else
        # Specific backup timestamp provided
        rollback_to_backup "$1"
    fi
    
    log "Rollback process completed!"
}

# Run main function
main "$@"
