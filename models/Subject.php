<?php
class Subject {
    private $conn;
    private $table_name = "subjects";

    // Properties
    public $id;
    public $name;
    public $code;
    // ... other properties

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all subjects
    function read() {
        $query = "SELECT id, name, code, is_ple_subject FROM " . $this->table_name . " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching subjects: " . $e->getMessage());
            return false;
        }
    }

     // Add other methods like readOne, create, update, delete as needed
}
?> 