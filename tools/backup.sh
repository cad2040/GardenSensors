#!/bin/bash

# Configuration
BACKUP_DIR="/path/to/backups"
LOG_FILE="/path/to/garden-sensors/logs/backup.log"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Log function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
    echo "$1"
}

# Start backup process
log "Starting backup process..."

# Database backup
log "Backing up database..."
mysqldump -u garden_user -p garden_sensors > "$BACKUP_DIR/db_backup_$DATE.sql"
if [ $? -eq 0 ]; then
    log "Database backup completed successfully"
else
    log "ERROR: Database backup failed"
    exit 1
fi

# Compress database backup
log "Compressing database backup..."
gzip "$BACKUP_DIR/db_backup_$DATE.sql"
if [ $? -eq 0 ]; then
    log "Database backup compressed successfully"
else
    log "ERROR: Failed to compress database backup"
fi

# Backup configuration files
log "Backing up configuration files..."
tar -czf "$BACKUP_DIR/config_backup_$DATE.tar.gz" -C /path/to/garden-sensors config/
if [ $? -eq 0 ]; then
    log "Configuration backup completed successfully"
else
    log "ERROR: Configuration backup failed"
fi

# Backup uploads directory
log "Backing up uploads directory..."
tar -czf "$BACKUP_DIR/uploads_backup_$DATE.tar.gz" -C /path/to/garden-sensors public/uploads/
if [ $? -eq 0 ]; then
    log "Uploads backup completed successfully"
else
    log "ERROR: Uploads backup failed"
fi

# Clean up old backups
log "Cleaning up old backups..."
find "$BACKUP_DIR" -type f -name "*.gz" -mtime +$RETENTION_DAYS -delete
if [ $? -eq 0 ]; then
    log "Old backups cleaned up successfully"
else
    log "ERROR: Failed to clean up old backups"
fi

# Verify backup integrity
log "Verifying backup integrity..."
for file in "$BACKUP_DIR"/*_$DATE.*; do
    if [ -f "$file" ]; then
        if gzip -t "$file" 2>/dev/null; then
            log "Backup file $file verified successfully"
        else
            log "ERROR: Backup file $file verification failed"
        fi
    fi
done

# Calculate backup size
total_size=$(du -sh "$BACKUP_DIR" | cut -f1)
log "Backup process completed. Total backup size: $total_size"

# Send notification (uncomment and configure if needed)
# mail -s "Garden Sensors Backup Completed" admin@example.com << EOF
# Backup completed successfully
# Date: $(date)
# Total size: $total_size
# EOF

exit 0 