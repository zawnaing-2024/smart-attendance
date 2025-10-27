#!/bin/bash

# Upload script for Smart Attendance System to GitHub
# This script will initialize git and push all files to the repository

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

# Configuration
REPO_URL="https://github.com/zawnaing-2024/smart_attendance.git"
REPO_NAME="smart_attendance"

# Check if git is installed
if ! command -v git &> /dev/null; then
    error "Git is not installed. Please install git first."
fi

# Check if we're in the project directory
if [ ! -f "index.php" ]; then
    error "Please run this script from the Smart Attendance System project directory"
fi

log "Starting upload to GitHub repository..."

# Initialize git repository if not already initialized
if [ ! -d ".git" ]; then
    log "Initializing git repository..."
    git init
    git branch -M main
else
    log "Git repository already initialized"
fi

# Add remote origin
log "Setting up remote repository..."
git remote remove origin 2>/dev/null || true
git remote add origin "$REPO_URL"

# Create .gitignore file
log "Creating .gitignore file..."
cat > .gitignore << 'EOF'
# Environment files
.env
.env.local
.env.production

# Logs
*.log
logs/
/var/log/

# Database backups
*.sql
backups/
/opt/backups/

# Uploads (keep structure but ignore content)
uploads/faces/*
!uploads/faces/.gitkeep

# Cache
cache/
tmp/
temp/

# IDE files
.vscode/
.idea/
*.swp
*.swo
*~

# OS files
.DS_Store
Thumbs.db

# Composer
vendor/
composer.lock

# Node modules (if any)
node_modules/
npm-debug.log*

# Deployment files (keep scripts but ignore local configs)
/opt/deployments/.env
/opt/deployments/local_*

# SSL certificates
*.pem
*.key
*.crt

# Local configuration overrides
config/local.php
config/production.php
EOF

# Create uploads directory structure
log "Creating uploads directory structure..."
mkdir -p uploads/faces
touch uploads/faces/.gitkeep

# Add all files to git
log "Adding files to git..."
git add .

# Check if there are any changes to commit
if git diff --staged --quiet; then
    warning "No changes to commit. Repository might already be up to date."
else
    # Commit changes
    log "Committing changes..."
    git commit -m "Initial commit: Smart Attendance System

Features:
- Complete admin portal with CCTV, student, grade, teacher management
- Teacher portal with attendance tracking and reports
- Live portal with face detection framework
- SMS integration for parent notifications
- Zero-downtime deployment scripts
- GitHub Actions workflows
- Comprehensive security features
- Production-ready configuration

Components:
- PHP/MySQL backend
- Bootstrap 5 responsive frontend
- Face detection integration ready
- Automated deployment system
- Health monitoring and rollback capabilities"
fi

# Push to GitHub
log "Pushing to GitHub repository..."
git push -u origin main

if [ $? -eq 0 ]; then
    log "✅ Successfully uploaded to GitHub!"
    log "Repository URL: $REPO_URL"
    log ""
    log "Next steps:"
    log "1. Configure GitHub Secrets for deployment:"
    log "   - SERVER_SSH_KEY"
    log "   - SERVER_USER"
    log "   - SERVER_HOST"
    log "   - DB_PASSWORD"
    log ""
    log "2. Set up your production server:"
    log "   curl -o setup-server.sh https://raw.githubusercontent.com/zawnaing-2024/smart_attendance/main/.github/scripts/setup-server.sh"
    log "   chmod +x setup-server.sh"
    log "   sudo ./setup-server.sh"
    log ""
    log "3. Deploy the application:"
    log "   /opt/deployments/deploy.sh production"
    log ""
    log "4. Access your application at:"
    log "   http://your-server-ip/smart_attendance"
    log "   Default login: admin / admin123"
else
    error "Failed to push to GitHub. Please check your credentials and try again."
fi

log "Upload process completed!"
