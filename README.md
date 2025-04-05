# SuperPack

An enterprise management system with HR management, face recognition login, and task management features.

## Requirements

- XAMPP (with PHP 7.4+ and MySQL)
- Python 3.7+
- Python libraries:
  - OpenCV
  - NumPy
  - face_recognition
  - Other dependencies listed in Face_API/Python/requirements.txt

## Setup Instructions

### Database Setup
1. Start XAMPP (run as administrator)
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `superpack`
4. Import the following SQL files:
   - `Database/superpack_database.sql`


### Python Environment Setup
1. Install required Python packages:
   ```
   pip install opencv-python numpy face_recognition
   ```
2. Navigate to the Face_API/Python directory
3. Run the Python server:
   ```
   python main.py
   ```

### Running the Application
1. Make sure XAMPP is running (Apache and MySQL services)
2. Make sure the Python server is running
3. Access the application at: https://localhost/superpack/welcome.php

## Project Structure
- `Face_API/` - Face recognition components
- `HR_management/` - HR management system
- `Capstone2/` - Main application components
- `Database/` - SQL database files
- `ssl certificate/` - SSL certificates for secure connection

## Troubleshooting
- If you encounter database connection issues, verify your database credentials
- For Face API issues, ensure Python and required libraries are properly installed
- Check XAMPP error logs for PHP-related errors

## Hosting on Hostinger VPS

### Prerequisites
- A Hostinger VPS account
- Basic Linux command knowledge
- SSH client (like PuTTY for Windows)
- SFTP client (like FileZilla)

### Step 1: Purchase and Set Up Hostinger VPS
1. Sign up at [Hostinger](https://www.hostinger.com/vps-hosting)
2. Choose a VPS plan (minimum 2GB RAM recommended)
3. Select Ubuntu as your OS
4. Complete the purchase and note your server IP and root credentials

### Step 2: Connect to Your VPS
```bash
ssh root@your-server-ip
```

### Step 3: Update System and Install Required Packages
```bash
apt update && apt upgrade -y
apt install apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-xmlrpc php-soap php-intl php-zip -y
apt install python3 python3-pip python3-venv -y
```

### Step 4: Configure MySQL
```bash
mysql_secure_installation

# Create database and user
mysql -u root -p
CREATE DATABASE superpack_database;
CREATE USER 'superpack_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON superpack_database.* TO 'superpack_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5: Import Databases
Upload your SQL files via SFTP to `/tmp`, then:
```bash
mysql -u root -p superpack_database < /tmp/superpack_database.sql
```

### Step 6: Set Up Python Environment
```bash
mkdir -p /var/www/html/superpack
cd /var/www/html/superpack
python3 -m venv venv
source venv/bin/activate
pip install flask flask-cors numpy mysql-connector-python opencv-python deepface
pip install tensorflow keras protobuf==4.25.5
```

### Step 7: Upload Project Files
Using SFTP (FileZilla):
1. Connect to your server using SFTP
2. Upload your entire project to `/var/www/html/superpack`

### Step 8: Configure Apache
```bash
# Enable required modules
a2enmod ssl
a2enmod rewrite

# Create virtual host configuration
nano /etc/apache2/sites-available/superpack.conf
```

Add the following content:
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/superpack
    
    <Directory /var/www/html/superpack>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/superpack_error.log
    CustomLog ${APACHE_LOG_DIR}/superpack_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/superpack
    
    <Directory /var/www/html/superpack>
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/apache-selfsigned.crt
    SSLCertificateKeyFile /etc/ssl/private/apache-selfsigned.key
    
    ErrorLog ${APACHE_LOG_DIR}/superpack_error.log
    CustomLog ${APACHE_LOG_DIR}/superpack_access.log combined
</VirtualHost>

Enable the site:
```bash
a2ensite superpack.conf
systemctl restart apache2
```

### Step 9: Set Up SSL Certificate
```bash
mkdir -p /etc/ssl/certs
mkdir -p /etc/ssl/private

# Upload your existing certificates via SFTP or generate new ones:
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/apache-selfsigned.key \
  -out /etc/ssl/certs/apache-selfsigned.crt
```

### Step 10: Update Python Flask App Configuration
Edit main.py to use the correct paths:
```python
# Update context path to match your new certificate locations
context = (r'/etc/ssl/certs/apache-selfsigned.crt', r'/etc/ssl/private/apache-selfsigned.key')

# Update MySQL connection if needed
connection = mysql.connector.connect(
    host="localhost",
    user="superpack_user",  # Your MySQL username
    password="your_secure_password",  # Your MySQL password
    database="superpack_database",
    port="3306"
)

# Uncomment SSL context in app.run()
if __name__ == '__main__':
    app.run(
        debug=False,  # Set to False in production
        host='0.0.0.0',
        port=5000,
        ssl_context=context
    )
```

### Step 11: Create Systemd Service for Flask App
```bash
nano /etc/systemd/system/superpack-flask.service
```

Add the following content:
```
[Unit]
Description=Superpack Face API Flask Service
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/superpack/Face_API/Python
Environment="PATH=/var/www/html/superpack/venv/bin"
ExecStart=/var/www/html/superpack/venv/bin/python main.py
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```bash
systemctl daemon-reload
systemctl enable superpack-flask
systemctl start superpack-flask
```

### Step 12: Set Proper Permissions
```bash
chown -R www-data:www-data /var/www/html/superpack
chmod -R 755 /var/www/html/superpack
mkdir -p /var/www/html/superpack/Face_API/Python/face_embeddings
chown -R www-data:www-data /var/www/html/superpack/Face_API/Python/face_embeddings
```

### Step 13: Configure Your Domain (DNS)
1. Add an A record in your domain's DNS settings pointing to your VPS IP address
2. Wait for DNS propagation (can take up to 48 hours)

### Step 14: Test Your Setup
Visit your domain in a browser:
- https://yourdomain.com/welcome.php

### Troubleshooting VPS Setup
- Check Apache error logs: `tail -f /var/log/apache2/superpack_error.log`
- Check Flask service status: `systemctl status superpack-flask`
- Verify MySQL is running: `systemctl status mysql`
- Test database connection: `mysql -u superpack_user -p -h localhost superpack_database`
- If the Flask app doesn't work, check its log: `journalctl -u superpack-flask`

### Security Recommendations
- Set up a firewall (UFW): `ufw allow 80,443,22/tcp`
- Install Fail2Ban to prevent brute force attacks
- Create a non-root user for SSH access
- Set up automatic security updates
- Consider using Let's Encrypt for free SSL certificates