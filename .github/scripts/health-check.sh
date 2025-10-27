#!/bin/bash

# Health check script for Smart Attendance System
APP_URL="http://localhost/smart_attendance"
LOG_FILE="/var/log/smart_attendance_health.log"
DB_NAME="smart_attendance"
DB_USER="attendance_user"
DB_PASS="${DB_PASSWORD:-attendance_password}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

# Check if application is responding
check_application() {
    if curl -f -s "$APP_URL" > /dev/null; then
        log "Application is healthy"
        echo "$(date): Application is healthy" >> "$LOG_FILE"
        return 0
    else
        error "Application is down!"
        echo "$(date): Application is down!" >> "$LOG_FILE"
        return 1
    fi
}

# Check database connection
check_database() {
    if mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" "$DB_NAME" > /dev/null 2>&1; then
        log "Database is healthy"
        echo "$(date): Database is healthy" >> "$LOG_FILE"
        return 0
    else
        error "Database connection failed!"
        echo "$(date): Database connection failed!" >> "$LOG_FILE"
        return 1
    fi
}

# Check disk space
check_disk_space() {
    DISK_USAGE=$(df /var/www/html | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 90 ]; then
        warning "Disk usage is high: ${DISK_USAGE}%"
        echo "$(date): Disk usage is high: ${DISK_USAGE}%" >> "$LOG_FILE"
        return 1
    else
        log "Disk usage is normal: ${DISK_USAGE}%"
        echo "$(date): Disk usage is normal: ${DISK_USAGE}%" >> "$LOG_FILE"
        return 0
    fi
}

# Check memory usage
check_memory() {
    MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    if [ "$MEMORY_USAGE" -gt 90 ]; then
        warning "Memory usage is high: ${MEMORY_USAGE}%"
        echo "$(date): Memory usage is high: ${MEMORY_USAGE}%" >> "$LOG_FILE"
        return 1
    else
        log "Memory usage is normal: ${MEMORY_USAGE}%"
        echo "$(date): Memory usage is normal: ${MEMORY_USAGE}%" >> "$LOG_FILE"
        return 0
    fi
}

# Check Apache status
check_apache() {
    if systemctl is-active --quiet apache2; then
        log "Apache is running"
        echo "$(date): Apache is running" >> "$LOG_FILE"
        return 0
    else
        error "Apache is not running!"
        echo "$(date): Apache is not running!" >> "$LOG_FILE"
        return 1
    fi
}

# Check MySQL status
check_mysql() {
    if systemctl is-active --quiet mysql; then
        log "MySQL is running"
        echo "$(date): MySQL is running" >> "$LOG_FILE"
        return 0
    else
        error "MySQL is not running!"
        echo "$(date): MySQL is not running!" >> "$LOG_FILE"
        return 1
    fi
}

# Main health check
main() {
    log "Starting health check..."
    
    local overall_status=0
    
    check_application || overall_status=1
    check_database || overall_status=1
    check_disk_space || overall_status=1
    check_memory || overall_status=1
    check_apache || overall_status=1
    check_mysql || overall_status=1
    
    if [ $overall_status -eq 0 ]; then
        log "All health checks passed!"
        echo "$(date): All health checks passed!" >> "$LOG_FILE"
    else
        error "Some health checks failed!"
        echo "$(date): Some health checks failed!" >> "$LOG_FILE"
        
        # Send alert (customize as needed)
        # mail -s "Smart Attendance System Health Check Failed" admin@yourdomain.com < "$LOG_FILE"
    fi
    
    return $overall_status
}

# Run health check
main "$@"
