# Deploying SuperPack to Hostinger Shared Hosting

This guide provides step-by-step instructions for deploying the SuperPack enterprise management system on Hostinger shared hosting.

## Pre-deployment Preparation

1. Export your local MySQL database from XAMPP:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Select your `superpack_database`
   - Click on the "Export" tab
   - Choose "Quick" export method and SQL format
   - Click "Go" to download the .sql file

2. Update database configuration:
   - Find and update database connection details in:
     - `HR_management/database.php`
     - `Face_API/Python/main.py` (for the Python server)
   - Replace with your Hostinger database credentials

## Deployment Steps

### 1. Setting up Hostinger Database

1. Log in to your Hostinger hPanel
2. Navigate to "Databases" → "MySQL Databases"
3. Create a new database (e.g., `superpack_database`)
4. Create a database user and assign it to the database
5. Note down the database name, username, password, and hostname

### 2. Uploading Files

#### Option A: Using Hostinger File Manager

1. Log in to your Hostinger hPanel
2. Go to "Files" → "File Manager"
3. Navigate to the public_html directory
4. Upload all files from your local SuperPack project

#### Option B: Using FTP

1. Use an FTP client like FileZilla
2. Connect to your Hostinger account using the FTP credentials provided by Hostinger
3. Upload all files to the public_html directory

### 3. Importing Database

1. In Hostinger hPanel, go to "Databases" → "MySQL Databases"
2. Find your database and click "Manage"
3. In phpMyAdmin, select your database
4. Click on the "Import" tab
5. Click "Choose File" and select your exported .sql file
6. Click "Go" to import the database

### 4. Updating Configuration

1. Update the database connection information in:
   - `HR_management/database.php`
   ```php
   $dsn = "mysql:host=HOSTINGER_DB_HOST;port=3306;dbname=YOUR_DB_NAME";
   $dbusername = "YOUR_DB_USERNAME";
   $dbpassword = "YOUR_DB_PASSWORD";
   ```

2. Update any hardcoded URLs or paths in your PHP files:
   - Replace localhost references with your domain name
   - Update file paths if necessary

### 5. SSL Certificate Configuration

1. In Hostinger hPanel, go to "SSL/TLS"
2. Enable SSL for your domain
3. Update any hardcoded SSL certificate paths in your code

### 6. Python Server Setup (For Face Recognition)

**Note:** Hostinger shared hosting does not support running Python services directly. You have two options:

#### Option A: Using a Separate VPS/Service for Python
1. Deploy the Python component on a separate VPS or cloud service (e.g., Heroku, PythonAnywhere)
2. Update the API endpoints in your PHP code to point to this external service

#### Option B: Simplifying Your App
1. Remove the face recognition functionality for shared hosting
2. Implement alternative authentication methods

### 7. Testing

1. Visit your website at `https://yourdomain.com/welcome.php`
2. Test all functionality to ensure it's working correctly
3. Check for any error messages or issues

## Troubleshooting

- **Database Connection Issues**: Verify your database credentials and hostname
- **File Permission Errors**: Set appropriate permissions (usually 755 for directories, 644 for files)
- **404 Errors**: Check your .htaccess file configuration
- **SSL Issues**: Ensure SSL is properly set up in Hostinger hPanel

## Additional Notes

- If you encounter issues with the Python face recognition component, consider deploying it separately or modifying your application to work without it on shared hosting
- For better performance, consider upgrading to Hostinger's VPS hosting which would allow you to run the Python server 