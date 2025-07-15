<?php
// Database connection configuration
$host = 'localhost:3307';
$dbname = 'paas_portal'; // Change this to your database name
$username = 'root'; // Change if needed
$password = ''; // Change if needed

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create table if it doesn't exist
try {
    $createTable = "CREATE TABLE IF NOT EXISTS paas_services (
        id VARCHAR(50) PRIMARY KEY,
        app_type VARCHAR(50) NOT NULL,
        platform VARCHAR(50) NOT NULL,
        tier VARCHAR(20) NOT NULL,
        instances INT NOT NULL,
        response_time INT NOT NULL,
        availability DECIMAL(5,2) NOT NULL,
        throughput INT NOT NULL,
        sla_compliance DECIMAL(5,2) NOT NULL,
        performance_index INT NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_scaled BIGINT DEFAULT 0,
        scaling_type VARCHAR(50) DEFAULT NULL
    )";
    
    $pdo->exec($createTable);
    
    // Add new columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE paas_services ADD COLUMN last_scaled BIGINT DEFAULT 0");
        $pdo->exec("ALTER TABLE paas_services ADD COLUMN scaling_type VARCHAR(50) DEFAULT NULL");
    } catch(PDOException $e) {
        // Columns might already exist, ignore the error
    }
    
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}

// Insert sample data if table is empty
try {
    $countQuery = $pdo->query("SELECT COUNT(*) FROM paas_services");
    if ($countQuery->fetchColumn() == 0) {
        $sampleData = [
            ['webapp-prod-01', 'webapp', 'Linux Container', 'standard', 3, 120, 99.2, 850, 99.1, 85],
            ['db-service-01', 'database', 'Kubernetes', 'premium', 2, 45, 99.8, 1200, 99.8, 92],
            ['api-gateway-01', 'apigateway', 'Serverless', 'enterprise', 5, 35, 99.9, 2100, 99.7, 95],
            ['microservice-auth', 'microservice', 'Linux Container', 'standard', 4, 85, 99.5, 650, 98.9, 88],
            ['webapp-dev-02', 'webapp', 'Windows Container', 'basic', 2, 180, 98.8, 420, 97.2, 78],
            ['db-analytics', 'database', 'Kubernetes', 'premium', 3, 65, 99.6, 980, 99.4, 90],
            ['microservice-payment', 'microservice', 'Linux Container', 'premium', 3, 75, 99.7, 750, 99.3, 91],
            ['webapp-staging-01', 'webapp', 'Docker', 'standard', 2, 150, 99.0, 600, 98.5, 82],
            ['api-auth-service', 'apigateway', 'Kubernetes', 'enterprise', 4, 40, 99.8, 1800, 99.6, 94],
            ['db-cache-redis', 'database', 'Linux Container', 'premium', 2, 25, 99.9, 1500, 99.8, 96]
        ];
        
        $insertQuery = "INSERT INTO paas_services (id, app_type, platform, tier, instances, response_time, availability, throughput, sla_compliance, performance_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertQuery);
        
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
    }
} catch(PDOException $e) {
    echo "Error inserting sample data: " . $e->getMessage();
}

