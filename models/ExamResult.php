<?php
class ExamResult {
    private $conn;
    private $table_name = "exam_results";

    // Properties
    public $id;
    public $student_id;
    public $subject_id;
    public $class_id;
    public $term;
    public $year;
    public $marks;
    public $grade;
    public $remarks;
    public $is_ple;
    // ... other properties

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new exam result entry
    function create() {
        // Check if entry already exists for this student, subject, term, year?
        // Optional: Add UPDATE logic if entry exists, or prevent duplicates
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    student_id=:student_id, subject_id=:subject_id, class_id=:class_id,
                    term=:term, year=:year, marks=:marks, grade=:grade, 
                    remarks=:remarks, is_ple=:is_ple";

        $stmt = $this->conn->prepare($query);

        // Sanitize properties
        $this->student_id=htmlspecialchars(strip_tags($this->student_id));
        $this->subject_id=htmlspecialchars(strip_tags($this->subject_id));
        $this->class_id=htmlspecialchars(strip_tags($this->class_id));
        $this->term=htmlspecialchars(strip_tags($this->term));
        $this->year=htmlspecialchars(strip_tags($this->year));
        $this->marks=htmlspecialchars(strip_tags($this->marks));
        $this->grade=htmlspecialchars(strip_tags($this->grade));
        $this->remarks=htmlspecialchars(strip_tags($this->remarks));
        $this->is_ple=($this->is_ple ? 1 : 0); // Convert boolean to int for DB

        // Bind values
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":subject_id", $this->subject_id);
        $stmt->bindParam(":class_id", $this->class_id);
        $stmt->bindParam(":term", $this->term);
        $stmt->bindParam(":year", $this->year);
        $stmt->bindParam(":marks", $this->marks);
        $stmt->bindParam(":grade", $this->grade);
        $stmt->bindParam(":remarks", $this->remarks);
        $stmt->bindParam(":is_ple", $this->is_ple);

        try {
            if($stmt->execute()){
                return true;
            }
        } catch (PDOException $e) {
             error_log("Error creating exam result: " . $e->getMessage());
             // Check for duplicate entry errors specifically if needed
        }
    
        return false;
    }

    // Add other methods as needed
}
?> 