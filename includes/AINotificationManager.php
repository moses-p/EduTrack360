<?php
require_once 'NotificationManager.php';

class AINotificationManager extends NotificationManager {
    private $openai;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->openai = new OpenAI([
            'api_key' => OPENAI_API_KEY
        ]);
    }
    
    public function analyzePaymentPatterns($student_id) {
        // Get student's payment history
        $stmt = $this->pdo->prepare("
            SELECT 
                payment_date,
                amount,
                payment_status,
                DATEDIFF(payment_date, due_date) as days_diff
            FROM financial_records
            WHERE student_id = ?
            ORDER BY payment_date DESC
            LIMIT 10
        ");
        $stmt->execute([$student_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($payments)) {
            return null;
        }
        
        // Analyze patterns
        $patterns = [
            'avg_days_before_due' => 0,
            'avg_days_after_due' => 0,
            'preferred_payment_day' => null,
            'payment_frequency' => null
        ];
        
        $total_before = 0;
        $total_after = 0;
        $count_before = 0;
        $count_after = 0;
        $payment_days = [];
        
        foreach ($payments as $payment) {
            if ($payment['days_diff'] < 0) {
                $total_before += abs($payment['days_diff']);
                $count_before++;
            } else {
                $total_after += $payment['days_diff'];
                $count_after++;
            }
            
            $payment_days[] = date('N', strtotime($payment['payment_date']));
        }
        
        if ($count_before > 0) {
            $patterns['avg_days_before_due'] = round($total_before / $count_before);
        }
        if ($count_after > 0) {
            $patterns['avg_days_after_due'] = round($total_after / $count_after);
        }
        
        // Find most common payment day
        $payment_days_count = array_count_values($payment_days);
        arsort($payment_days_count);
        $patterns['preferred_payment_day'] = array_key_first($payment_days_count);
        
        return $patterns;
    }
    
    public function generatePersonalizedMessage($student_id, $template, $variables) {
        // Get student's payment patterns
        $patterns = $this->analyzePaymentPatterns($student_id);
        
        // Get student's preferred language
        $stmt = $this->pdo->prepare("
            SELECT preferred_language
            FROM students
            WHERE id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generate personalized message using OpenAI
        $prompt = "Generate a personalized payment reminder message with the following context:\n";
        $prompt .= "Student's payment patterns:\n";
        if ($patterns) {
            $prompt .= "- Usually pays " . ($patterns['avg_days_before_due'] ? $patterns['avg_days_before_due'] . " days before due date" : "on time") . "\n";
            $prompt .= "- Preferred payment day: " . date('l', strtotime("next Monday +" . ($patterns['preferred_payment_day'] - 1) . " days")) . "\n";
        }
        $prompt .= "Template variables: " . json_encode($variables) . "\n";
        $prompt .= "Preferred language: " . ($student['preferred_language'] ?? 'English') . "\n";
        $prompt .= "Make the message friendly, clear, and easy to understand. Include payment suggestions if applicable.";
        
        $response = $this->openai->chat->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful school payment assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ]);
        
        return $response->choices[0]->message->content;
    }
    
    public function schedulePaymentReminders($financial_record_id) {
        // Get financial record details
        $stmt = $this->pdo->prepare("
            SELECT fr.*, s.preferred_language
            FROM financial_records fr
            JOIN students s ON fr.student_id = s.id
            WHERE fr.id = ?
        ");
        $stmt->execute([$financial_record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            throw new Exception('Financial record not found');
        }
        
        // Get notification settings
        $stmt = $this->pdo->prepare("SELECT * FROM notification_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get email templates
        $stmt = $this->pdo->prepare("SELECT * FROM email_templates");
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate reminder dates
        $reminder_dates = $this->calculateReminderDates($record['due_date'], $settings);
        
        // Schedule reminders
        foreach ($reminder_dates as $reminder_date) {
            $template = $this->selectTemplate($reminder_date, $record['due_date'], $templates);
            
            // Generate personalized message
            $variables = [
                'student_name' => $record['student_name'],
                'amount' => $record['amount'],
                'due_date' => $record['due_date'],
                'transaction_type' => $record['transaction_type'],
                'description' => $record['description'],
                'days_overdue' => max(0, strtotime($reminder_date) - strtotime($record['due_date'])) / (60 * 60 * 24)
            ];
            
            $personalized_message = $this->generatePersonalizedMessage($record['student_id'], $template, $variables);
            
            // Create notification
            $this->createNotification($record, $template, $reminder_date, $personalized_message);
        }
    }
    
    private function createNotification($record, $template, $scheduled_date, $personalized_message) {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_notifications (
                student_id,
                financial_record_id,
                template_id,
                status,
                scheduled_date,
                personalized_message
            ) VALUES (?, ?, ?, 'pending', ?, ?)
        ");
        
        $stmt->execute([
            $record['student_id'],
            $record['id'],
            $template['id'],
            $scheduled_date,
            $personalized_message
        ]);
    }
}
?> 