// Fetch services data function
function getServicesData($pdo) {
    try {
        $query = "SELECT * FROM paas_services ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Update service function
function updateService($pdo, $serviceId, $updates) {
    try {
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['instances', 'response_time', 'availability', 'throughput', 'sla_compliance', 'performance_index', 'tier', 'status', 'last_scaled', 'scaling_type'];
        
        foreach ($updates as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateFields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($updateFields)) {
            $params[] = $serviceId;
            $query = "UPDATE paas_services SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($query);
            return $stmt->execute($params);
        }
        return false;
    } catch(PDOException $e) {
        error_log("Error updating service: " . $e->getMessage());
        return false;
    }
}

// Bulk update services function
function bulkUpdateServices($pdo, $servicesData) {
    try {
        $pdo->beginTransaction();
        
        foreach ($servicesData as $service) {
            $serviceId = $service['id'];
            unset($service['id']);
            updateService($pdo, $serviceId, $service);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        error_log("Error in bulk update: " . $e->getMessage());
        return false;
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_services':
            echo json_encode(getServicesData($pdo));
            exit;
            
        case 'update_service':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $serviceId = $_GET['id'] ?? null;
                
                if (!$serviceId) {
                    echo json_encode(['success' => false, 'error' => 'Service ID required']);
                    exit;
                }
                
                $success = updateService($pdo, $serviceId, $input);
                echo json_encode(['success' => $success]);
            }
            exit;
            
        case 'bulk_update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $success = bulkUpdateServices($pdo, $input);
                echo json_encode(['success' => $success]);
            }
            exit;
            
        case 'refresh_services':
            echo json_encode(['services' => getServicesData($pdo)]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
}

// Get services data for the page
$services = getServicesData($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaaS Hybrid Scaling Dashboard - Service-Level Auto-Scaling</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #667eea 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 8px;
            font-size: 20px;
        }
        
        .sla-metrics {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .sla-metric {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 5px solid #3498db;
        }
        
        .sla-metric.critical {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        }
        
        .sla-metric.warning {
            border-left-color: #f39c12;
            background: linear-gradient(135deg, #fffbf0 0%, #feebc8 100%);
        }
        
        .sla-metric.healthy {
            border-left-color: #27ae60;
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .metric-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 8px;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-badge.healthy { background: #d4edda; color: #155724; }
        .status-badge.warning { background: #fff3cd; color: #856404; }
        .status-badge.critical { background: #f8d7da; color: #721c24; }
        .status-badge.scaling { background: #cce5ff; color: #004085; }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .service-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .service-card.scaling-vertical {
            border-left-color: #f39c12;
            background: linear-gradient(135deg, #fffbf0 0%, #feebc8 100%);
        }
        
        .service-card.scaling-horizontal {
            border-left-color: #27ae60;
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
        }
        
        .service-card.sla-violation {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .service-name {
            font-weight: bold;
            font-size: 16px;
            color: #2c3e50;
        }
        
        .service-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .service-spec {
            background: rgba(255, 255, 255, 0.7);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
        }
        
        .service-spec strong {
            color: #2c3e50;
        }
        
        .performance-progress {
            margin-top: 15px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-fill.healthy { background: linear-gradient(90deg, #27ae60, #2ecc71); }
        .progress-fill.warning { background: linear-gradient(90deg, #f39c12, #e67e22); }
        .progress-fill.critical { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        
        .controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
        .btn-success { background: linear-gradient(135deg, #27ae60, #229954); color: white; }
        .btn-warning { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; }
        .btn-danger { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .logs {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: #ecf0f1;
            padding: 20px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            height: 250px;
            overflow-y: auto;
            margin-top: 15px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .app-type-badge {
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .app-type-badge.webapp { background: #17a2b8; }
        .app-type-badge.database { background: #6f42c1; }
        .app-type-badge.microservice { background: #20c997; }
        .app-type-badge.apigateway { background: #fd7e14; }
        
        .tier-badge {
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 8px;
        }
        
        .tier-badge.basic { background: #6c757d; }
        .tier-badge.standard { background: #28a745; }
        .tier-badge.premium { background: #007bff; }
        .tier-badge.enterprise { background: #6f42c1; }

        .service-fleet-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-app-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .add-app-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        .platform-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .platform-badge {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        /* Platform-specific colors */
        .platform-badge.aws { background: linear-gradient(135deg, #ff9900, #e68600); }
        .platform-badge.azure { background: linear-gradient(135deg, #0078d4, #106ebe); }
        .platform-badge.google-cloud { background: linear-gradient(135deg, #4285f4, #3367d6); }
        .platform-badge.kubernetes { background: linear-gradient(135deg, #326ce5, #1a73e8); }
        .platform-badge.docker { background: linear-gradient(135deg, #2496ed, #0db7ed); }
        .platform-badge.openshift { background: linear-gradient(135deg, #ee0000, #cc0000); }
        .platform-badge.heroku { background: linear-gradient(135deg, #430098, #6567a5); }
        .platform-badge.digitalocean { background: linear-gradient(135deg, #0080ff, #0066cc); }
        .platform-badge.linode { background: linear-gradient(135deg, #00b04f, #009639); }
        .platform-badge.vultr { background: linear-gradient(135deg, #007bfc, #0056b3); }
        .platform-badge.on-premise { background: linear-gradient(135deg, #6c757d, #5a6268); }
        .platform-badge.hybrid { background: linear-gradient(135deg, #17a2b8, #138496); }

        .platform-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 PaaS Hybrid Scaling Dashboard</h1>
            <p>Service-level auto-scaling based on application type, OS platform, service tier, and SLA performance metrics</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>📊 Service Performance Overview</h3>
                <div class="sla-metrics">
                    <div class="sla-metric healthy" id="slaOverall">
                        <div class="metric-value" id="overallSla">--</div>
                        <div class="metric-label">SLA Compliance</div>
                    </div>
                    <div class="sla-metric" id="responseTime">
                        <div class="metric-value" id="avgResponseTime">--</div>
                        <div class="metric-label">Avg Response Time</div>
                    </div>
                    <div class="sla-metric" id="throughput">
                        <div class="metric-value" id="currentThroughput">--</div>
                        <div class="metric-label">Requests/min</div>
                    </div>
                    <div class="sla-metric" id="availability">
                        <div class="metric-value" id="currentAvailability">--</div>
                        <div class="metric-label">Service Availability</div>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <strong>Platform Status:</strong> <span class="status-badge healthy" id="systemStatus">Loading...</span>
                    <strong style="margin-left: 20px;">Active Services:</strong> <span id="activeServices">--</span>
                </div>
                <div class="controls">
                    <button class="btn btn-primary" onclick="simulateTrafficSpike()">📈 Traffic Spike</button>
                    <button class="btn btn-warning" onclick="triggerServiceUpgrade()">⬆️ Service Upgrade</button>
                    <button class="btn btn-success" onclick="triggerServiceExpansion()">🔗 Service Expansion</button>
                    <button class="btn btn-danger" onclick="toggleAutoScaling()">🔄 Toggle Auto-Scale</button>
                    <button class="btn btn-primary" onclick="refreshServices()">🔄 Refresh Data</button>
                </div>
            </div>
            
            <div class="card">
                <h3>📈 Performance Metrics Trends</h3>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        
 <div class="card">
    <div class="service-fleet-header" style="display: flex; justify-content: center;">
        <a href="ccservices.php" class="add-app-btn">➕ Add Application</a>
    </div>
</div>
            </div>
            <div id="servicesError" class="error" style="display: none;"></div>
            <div class="service-grid" id="serviceGrid">
                <!-- Services will be loaded here -->
            </div>

        </div>
        
        <div class="card">
            <h3>📝 Scaling Activity Logs</h3>
            <div class="logs" id="logs">
                [SYSTEM] PaaS hybrid scaling platform initialized<br>
                [CONFIG] SLA targets: Response Time < 200ms, Availability > 99.9%<br>
                [CONFIG] Service tiers: Basic → Standard → Premium → Enterprise<br>
                [CONFIG] Service expansion: Min 2 instances, Max 20 instances per service<br>
            </div>
        </div>
    </div>

    <script>
        // Initialize with PHP data
        const phpServices = <?php echo json_encode($services); ?>;
        
        // PaaS scaling configuration
        const config = {
            sla: {
                responseTimeThreshold: 200, // ms
                availabilityThreshold: 99.9, // percentage
                throughputMin: 1000, // requests/min
                complianceThreshold: 95.0 // percentage
            },
            serviceTierUpgrade: {
                tiers: ['basic', 'standard', 'premium', 'enterprise'],
                cooldown: 30000 // 30 seconds
            },
            serviceExpansion: {
                minInstances: 2,
                maxInstances: 20,
                cooldown: 60000 // 60 seconds
            },
            autoScalingEnabled: true
        };

        // Application types and their service characteristics
        const appTypes = {
            webapp: { 
                name: 'Web Application', 
                baseCapacity: 500, 
                scalingFactor: 1.2,
                slaWeight: 1.1
            },
            database: { 
                name: 'Database Service', 
                baseCapacity: 800, 
                scalingFactor: 1.5,
                slaWeight: 1.4
            },
            microservice: { 
                name: 'Microservice', 
                baseCapacity: 300, 
                scalingFactor: 1.0,
                slaWeight: 1.0
            },
            apigateway: { 
                name: 'API Gateway', 
                baseCapacity: 1000, 
                scalingFactor: 1.3,
                slaWeight: 1.2
            }
        };

        // Convert PHP services data to JavaScript format
        let services = phpServices.map(service => ({
            id: service.id,
            appType: service.app_type,
            platform: service.platform,
            tier: service.tier,
            instances: parseInt(service.instances),
            responseTime: parseInt(service.response_time),
            availability: parseFloat(service.availability),
            throughput: parseInt(service.throughput),
            slaCompliance: parseFloat(service.sla_compliance),
            status: service.status,
            lastScaled: 0,
            scalingType: null,
            performanceIndex: parseInt(service.performance_index)
        }));

        let lastExpansionScale = 0;

        // Chart setup
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'SLA Compliance (%)',
                        data: [],
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Response Time (ms)',
                        data: [],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Service Availability (%)',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        },
                        min: 90,
                        max: 100
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        function log(message, type = 'INFO') {
            const logs = document.getElementById('logs');
            const timestamp = new Date().toLocaleTimeString();
            const typeColors = {
                'SYSTEM': '#3498db',
                'UPGRADE': '#f39c12',
                'EXPAND': '#27ae60',
                'SLA': '#e74c3c',
                'CONFIG': '#9b59b6',
                'INFO': '#95a5a6',
                'ERROR': '#e74c3c'
            };
            const color = typeColors[type] || '#95a5a6';
            logs.innerHTML += `<span style="color: ${color}">[${type}] ${timestamp}: ${message}</span><br>`;
            logs.scrollTop = logs.scrollHeight;
        }

        function calculateOverallMetrics() {
            if (services.length === 0) {
                return { totalThroughput: 0, avgResponseTime: 0, avgAvailability: 0, overallSla: 0 };
            }

            const totalThroughput = services.reduce((sum, service) => sum + service.throughput, 0);
            const avgResponseTime = Math.round(services.reduce((sum, service) => sum + service.responseTime, 0) / services.length);
            const avgAvailability = (services.reduce((sum, service) => sum + service.availability, 0) / services.length).toFixed(2);
            const overallSla = (services.reduce((sum, service) => sum + service.slaCompliance, 0) / services.length).toFixed(1);
            
            return { totalThroughput, avgResponseTime, avgAvailability, overallSla };
        }

        function updateDashboardMetrics() {
            const metrics = calculateOverallMetrics();
            
            document.getElementById('overallSla').textContent = metrics.overallSla + '%';
            document.getElementById('avgResponseTime').textContent = metrics.avgResponseTime + 'ms';
            document.getElementById('currentThroughput').textContent = metrics.totalThroughput.toLocaleString();
            document.getElementById('currentAvailability').textContent = metrics.avgAvailability + '%';
            document.getElementById('activeServices').textContent = services.length;

            // Update metric styling
            const slaElement = document.getElementById('slaOverall');
            const responseElement = document.getElementById('responseTime');
            const availabilityElement = document.getElementById('availability');
            
            if (metrics.overallSla < config.sla.complianceThreshold) {
                slaElement.className = 'sla-metric critical';
            } else if (metrics.overallSla < 98) {
                slaElement.className = 'sla-metric warning';
            } else {
                slaElement.className = 'sla-metric healthy';
            }

            if (metrics.avgResponseTime > config.sla.responseTimeThreshold) {
                responseElement.className = 'sla-metric critical';
            } else if (metrics.avgResponseTime > config.sla.responseTimeThreshold * 0.8) {
                responseElement.className = 'sla-metric warning';
            } else {
                responseElement.className = 'sla-metric healthy';
            }

            if (metrics.avgAvailability < config.sla.availabilityThreshold) {
                availabilityElement.className = 'sla-metric critical';
            } else if (metrics.avgAvailability < 99.5) {
                availabilityElement.className = 'sla-metric warning';
            } else {
                availabilityElement.className = 'sla-metric healthy';
            }

            // Update system status
            const statusElement = document.getElementById('systemStatus');
            if (metrics.overallSla < config.sla.complianceThreshold || metrics.avgResponseTime > config.sla.responseTimeThreshold) {
                statusElement.textContent = 'SLA Alert';
                statusElement.className = 'status-badge critical';
            } else if (metrics.overallSla < 98 || metrics.avgResponseTime > config.sla.responseTimeThreshold * 0.8) {
                statusElement.textContent = 'Performance Warning';
                statusElement.className = 'status-badge warning';
            } else {
                statusElement.textContent = 'Optimal';
                statusElement.className = 'status-badge healthy';
            }

            // Update chart
            const now = new Date().toLocaleTimeString().slice(0, 5);
            chart.data.labels.push(now);
            chart.data.datasets[0].data.push(parseFloat(metrics.overallSla));
            chart.data.datasets[1].data.push(metrics.avgResponseTime);
            chart.data.datasets[2].data.push(parseFloat(metrics.avgAvailability));
            
            if (chart.data.labels.length > 15) {
                chart.data.labels.shift();
                chart.data.datasets.forEach(dataset => dataset.data.shift());
            }
            
            chart.update('none');
        }


function updateServiceDisplay() {
    const grid = document.getElementById('serviceGrid');
    grid.innerHTML = '';
    
    services.forEach(service => {
        const div = document.createElement('div');
        let serviceClass = 'service-card';
        
        if (service.scalingType === 'tier-upgrade') serviceClass += ' scaling-vertical';
        else if (service.scalingType === 'instance-expansion') serviceClass += ' scaling-horizontal';
        else if (service.slaCompliance < config.sla.complianceThreshold) serviceClass += ' sla-violation';
        
        div.className = serviceClass;
        div.innerHTML = `
            ${service.scalingType ? '<div class="scaling-animation"></div>' : ''}
            <div class="service-header">
                <div class="service-name">${service.id}</div>
                <div>
                    <span class="app-type-badge ${service.appType}">${appTypes[service.appType].name}</span>
                    <span class="tier-badge ${service.tier}">${service.tier.toUpperCase()}</span>
                </div>
            </div>
            <div class="platform-container">
                <div class="platform-badge ${service.platform.toLowerCase().replace(/\s+/g, '-')}">${service.platform}</div>
            </div>
            <div class="service-specs">
                <div class="service-spec"><strong>Instances:</strong> ${service.instances}</div>
                <div class="service-spec"><strong>Response:</strong> ${service.responseTime}ms</div>
                <div class="service-spec"><strong>Availability:</strong> ${service.availability}%</div>
                <div class="service-spec"><strong>Throughput:</strong> ${service.throughput}/min</div>
                <div class="service-spec"><strong>Performance:</strong> ${service.performanceIndex}/100</div>
            </div>
            <div class="performance-progress">
                <div class="progress-label">
                    <span>SLA Compliance</span>
                    <span>${service.slaCompliance}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill ${service.slaCompliance >= 98 ? 'healthy' : service.slaCompliance >= 95 ? 'warning' : 'critical'}" 
                         style="width: ${service.slaCompliance}%"></div>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 12px; color: #7f8c8d;">
                Status: ${service.status}
                ${service.scalingType ? ` | Scaling: ${service.scalingType}` : ''}
            </div>
        `;
        grid.appendChild(div);
    });
}
        function performServiceTierUpgrade(service) {
            if (Date.now() - service.lastScaled < config.serviceTierUpgrade.cooldown) return false;
            
            const currentTierIndex = config.serviceTierUpgrade.tiers.indexOf(service.tier);
            if (currentTierIndex >= config.serviceTierUpgrade.tiers.length - 1) return false;
            
            const newTier = config.serviceTierUpgrade.tiers[currentTierIndex + 1];
            const oldTier = service.tier;
            
            service.tier = newTier;
            service.lastScaled = Date.now();
            service.scalingType = 'tier-upgrade';
            
            log(`Service tier upgrade: ${service.id} upgraded from ${oldTier} to ${newTier} tier`, 'UPGRADE');
            
            // Simulate tier upgrade benefits
            setTimeout(() => {
                const tierConfig = serviceTiers[newTier];
                service.responseTime = Math.max(30, Math.floor(service.responseTime * 0.7));
                service.availability = Math.min(99.99, Number((service.availability + 0.5).toFixed(2)));
                service.slaCompliance = Math.min(99.9, Number((service.slaCompliance + 2).toFixed(1)));
                service.performanceIndex = Math.min(100, service.performanceIndex + 15);
                service.throughput = Math.floor(service.throughput * tierConfig.performance);
                service.scalingType = null;
                log(`Service tier upgrade completed for ${service.id} - Enhanced ${newTier} tier active`, 'UPGRADE');
            }, 4000);
            
            return true;
        }

        function performServiceInstanceExpansion() {
            if (Date.now() - lastExpansionScale < config.serviceExpansion.cooldown) return false;
            
            // Find service that needs instance expansion
            const serviceNeedingExpansion = services.find(s => 
                s.instances < config.serviceExpansion.maxInstances && 
                (s.responseTime > config.sla.responseTimeThreshold * 0.8 || 
                 s.slaCompliance < 98 || 
                 s.throughput < appTypes[s.appType].baseCapacity)
            );
            
            if (!serviceNeedingExpansion) return false;
            
            const additionalInstances = Math.min(
                Math.ceil(serviceNeedingExpansion.instances * 0.5), 
                config.serviceExpansion.maxInstances - serviceNeedingExpansion.instances
            );
            
            serviceNeedingExpansion.instances += additionalInstances;
            serviceNeedingExpansion.scalingType = 'instance-expansion';
            lastExpansionScale = Date.now();
            
            log(`Service instance expansion: ${serviceNeedingExpansion.id} scaled from ${serviceNeedingExpansion.instances - additionalInstances} to ${serviceNeedingExpansion.instances} instances`, 'EXPAND');
            
            // Simulate expansion benefits
            setTimeout(() => {
                const scalingFactor = 1 + (additionalInstances * 0.1);
                serviceNeedingExpansion.responseTime = Math.max(30, Math.floor(serviceNeedingExpansion.responseTime / scalingFactor));
                serviceNeedingExpansion.throughput = Math.floor(serviceNeedingExpansion.throughput * scalingFactor);
                serviceNeedingExpansion.availability = Math.min(99.99, Number((serviceNeedingExpansion.availability + 0.2).toFixed(2)));
                serviceNeedingExpansion.slaCompliance = Math.min(99.9, Number((serviceNeedingExpansion.slaCompliance + 1).toFixed(1)));
                serviceNeedingExpansion.performanceIndex = Math.min(100, serviceNeedingExpansion.performanceIndex + 10);
                serviceNeedingExpansion.scalingType = null;
                log(`Service expansion completed for ${serviceNeedingExpansion.id} - Enhanced capacity active`, 'EXPAND');
            }, 3000);
            
            return true;
        }

        function monitorSLACompliance() {
            services.forEach(service => {
                if (service.slaCompliance < config.sla.complianceThreshold) {
                    log(`SLA violation detected: ${service.id} compliance at ${service.slaCompliance}%`, 'SLA');
                    
                    if (config.autoScalingEnabled) {
                        // Try tier upgrade first
                        if (!performServiceTierUpgrade(service)) {
                            // If tier upgrade not possible, try instance expansion
                            performServiceInstanceExpansion();
                        }
                    }
                }
            });
        }

        function simulateTrafficSpike() {
            log('Simulating traffic spike across all services', 'SYSTEM');
            
            services.forEach(service => {
                // Increase load metrics
                service.responseTime = Math.floor(service.responseTime * (1.3 + Math.random() * 0.5));
                service.throughput = Math.floor(service.throughput * (1.5 + Math.random() * 0.3));
                service.availability = Math.max(95, service.availability - (Math.random() * 2));
                service.slaCompliance = Math.max(90, service.slaCompliance - (Math.random() * 5));
                service.performanceIndex = Math.max(60, service.performanceIndex - (Math.random() * 15));
            });
            
            updateDashboardMetrics();
            updateServiceDisplay();
            
            // Auto-scale if enabled
            if (config.autoScalingEnabled) {
                setTimeout(() => {
                    log('Auto-scaling triggered due to traffic spike', 'SYSTEM');
                    monitorSLACompliance();
                }, 2000);
            }
        }

        function triggerServiceUpgrade() {
            const servicesToUpgrade = services.filter(s => 
                s.tier !== 'enterprise' && 
                (s.slaCompliance < 98 || s.responseTime > config.sla.responseTimeThreshold * 0.7)
            );
            
            if (servicesToUpgrade.length === 0) {
                log('No services require tier upgrades at this time', 'UPGRADE');
                return;
            }
            
            const serviceToUpgrade = servicesToUpgrade[Math.floor(Math.random() * servicesToUpgrade.length)];
            if (performServiceTierUpgrade(serviceToUpgrade)) {
                updateServiceDisplay();
            }
        }

        function triggerServiceExpansion() {
            if (performServiceInstanceExpansion()) {
                updateServiceDisplay();
            } else {
                log('No services require instance expansion at this time', 'EXPAND');
            }
        }

        function toggleAutoScaling() {
            config.autoScalingEnabled = !config.autoScalingEnabled;
            const status = config.autoScalingEnabled ? 'ENABLED' : 'DISABLED';
            log(`Auto-scaling has been ${status}`, 'CONFIG');
            
            const button = event.target;
            button.textContent = config.autoScalingEnabled ? '🔄 Disable Auto-Scale' : '🔄 Enable Auto-Scale';
            button.className = config.autoScalingEnabled ? 'btn btn-danger' : 'btn btn-success';
        }

        function simulatePerformanceVariations() {
            services.forEach(service => {
                // Natural performance variations
                const variation = (Math.random() - 0.5) * 0.1;
                
                service.responseTime = Math.max(20, Math.floor(service.responseTime + (service.responseTime * variation)));
                service.availability = Math.min(99.99, Math.max(95, service.availability + variation));
                service.throughput = Math.max(100, Math.floor(service.throughput + (service.throughput * variation * 0.5)));
                service.slaCompliance = Math.min(99.9, Math.max(90, service.slaCompliance + (variation * 2)));
                service.performanceIndex = Math.min(100, Math.max(50, service.performanceIndex + (variation * 10)));
            });
        }

        function initializeDashboard() {
            updateDashboardMetrics();
            updateServiceDisplay();
            log('PaaS hybrid scaling dashboard initialized successfully', 'SYSTEM');
        }

        // Auto-monitoring and scaling loop
        setInterval(() => {
            simulatePerformanceVariations();
            
            if (config.autoScalingEnabled) {
                monitorSLACompliance();
            }
            
            updateDashboardMetrics();
            updateServiceDisplay();
        }, 5000);

        // Initialize dashboard on page load
        window.addEventListener('load', initializeDashboard);
    </script>
</body>
</html>