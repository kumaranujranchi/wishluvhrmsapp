#!/bin/bash

# Server Details (Maine error logs se nikala hai)
SERVER_USER="u743439976"
SERVER_HOST="wishluvbuildcon.com" # Ya server ka IP address
PROJECT_PATH="/home/u743439976/domains/wishluvbuildcon.com/public_html" 
# Note: Agar 'hrms' folder mein hai toh path ke end mein /hrms lagayein

echo "============================================"
echo "Starting Deployment to $SERVER_HOST..."
echo "============================================"

# Connect to server and pull latest code
ssh -p 65002 $SERVER_USER@$SERVER_HOST "cd $PROJECT_PATH && git checkout employee_dashboard.php sw.js && git pull origin main && echo '✅ Deployment Successful: Github se latest code server par aa gaya hai.'"

echo "============================================"
echo "Done."
