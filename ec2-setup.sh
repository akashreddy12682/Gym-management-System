#!/bin/bash
# ==============================================================================
# AWS EC2 Ubuntu Deployment Script
# Project: Gym Management System (PHP 8+, Apache, MySQL)
# Target OS: Ubuntu 22.04 LTS / 24.04 LTS on AWS EC2
# ==============================================================================

set -e

echo "=========================================================="
echo "Starting Gym Management System Setup on AWS EC2 Ubuntu..."
echo "=========================================================="

# 1. Update system packages
echo "[1/7] Updating system package index..."
sudo apt-get update -y
sudo apt-get upgrade -y

# 2. Install Apache web server
echo "[2/7] Installing Apache2 web server..."
sudo apt-get install -y apache2

# 3. Install PHP 8.x and required extensions
echo "[3/7] Installing PHP and modules..."
sudo apt-get install -y php libapache2-mod-php php-mysql php-mbstring php-xml php-curl php-zip php-gd

# 4. Install MySQL Server
echo "[4/7] Installing MySQL Server..."
sudo apt-get install -y mysql-server

# Start and enable services
sudo systemctl enable apache2
sudo systemctl start apache2
sudo systemctl enable mysql
sudo systemctl start mysql

# 5. Create Database and Import Schema
echo "[5/7] Creating gym_management database and seeding tables..."
DB_NAME="${DB_NAME:-gym_management}"
DB_USER="${DB_USER:-gym_user}"
DB_PASS="${DB_PASS:-GymSecurePass2026!}"

sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Locate and import schema.sql if exists in the current directory
CURRENT_DIR=$(pwd)
if [ -f "$CURRENT_DIR/database/schema.sql" ]; then
    echo "Importing schema from $CURRENT_DIR/database/schema.sql..."
    sudo mysql "$DB_NAME" < "$CURRENT_DIR/database/schema.sql"
elif [ -f "./schema.sql" ]; then
    sudo mysql "$DB_NAME" < "./schema.sql"
fi

# 6. Deploy Code to Apache DocumentRoot
echo "[6/7] Deploying application files to /var/www/html/gym-management-system..."
TARGET_DIR="/var/www/html/gym-management-system"
sudo mkdir -p "$TARGET_DIR"

if [ "$CURRENT_DIR" != "$TARGET_DIR" ]; then
    sudo cp -r "$CURRENT_DIR/"* "$TARGET_DIR/"
fi

# Configure config/database.php for the newly created user
sudo tee "$TARGET_DIR/config/database.php" > /dev/null << EOF
<?php
/**
 * Database Configuration & Connection Handler
 * Configured for AWS EC2 Deployment
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: '${DB_NAME}');
define('DB_USER', getenv('DB_USER') ?: '${DB_USER}');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '${DB_PASS}');

function get_db_connection() {
    static \$pdo = null;
    if (\$pdo === null) {
        \$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die("Database Connection Error: " . \$e->getMessage());
        }
    }
    return \$pdo;
}
EOF

# 7. Configure Permissions and Apache Rewrite
echo "[7/7] Configuring permissions and Apache modules..."
sudo chown -R www-data:www-data "$TARGET_DIR"
sudo chmod -R 755 "$TARGET_DIR"

# Enable rewrite module
sudo a2enmod rewrite

# Restart Apache
sudo systemctl restart apache2

# Obtain Public IP
PUBLIC_IP=$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4 || curl -s ifconfig.me || echo "YOUR-EC2-PUBLIC-IP")

echo "=========================================================="
echo "Deployment Complete!"
echo "You can now access your Gym Management System at:"
echo "http://${PUBLIC_IP}/gym-management-system/"
echo "=========================================================="
echo "Default Logins:"
echo "Admin   : admin@gym.com    | Password: admin123"
echo "Trainer : alex@gym.com     | Password: trainer123"
echo "Member  : john@gmail.com   | Password: member123"
echo "=========================================================="
