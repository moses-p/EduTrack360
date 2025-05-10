<?php
// Set page title
$page_title = "Payment Dashboard";

// Generate content
$content = <<<HTML
<div class="row">
    <div class="col-md-8 mx-auto">
        <!-- Payment Summary Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Payment Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h3 class="text-primary" id="totalDue">$0.00</h3>
                        <p class="text-muted">Total Due</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-success" id="totalPaid">$0.00</h3>
                        <p class="text-muted">Total Paid</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-info" id="paymentPercentage">0%</h3>
                        <p class="text-muted">Payment Progress</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Payment Assistant -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Payment Assistant</h5>
            </div>
            <div class="card-body">
                <div id="aiSuggestions" class="mb-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-robot me-2"></i>
                    Our AI assistant analyzes your payment patterns to provide personalized suggestions.
                </div>
            </div>
        </div>

        <!-- Upcoming Payments -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="upcomingPaymentsTable">
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="paymentHistoryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load payment data
    loadPaymentData();
    
    // Set up auto-refresh every 5 minutes
    setInterval(loadPaymentData, 300000);
});

function loadPaymentData() {
    // Load payment summary
    fetch('api/get_payment_summary.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalDue').textContent = formatCurrency(data.total_due);
            document.getElementById('totalPaid').textContent = formatCurrency(data.total_paid);
            document.getElementById('paymentPercentage').textContent = data.payment_percentage + '%';
            
            // Update progress bar color based on percentage
            const progressBar = document.getElementById('paymentPercentage');
            if (data.payment_percentage >= 90) {
                progressBar.className = 'text-success';
            } else if (data.payment_percentage >= 50) {
                progressBar.className = 'text-warning';
            } else {
                progressBar.className = 'text-danger';
            }
        })
        .catch(error => console.error('Error loading payment summary:', error));
    
    // Load AI suggestions
    fetch('api/get_payment_suggestions.php')
        .then(response => response.json())
        .then(data => {
            const suggestionsDiv = document.getElementById('aiSuggestions');
            suggestionsDiv.innerHTML = data.suggestions.map(suggestion => `
                <div class="alert alert-light border">
                    <i class="fas fa-lightbulb text-warning me-2"></i>
                    ${suggestion}
                </div>
            `).join('');
        })
        .catch(error => console.error('Error loading AI suggestions:', error));
    
    // Load upcoming payments
    fetch('api/get_upcoming_payments.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#upcomingPaymentsTable tbody');
            tbody.innerHTML = data.payments.map(payment => `
                <tr>
                    <td>${formatDate(payment.due_date)}</td>
                    <td>${payment.description}</td>
                    <td>${formatCurrency(payment.amount)}</td>
                    <td>
                        <span class="badge bg-${getStatusColor(payment.status)}">
                            ${payment.status}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="viewPaymentDetails(${payment.id})">
                            View Details
                        </button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => console.error('Error loading upcoming payments:', error));
    
    // Load payment history
    fetch('api/get_payment_history.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#paymentHistoryTable tbody');
            tbody.innerHTML = data.payments.map(payment => `
                <tr>
                    <td>${formatDate(payment.payment_date)}</td>
                    <td>${payment.description}</td>
                    <td>${formatCurrency(payment.amount)}</td>
                    <td>
                        <span class="badge bg-${getStatusColor(payment.status)}">
                            ${payment.status}
                        </span>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => console.error('Error loading payment history:', error));
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getStatusColor(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warning';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

function viewPaymentDetails(paymentId) {
    // Implement payment details view
    window.location.href = `payment_details.php?id=${paymentId}`;
}
</script>
HTML;
?> 