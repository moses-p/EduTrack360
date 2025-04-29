<?php
class Student {
    private $conn;
    private $table_name = "students";

    // Properties
    public $id;
    public $admission_number;
    public $full_name;
    // ... other properties

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read students by class ID
    function readByClass($class_id) {
        $query = "SELECT id, admission_number, full_name FROM " . $this->table_name . " WHERE class_id = ? ORDER BY full_name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $class_id);
        
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error or handle appropriately
            error_log("Error fetching students by class: " . $e->getMessage());
            return false;
        }
    }

    // Read single student details (needed by save_mark API)
    function readOne($student_id) {
         $query = "SELECT id, admission_number, full_name, class_id FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching single student: " . $e->getMessage());
            return false;
        }
    }

    // Create a new student
    function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    full_name=:full_name, admission_number=:admission_number,
                    date_of_birth=:date_of_birth, gender=:gender, class_id=:class_id,
                    parent_id=:parent_id"; // created_at/updated_at have defaults

        $stmt = $this->conn->prepare($query);

        // Sanitize properties (basic)
        $this->full_name=htmlspecialchars(strip_tags($this->full_name));
        $this->admission_number=htmlspecialchars(strip_tags($this->admission_number));
        $this->date_of_birth=htmlspecialchars(strip_tags($this->date_of_birth));
        $this->gender=htmlspecialchars(strip_tags($this->gender));
        $this->class_id=htmlspecialchars(strip_tags($this->class_id));
        // Handle null parent_id
        $this->parent_id = ($this->parent_id === null || $this->parent_id === '') ? null : htmlspecialchars(strip_tags($this->parent_id));

        // Bind values
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":admission_number", $this->admission_number);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":class_id", $this->class_id);
        $stmt->bindParam(":parent_id", $this->parent_id, ($this->parent_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT));
        
        try {
            if($stmt->execute()){
                $this->id = $this->conn->lastInsertId(); // Get the ID of the newly created student
                return true;
            }
        } catch (PDOException $e) {
             error_log("Error creating student: " . $e->getMessage());
             // You might want to check for specific errors like duplicate admission number if constraint exists
        }
    
        return false;
    }

    // Add other methods like create, update, delete as needed
}
?> 