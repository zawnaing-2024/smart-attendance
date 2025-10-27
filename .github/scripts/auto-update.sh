#!/bin/bash

# Automated update script with cron
# Add to crontab: 0 2 * * * /opt/deployments/auto-update.sh

LOG_FILE="/var/log/smart_attendance_updates.log"
DEPLOY_SCRIPT="/opt/deployments/deploy.sh"
GIT_REPO="https://github.com/zawnaing-2024/smart_attendance.git"
PRODUCTION_DIR="/var/www/html/smart_attendance"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
    echo "$(date): $1" >> "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    echo "$(date): ERROR: $1" >> "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
    echo "$(date): WARNING: $1" >> "$LOG_FILE"
}

# Check for updates
check_for_updates() {
    log "Checking for updates..."
    
    cd /tmp
    rm -rf temp_check
    git clone "$GIT_REPO" temp_check
    cd temp_check

    # Get latest commit hash
    LATEST_COMMIT=$(git rev-parse HEAD)
    CURRENT_COMMIT=$(cd "$PRODUCTION_DIR" && git rev-parse HEAD)

    if [ "$LATEST_COMMIT" != "$CURRENT_COMMIT" ]; then
        log "Update available. Latest commit: $LATEST_COMMIT"
        log "Current commit: $CURRENT_COMMIT"
        return 0
    else
        log "No updates available."
        return 1
    fi
}

# Run deployment
run_deployment() {
    log "Running deployment..."
    
    if [ -f "$DEPLOY_SCRIPT" ]; then
        "$DEPLOY_SCRIPT" >> "$LOG_FILE" 2>&1
        if [ $? -eq 0 ]; then
            log "Deployment completed successfully!"
            return 0
        else
            error "Deployment failed!"
            return 1
        fi
    else
        error "Deployment script not found at $DEPLOY_SCRIPT"
        return 1
    fi
}

# Send notification
send_notification() {
    local status=$1
    local message=$2
    
    # Customize notification method (email, Slack, Discord, etc.)
    if [ "$status" = "success" ]; then
        log "✅ $message"
        # mail -s "Smart Attendance System Updated Successfully" admin@yourdomain.com <<< "$message"
    else
        error "❌ $message"
        # mail -s "Smart Attendance System Update Failed" admin@yourdomain.com <<< "$message"
    fi
}

# Main function
main() {
    log "Starting automated update check..."
    
    # Check if production directory exists
    if [ ! -d "$PRODUCTION_DIR" ]; then
        error "Production directory $PRODUCTION_DIR does not exist"
        exit 1
    fi
    
    # Check for updates
    if check_for_updates; then
        log "Updates found, starting deployment..."
        
        # Run deployment
        if run_deployment; then
            send_notification "success" "Smart Attendance System has been updated successfully!"
        else
            send_notification "error" "Smart Attendance System update failed! Check logs for details."
            exit 1
        fi
    else
        log "No updates available, skipping deployment."
    fi
    
    # Cleanup
    rm -rf /tmp/temp_check
    
    log "Automated update check completed."
}

# Run main function
main "$@"
