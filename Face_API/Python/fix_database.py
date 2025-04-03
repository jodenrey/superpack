import mysql.connector
import os

print("====== Database Fix Script ======")

# Define the connection
connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="password",
    database="superpack_database",
    port="3306"
)

# Define the cursor
cursor = connection.cursor()

# Create directory for face embeddings if it doesn't exist
os.makedirs('face_embeddings', exist_ok=True)
print("✓ Ensured face_embeddings directory exists")

try:
    # ===== FIX REGISTER TABLE =====
    # Drop the register table if it exists and recreate it with correct structure
    print("\nFixing register table...")
    
    # Try dropping the register table
    try:
        cursor.execute("DROP TABLE IF EXISTS register")
        connection.commit()
        print("✓ Dropped existing register table")
    except Exception as e:
        print(f"Error dropping register table: {str(e)}")
    
    # Create register table with the correct structure
    create_register_query = """
    CREATE TABLE register (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        landmarks_hash TEXT,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    """
    cursor.execute(create_register_query)
    connection.commit()
    print("✓ Created new register table with correct structure")
    
    # ===== FIX ATTENDANCE TABLE =====
    print("\nFixing attendance table...")
    try:
        cursor.execute("DROP TABLE IF EXISTS attendance")
        connection.commit()
        print("✓ Dropped existing attendance table")
    except Exception as e:
        print(f"Error dropping attendance table: {str(e)}")
    
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
    print("✓ Created new attendance table")
    
    # ===== FIX USERS TABLE =====
    print("\nFixing users table...")
    try:
        cursor.execute("DROP TABLE IF EXISTS users")
        connection.commit()
        print("✓ Dropped existing users table")
    except Exception as e:
        print(f"Error dropping users table: {str(e)}")
    
    # Create the users table with proper structure
    create_users_table = """
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    """
    cursor.execute(create_users_table)
    connection.commit()
    print("✓ Created new users table with correct structure")
    
    # Verify tables were created properly
    print("\nVerifying table structure...")
    cursor.execute("DESCRIBE register")
    register_columns = [column[0] for column in cursor.fetchall()]
    print(f"Register table columns: {register_columns}")
    
    cursor.execute("DESCRIBE attendance")
    attendance_columns = [column[0] for column in cursor.fetchall()]
    print(f"Attendance table columns: {attendance_columns}")
    
    # Verify users table structure
    cursor.execute("DESCRIBE users")
    users_columns = [column[0] for column in cursor.fetchall()]
    print(f"Users table columns: {users_columns}")
    
    print("\n✓ DATABASE FIX COMPLETE! ✓")
    
except Exception as e:
    print(f"\n❌ ERROR: {str(e)}")
finally:
    # Close the connection
    cursor.close()
    connection.close()
    print("\nDatabase connection closed") 