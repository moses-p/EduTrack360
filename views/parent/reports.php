<?php
if (!isAuthorized('parent')) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get parent's children
$stmt = $conn->prepare("
    SELECT s.*, c.name as class_name 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.parent_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - EduTrack360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Student Reports</h2>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <select class="form-select" id="studentSelect">
                    <?php foreach ($children as $child): ?>
                        <option value="<?php echo $child['id']; ?>">
                            <?php echo $child['full_name'] . ' - ' . $child['class_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="termSelect">
                    <option value="1">Term 1</option>
                    <option value="2">Term 2</option>
                    <option value="3">Term 3</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" id="yearSelect" placeholder="Year">
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Performance Chart</h5>
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Summary</h5>
                        <div id="reportSummary">
                            <!-- Summary will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Detailed Report</h5>
                        <div id="detailedReport">
                            <!-- Detailed report will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        let performanceChart = null;

        function loadReport() {
            const studentId = document.getElementById('studentSelect').value;
            const term = document.getElementById('termSelect').value;
            const year = document.getElementById('yearSelect').value;

            fetch(`api/get_report.php?student_id=${studentId}&term=${term}&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    // Update summary
                    document.getElementById('reportSummary').innerHTML = `
                        <p><strong>Total Marks:</strong> ${data.total_marks}</p>
                        <p><strong>Average Marks:</strong> ${data.average_marks.toFixed(2)}</p>
                        <p><strong>Position:</strong> ${data.position}</p>
                        <p><strong>Remarks:</strong> ${data.remarks}</p>
                    `;

                    // Update detailed report
                    let detailedHtml = '<table class="table"><thead><tr><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';
                    data.results.forEach(result => {
                        detailedHtml += `
                            <tr>
                                <td>${result.subject_name}</td>
                                <td>${result.marks}</td>
                                <td>${result.grade}</td>
                            </tr>
                        `;
                    });
                    detailedHtml += '</tbody></table>';
                    document.getElementById('detailedReport').innerHTML = detailedHtml;

                    // Update chart
                    if (performanceChart) {
                        performanceChart.destroy();
                    }

                    const ctx = document.getElementById('performanceChart').getContext('2d');
                    performanceChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.results.map(r => r.subject_name),
                            datasets: [{
                                label: 'Marks',
                                data: data.results.map(r => r.marks),
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100
                                }
                            }
                        }
                    });
                });
        }

        // Load report when selections change
        document.getElementById('studentSelect').addEventListener('change', loadReport);
        document.getElementById('termSelect').addEventListener('change', loadReport);
        document.getElementById('yearSelect').addEventListener('change', loadReport);

        // Load initial report
        loadReport();
    </script>
</body>
</html> 