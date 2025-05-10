<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get books or borrowings
        try {
            $type = $_GET['type'] ?? 'books';
            
            if ($type === 'books') {
                // Get books
                $params = [];
                $where = [];
                
                // Filter by title
                if (isset($_GET['title'])) {
                    $where[] = "title LIKE ?";
                    $params[] = "%{$_GET['title']}%";
                }
                
                // Filter by author
                if (isset($_GET['author'])) {
                    $where[] = "author LIKE ?";
                    $params[] = "%{$_GET['author']}%";
                }
                
                // Filter by category
                if (isset($_GET['category'])) {
                    $where[] = "category = ?";
                    $params[] = $_GET['category'];
                }
                
                // Filter by availability
                if (isset($_GET['available']) && $_GET['available'] === 'true') {
                    $where[] = "available_quantity > 0";
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT * FROM library_books
                    $whereClause
                    ORDER BY title, author
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else if ($type === 'borrowings') {
                // Get borrowings
                $params = [];
                $where = [];
                
                // Filter by student
                if (isset($_GET['student_id'])) {
                    $where[] = "b.student_id = ?";
                    $params[] = $_GET['student_id'];
                }
                
                // Filter by status
                if (isset($_GET['status'])) {
                    $where[] = "b.status = ?";
                    $params[] = $_GET['status'];
                }
                
                // Filter by date range
                if (isset($_GET['start_date'])) {
                    $where[] = "b.borrowed_date >= ?";
                    $params[] = $_GET['start_date'];
                }
                if (isset($_GET['end_date'])) {
                    $where[] = "b.borrowed_date <= ?";
                    $params[] = $_GET['end_date'];
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $pdo->prepare("
                    SELECT b.*, 
                           bk.title, bk.author,
                           s.first_name as student_first_name,
                           s.last_name as student_last_name
                    FROM book_borrowings b
                    JOIN library_books bk ON b.book_id = bk.id
                    JOIN students s ON b.student_id = s.id
                    $whereClause
                    ORDER BY b.borrowed_date DESC
                ");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($type === 'summary') {
                // Get library summary
                $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
                
                // Get counts for different borrowing statuses
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed,
                        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue,
                        COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned
                    FROM book_borrowings
                    WHERE student_id = ?
                    AND borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ");
                $stmt->execute([$student_id]);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Ensure numeric values
                $summary['borrowed'] = (int)$summary['borrowed'];
                $summary['overdue'] = (int)$summary['overdue'];
                $summary['returned'] = (int)$summary['returned'];
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $summary]);
                
            } else if ($type === 'current') {
                // Get current borrowings
                $student_id = $_GET['student_id'] ?? $_SESSION['student_id'];
                
                $stmt = $pdo->prepare("
                    SELECT b.*, 
                           bk.title, bk.author
                    FROM book_borrowings b
                    JOIN library_books bk ON b.book_id = bk.id
                    WHERE b.student_id = ?
                    AND b.status IN ('borrowed', 'overdue')
                    ORDER BY b.due_date ASC
                ");
                $stmt->execute([$student_id]);
                $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $borrowings]);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $records]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Add new book or borrowing
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $type = $data['type'] ?? 'book';
            
            if ($type === 'book') {
                // Add new book
                $required_fields = ['title', 'author'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO library_books (
                        title, author, isbn, category,
                        quantity, available_quantity, location
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['title'],
                    $data['author'],
                    $data['isbn'] ?? null,
                    $data['category'] ?? null,
                    $data['quantity'] ?? 1,
                    $data['quantity'] ?? 1,
                    $data['location'] ?? null
                ]);
                
                $book_id = $pdo->lastInsertId();
                
                // Get the created book
                $stmt = $pdo->prepare("SELECT * FROM library_books WHERE id = ?");
                $stmt->execute([$book_id]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'borrowing') {
                // Add new borrowing
                $required_fields = ['book_id', 'student_id', 'due_date'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                
                // Check if book is available
                $stmt = $pdo->prepare("
                    SELECT available_quantity 
                    FROM library_books 
                    WHERE id = ?
                ");
                $stmt->execute([$data['book_id']]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$book || $book['available_quantity'] <= 0) {
                    throw new Exception('Book is not available for borrowing');
                }
                
                // Check if student already has this book
                $stmt = $pdo->prepare("
                    SELECT id 
                    FROM book_borrowings 
                    WHERE book_id = ? AND student_id = ? AND status = 'borrowed'
                ");
                $stmt->execute([$data['book_id'], $data['student_id']]);
                if ($stmt->fetch()) {
                    throw new Exception('Student already has this book');
                }
                
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Create borrowing record
                    $stmt = $pdo->prepare("
                        INSERT INTO book_borrowings (
                            book_id, student_id, borrowed_date,
                            due_date, status
                        ) VALUES (?, ?, CURDATE(), ?, 'borrowed')
                    ");
                    $stmt->execute([
                        $data['book_id'],
                        $data['student_id'],
                        $data['due_date']
                    ]);
                    
                    // Update book availability
                    $stmt = $pdo->prepare("
                        UPDATE library_books 
                        SET available_quantity = available_quantity - 1
                        WHERE id = ?
                    ");
                    $stmt->execute([$data['book_id']]);
                    
                    $pdo->commit();
                    
                    $borrowing_id = $pdo->lastInsertId();
                    
                    // Get the created borrowing
                    $stmt = $pdo->prepare("
                        SELECT b.*, 
                               bk.title, bk.author,
                               s.first_name as student_first_name,
                               s.last_name as student_last_name
                        FROM book_borrowings b
                        JOIN library_books bk ON b.book_id = bk.id
                        JOIN students s ON b.student_id = s.id
                        WHERE b.id = ?
                    ");
                    $stmt->execute([$borrowing_id]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' created successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update book or return borrowing
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $type = $data['type'] ?? 'book';
            
            if ($type === 'book') {
                // Update book
                if (!isset($data['id'])) {
                    throw new Exception('Book ID is required');
                }
                
                // Build update query
                $updates = [];
                $params = [];
                
                $fields = ['title', 'author', 'isbn', 'category', 'quantity', 'location'];
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $updates[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }
                
                if (empty($updates)) {
                    throw new Exception('No fields to update');
                }
                
                $params[] = $data['id'];
                
                $stmt = $pdo->prepare("
                    UPDATE library_books 
                    SET " . implode(", ", $updates) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
                
                // Get the updated book
                $stmt = $pdo->prepare("SELECT * FROM library_books WHERE id = ?");
                $stmt->execute([$data['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } else if ($type === 'return') {
                // Return book
                if (!isset($data['borrowing_id'])) {
                    throw new Exception('Borrowing ID is required');
                }
                
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Update borrowing record
                    $stmt = $pdo->prepare("
                        UPDATE book_borrowings 
                        SET status = 'returned',
                            return_date = CURDATE()
                        WHERE id = ? AND status = 'borrowed'
                    ");
                    $stmt->execute([$data['borrowing_id']]);
                    
                    if ($stmt->rowCount() === 0) {
                        throw new Exception('Borrowing record not found or already returned');
                    }
                    
                    // Get book ID
                    $stmt = $pdo->prepare("
                        SELECT book_id 
                        FROM book_borrowings 
                        WHERE id = ?
                    ");
                    $stmt->execute([$data['borrowing_id']]);
                    $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Update book availability
                    $stmt = $pdo->prepare("
                        UPDATE library_books 
                        SET available_quantity = available_quantity + 1
                        WHERE id = ?
                    ");
                    $stmt->execute([$borrowing['book_id']]);
                    
                    $pdo->commit();
                    
                    // Get the updated borrowing
                    $stmt = $pdo->prepare("
                        SELECT b.*, 
                               bk.title, bk.author,
                               s.first_name as student_first_name,
                               s.last_name as student_last_name
                        FROM book_borrowings b
                        JOIN library_books bk ON b.book_id = bk.id
                        JOIN students s ON b.student_id = s.id
                        WHERE b.id = ?
                    ");
                    $stmt->execute([$data['borrowing_id']]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' updated successfully',
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete book
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Book ID is required');
            }
            
            // Check if book has active borrowings
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM book_borrowings 
                WHERE book_id = ? AND status = 'borrowed'
            ");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete book with active borrowings');
            }
            
            $stmt = $pdo->prepare("DELETE FROM library_books WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?> 