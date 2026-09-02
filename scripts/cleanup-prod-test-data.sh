#!/bin/bash
# Script to clean up test data from production CRM database

# SSH connection details (from memory context)
SSH_USER="u976787655"
SSH_PORT="65002"
SSH_HOST="46.17.175.73"
PROJECT_PATH="/home/u976787655/domains/pardodlaimigs.lv/public_html/crm"

echo "Cleaning up TEST data from production database..."

# Run the cleanup command on the server
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $PROJECT_PATH && php artisan pdc:cleanup-test-data --keep-attachments"

echo ""
echo "Cleanup complete. You may want to verify the results."