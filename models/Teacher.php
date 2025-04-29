<?php
class Teacher {
    private $conn;
    private $table_name = "teachers";

    // Properties
    public $id;
    public $user_id;
    public $subject_id;
    public $class_id;
    // ... other properties

    public function __construct($db) {
        $this->conn = $db;
    }

    // Find teacher details by user ID (needed by get_students API)
    function findByUserId($user_id) {
        $query = "SELECT id, user_id, subject_id, class_id, is_class_teacher FROM " . $this->table_name . " WHERE user_id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching teacher by user ID: " . $e->getMessage());
            return false;
        }
    }

    // Add other methods as needed
}
?> 