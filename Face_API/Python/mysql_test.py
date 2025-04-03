# Import mysql connector
import mysql.connector
import datetime
import os

# Define the connection
connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="password",
    database="superpack_database",
    port = "3306"
)

# Define the cursor
cursor = connection.cursor()

# Check if register table exists
cursor.execute("SHOW TABLES LIKE 'register'")
table_exists = cursor.fetchone()

if table_exists:
    # If the table exists, check its structure
    cursor.execute("DESCRIBE register")
    columns = cursor.fetchall()
    
    # Print the current table structure
    print("Current register table structure:")
    column_names = [column[0] for column in columns]
    print(column_names)
    
    # Check if name column exists
    if 'name' not in column_names:
        # Try to add the name column if not exists
        try:
            cursor.execute("ALTER TABLE register ADD COLUMN name VARCHAR(100) NOT NULL")
            connection.commit()
            print("Added 'name' column to register table")
        except Exception as e:
            print(f"Error adding name column: {str(e)}")
    
    # Check if role column exists
    if 'role' not in column_names:
        try:
            cursor.execute("ALTER TABLE register ADD COLUMN role VARCHAR(50) NOT NULL")
            connection.commit()
            print("Added 'role' column to register table")
        except Exception as e:
            print(f"Error adding role column: {str(e)}")
    
    # Check if department column exists
    if 'department' not in column_names:
        try:
            cursor.execute("ALTER TABLE register ADD COLUMN department VARCHAR(50) NOT NULL")
            connection.commit()
            print("Added 'department' column to register table")
        except Exception as e:
            print(f"Error adding department column: {str(e)}")
    
    # Check if landmarks_hash column exists
    if 'landmarks_hash' not in column_names:
        try:
            cursor.execute("ALTER TABLE register ADD COLUMN landmarks_hash TEXT")
            connection.commit()
            print("Added 'landmarks_hash' column to register table")
        except Exception as e:
            print(f"Error adding landmarks_hash column: {str(e)}")
    
else:
    # Create the register table with all required columns
    create_table_query = """
    CREATE TABLE register (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        landmarks_hash TEXT,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    """
    cursor.execute(create_table_query)
    connection.commit()
    print("Created new register table with all required columns")

# Create directory for face embeddings if it doesn't exist
os.makedirs('face_embeddings', exist_ok=True)
print("Ensured face_embeddings directory exists")

# Check if attendance table exists
cursor.execute("SHOW TABLES LIKE 'attendance'")
attendance_exists = cursor.fetchone()

if not attendance_exists:
    # Create the attendance table
    create_attendance_query = """
    CREATE TABLE attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL,
        time_in DATETIME,
        time_out DATETIME,
        date DATE DEFAULT (CURRENT_DATE)
    )
    """
    cursor.execute(create_attendance_query)
    connection.commit()
    print("Created new attendance table")

print("Database setup completed successfully")

# Close the connection
cursor.close()
connection.close()


