<?php
session_start();

// Configuration data
$appTypes = [
    'web' => ['name' => 'Web Application'],
    'api' => ['name' => 'API Service'],
    'microservice' => ['name' => 'Microservice'],
    'batch' => ['name' => 'Batch Processing'],
    'database' => ['name' => 'Database Service']
];

$platformTypes = ['Linux', 'Windows', 'Kubernetes', 'Serverless'];

$serviceTiers = [
    'basic' => [
        'slaTarget' => 95,
        'capacity' => 'Low',
        'performance' => 1,
        'color' => '#6c757d'
    ],
    'standard' => [
        'slaTarget' => 98,
        'capacity' => 'Medium',
        'performance' => 2,
        'color' => '#28a745'
    ],
    'premium' => [
        'slaTarget' => 99,
        'capacity' => 'High',
        'performance' => 3,
        'color' => '#007bff'
    ],
    'enterprise' => [
        'slaTarget' => 99.9,
        'capacity' => 'Maximum',
        'performance' => 5,
        'color' => '#6f42c1'
    ]
];

// MySQL database connection
$conn = mysqli_connect("localhost:3307", "root", "", "paas_portal");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS paas_services (
    id VARCHAR(50) PRIMARY KEY,
    app_type VARCHAR(20) NOT NULL,
    platform VARCHAR(20) NOT NULL,
    tier VARCHAR(20) NOT NULL,
    instances INT NOT NULL,
    response_time INT NOT NULL,
    availability DECIMAL(5,2) NOT NULL,
    throughput INT NOT NULL,
    turnaround_time INT NOT NULL,
    burst_time INT NOT NULL,
    sla_compliance DECIMAL(8,2) NOT NULL,
    performance_index INT NOT NULL,
    status VARCHAR(20) DEFAULT 'running',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTable)) {
    die("Error creating table: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = 'service-' . uniqid();
    $appType = $_POST['appType'];
    $platform = $_POST['platform'];
    $tier = $_POST['tier'];
    $instances = (int)$_POST['instances'];
    
    // Generate random metrics
    $responseTime = rand(30, 200);
    $availability = rand(950, 1000) / 10;
    $throughput = rand(300, 1500);
    
    // Generate turnaround time and burst time for SLA compliance calculation
    $burstTime = rand(50, 150); // Time actually needed for processing
    $turnaroundTime = rand(100, 300); // Total time from request to completion
    
    // SLA Compliance = Turnaround Time - Burst Time (efficiency metric)
    $slaCompliance = $turnaroundTime - $burstTime;
    
    $performanceIndex = rand(70, 95);
    $status = 'running';
    
    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO paas_services 
                       (id, app_type, platform, tier, instances, response_time, 
                        availability, throughput, sla_compliance, performance_index, status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssiidiids", 
                  $id, $appType, $platform, $tier, $instances, 
                  $responseTime, $availability, $throughput, $slaCompliance, $performanceIndex, $status);
    
    if ($stmt->execute()) {
        $successMessage = "Service configuration saved successfully! ID: $id";
    } else {
        $errorMessage = "Error saving configuration: " . $conn->error;
    }
    
    $stmt->close();
}

// Fetch existing services
$services = [];
$result = $conn->query("SELECT * FROM paas_services ORDER BY created_at DESC");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaaS Configuration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* Base styles */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
        max-width: 1400px;
    }
    
    /* Card enhancements */
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: none;
        overflow: hidden;
    }
    
    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    
    /* Button styles */
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
        padding: 8px 20px;
        border-radius: 5px;
        font-weight: 500;
    }
    
    .btn-primary:hover {
        background-color: #3a5ec0;
        border-color: #3a5ec0;
    }
    
    /* Form styles */
    .form-control, .form-select {
        padding: 10px 15px;
        border-radius: 5px;
        border: 1px solid #d1d3e2;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    
    /* Table styles */
    .table {
        margin-bottom: 0;
    }
    
    .table th {
        background-color: #f8f9fc;
        color: #5a5c69;
        font-weight: 600;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .table td {
        vertical-align: middle;
        border-color: #e3e6f0;
    }
    
    .badge {
        padding: 5px 10px;
        font-weight: 500;
        border-radius: 4px;
    }
    
    /* Alert styles */
    .alert {
        border-radius: 5px;
        padding: 12px 20px;
    }
    
    /* Tier card enhancements */
    .tier-card {
        border-left: 4px solid;
        margin-bottom: 15px;
        transition: all 0.3s;
        border-radius: 8px;
        padding: 20px;
        background-color: white;
    }
    
    .tier-card h4 {
        color: #5a5c69;
        margin-bottom: 15px;
    }
    
    .tier-card p {
        margin-bottom: 5px;
        color: #6e707e;
    }
    
    .tier-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .tier-basic { border-color: #6c757d; }
    .tier-standard { border-color: #28a745; }
    .tier-premium { border-color: #007bff; }
    .tier-enterprise { border-color: #6f42c1; }
    
    /* Header styles */
    h1 {
        color:rgb(232, 233, 237);
        font-weight: 600;
        margin-bottom: 1.5rem;
    }
    
    h3 {
        color: white;
        font-weight: 600;
        font-size: 1.5rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }
        
        .table-responsive {
            border: none;
        }
    }
    
    /* Animation for success message */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-success {
        animation: fadeIn 0.3s ease-out;
    }
    
    /* Status badges */
    .bg-success {
        background-color: #1cc88a !important;
    }
    
    .bg-danger {
        background-color: #e74a3b !important;
    }
    
    /* Hover effect for table rows */
    tbody tr {
        transition: background-color 0.2s;
    }
    
    tbody tr:hover {
        background-color: #f8f9fc;
    }
    
    /* SLA compliance styling */
    .sla-good {
        color: #28a745;
        font-weight: bold;
    }
    
    .sla-average {
        color: #ffc107;
        font-weight: bold;
    }
    
    .sla-poor {
        color: #dc3545;
        font-weight: bold;
    }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">PaaS Service Configuration</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3>Configure New Service</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="appType" class="form-label">Application Type</label>
                                <select class="form-select" id="appType" name="appType" required>
                                    <option value="">Select application type</option>
                                    <?php foreach ($appTypes as $key => $type): ?>
                                        <option value="<?= $key ?>"><?= $type['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="platform" class="form-label">Platform Type</label>
                                <select class="form-select" id="platform" name="platform" required>
                                    <option value="">Select platform</option>
                                    <?php foreach ($platformTypes as $platform): ?>
                                        <option value="<?= $platform ?>"><?= $platform ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tier" class="form-label">Service Tier</label>
                                <select class="form-select" id="tier" name="tier" required>
                                    <option value="">Select service tier</option>
                                    <?php foreach ($serviceTiers as $key => $tier): ?>
                                        <option value="<?= $key ?>"><?= ucfirst($key) ?> (SLA: <?= $tier['slaTarget'] ?>ms)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="instances" class="form-label">Number of Instances</label>
                                <input type="number" class="form-control" id="instances" name="instances" 
                                       min="1" max="20" value="2" required>
                                <div class="form-text">Between 1 and 20 instances</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Configuration</button>
                            <button type="button" class="btn btn-primary" onclick="window.location.href='newpaasdashboard.php'">
                                Status check
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h3>Service Tiers Information</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($serviceTiers as $key => $tier): ?>
                            <div class="tier-card card p-3 mb-3 tier-<?= $key ?>">
                                <h4><?= ucfirst($key) ?> Tier</h4>
                                <p><strong>SLA Target:</strong> <?= $tier['slaTarget'] ?>ms</p>
                                <p><strong>Capacity:</strong> <?= $tier['capacity'] ?></p>
                                <p><strong>Performance:</strong> <?= $tier['performance'] ?>x</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h3>Configured Services</h3>
            </div>
            <div class="card-body">
                <?php if (empty($services)): ?>
                    <p>No services configured yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Platform</th>
                                    <th>Tier</th>
                                    <th>Instances</th>
                                    <th>Response Time</th>
                                    <th>Availability</th>
                                    <th>Turnaround Time</th>
                                    <th>Burst Time</th>
                                    <th>SLA Efficiency</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <?php 
                                    // Determine SLA efficiency class based on the difference
                                    $slaClass = 'sla-average';
                                    if ($service['sla_compliance'] <= 50) {
                                        $slaClass = 'sla-good'; // Lower difference = more efficient
                                    } elseif ($service['sla_compliance'] > 100) {
                                        $slaClass = 'sla-poor'; // Higher difference = less efficient
                                    }
                                    ?>
                                    <tr>
                                        <td><?= substr($service['id'], 0, 8) ?>...</td>
                                        <td><?= $appTypes[$service['app_type']]['name'] ?? $service['app_type'] ?></td>
                                        <td><?= $service['platform'] ?></td>
                                        <td>
                                            <span class="badge" style="background-color: <?= $serviceTiers[$service['tier']]['color'] ?? '#6c757d' ?>">
                                                <?= ucfirst($service['tier']) ?>
                                            </span>
                                        </td>
                                        <td><?= $service['instances'] ?></td>
                                        <td><?= $service['response_time'] ?>ms</td>
                                        <td><?= $service['availability'] ?>%</td>
                                        <td><?= $service['turnaround_time'] ?? 'N/A' ?>ms</td>
                                        <td><?= $service['burst_time'] ?? 'N/A' ?>ms</td>
                                        <td class="<?= $slaClass ?>"><?= $service['sla_compliance'] ?>ms</td>
                                        <td>
                                            <span class="badge bg-<?= $service['status'] === 'running' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($service['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-warning text-dark">
                <h4>SLA Efficiency Explanation</h4>
            </div>
            <div class="card-body">
                <p><strong>SLA Efficiency = Turnaround Time - Burst Time</strong></p>
                <ul>
                    <li><span class="sla-good">Green (≤50ms):</span> Excellent efficiency - minimal overhead</li>
                    <li><span class="sla-average">Yellow (51-100ms):</span> Good efficiency - acceptable overhead</li>
                    <li><span class="sla-poor">Red (>100ms):</span> Poor efficiency - high overhead</li>
                </ul>
                <p><em>Lower values indicate better service efficiency with less waiting time overhead.</em></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic form interactions
        document.getElementById('tier').addEventListener('change', function() {
            const tier = this.value;
            const instancesInput = document.getElementById('instances');
            
            // Adjust max instances based on tier
            if (tier === 'enterprise') {
                instancesInput.max = 20;
            } else if (tier === 'premium') {
                instancesInput.max = 15;
            } else if (tier === 'standard') {
                instancesInput.max = 10;
            } else {
                instancesInput.max = 5;
            }
            
            // Also adjust the current value if it's higher than the new max
            if (parseInt(instancesInput.value) > parseInt(instancesInput.max)) {
                instancesInput.value = instancesInput.max;
            }
        });
    </script>
</body>
</html>