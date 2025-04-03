# Import needed modules
import mysql.connector

# Function to register a user with facial recognition
def register_user_with_facial_recognition(name, username, role, department, face_image_path):
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            password="password",
            database="superpack_database"
        )
        
        cursor = connection.cursor()
        
        # Start transaction to ensure data consistency across all tables
        cursor.execute("START TRANSACTION")
        
        # 1. Insert into register table (facial recognition data)
        insert_register = "INSERT INTO register (name, role, department, landmarks_hash) VALUES (%s, %s, %s, %s)"
        cursor.execute(insert_register, (name, role, department, face_image_path))
        register_id = cursor.lastrowid
        
        # 2. Insert into employee_records table
        insert_employee = """
        INSERT INTO employee_records 
        (name, position, address, phone_number, age, email, shift, salary, status, start_date) 
        VALUES (%s, %s, '', '', '', '', '1', '0', 'Active', NOW())
        """
        cursor.execute(insert_employee, (name, role))
        employee_id = cursor.lastrowid
        
        # 3. Insert into worker_evaluations
        emp_id = f"EMP-{employee_id:04d}"
        insert_evaluation = """
        INSERT INTO worker_evaluations 
        (id, employee_id, name, position, department, start_date, comments, performance) 
        VALUES (%s, %s, %s, %s, %s, NOW(), '', 0)
        """
        cursor.execute(insert_evaluation, (emp_id, emp_id, name, role, department))
        
        # 4. Insert into payroll_records
        insert_payroll = """
        INSERT INTO payroll_records 
        (name, position, salary, daily_rate, basic_pay, ot_pay, late_deduct, gross_pay, 
        sss_deduct, pagibig_deduct, total_deduct, net_salary, date_created) 
        VALUES (%s, %s, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NOW())
        """
        cursor.execute(insert_payroll, (name, role))
        
        # Commit all changes
        connection.commit()
        return True, "User registered successfully in all systems"
    except Exception as e:
        # Rollback in case of error
        connection.rollback()
        return False, f"Error registering user: {str(e)}"
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close() 