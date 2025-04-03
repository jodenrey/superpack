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