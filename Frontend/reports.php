<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Set default date range (today)
$date = $_POST['date'] ?? date('Y-m-d');
$report_type = $_POST['report_type'] ?? 'daily';

// Handle report generation
$report_data = [];
$summary_stats = [
    'total' => 0,
    'completed' => 0,
    'in_progress' => 0,
    'missed' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $date = $conn->real_escape_string($date);
    $report_type = $conn->real_escape_string($report_type);
    
    $query = "
        SELECT r.name as route_name, 
               a.status, 
               a.start_time, 
               a.end_time,
               u.name as cleaner_name
        FROM assignments a
        JOIN routes r ON a.route_id = r.id
        LEFT JOIN cleaners c ON a.cleaner_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
    ";
    
    if ($report_type === 'daily') {
        $query .= " WHERE DATE(a.assigned_at) = '$date'";
    } elseif ($report_type === 'weekly') {
        $query .= " WHERE YEARWEEK(a.assigned_at, 1) = YEARWEEK('$date', 1)";
    } elseif ($report_type === 'missed') {
        $query .= " WHERE a.status = 'missed' AND DATE(a.assigned_at) = '$date'";
    }
    
    $query .= " ORDER BY a.status, r.name";
    
    $report_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
    
    // Calculate summary stats
    $summary_stats['total'] = count($report_data);
    $summary_stats['completed'] = count(array_filter($report_data, fn($r) => $r['status'] === 'completed'));
    $summary_stats['in_progress'] = count(array_filter($report_data, fn($r) => $r['status'] === 'in-progress'));
    $summary_stats['missed'] = count(array_filter($report_data, fn($r) => $r['status'] === 'missed'));
}

// Save generated data to session for export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $_SESSION['export_data'] = $report_data;
    $_SESSION['summary_stats'] = $summary_stats;
    $_SESSION['report_type'] = $report_type;
    $_SESSION['report_date'] = $date;
}

// Handle export requests
if (isset($_GET['export']) && isset($_SESSION['export_data'])) {
    $export_type = $_GET['export'];
    $report_data = $_SESSION['export_data'];
    $summary_stats = $_SESSION['summary_stats'];
    $report_type = $_SESSION['report_type'];
    $date = $_SESSION['report_date'];

    $export_data = [];

    // Prepare data for export
    foreach ($report_data as $row) {
        $export_data[] = [
            'Route' => $row['route_name'],
            'Cleaner' => $row['cleaner_name'] ?? 'Unassigned',
            'Status' => ucfirst($row['status']),
            'Start Time' => $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '-',
            'End Time' => $row['end_time'] ? date('H:i', strtotime($row['end_time'])) : '-',
            'Date' => $date
        ];
    }

    if ($export_type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cleaning_report_' . $report_type . '_' . $date . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($export_data[0]));
        foreach ($export_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    } elseif ($export_type === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="cleaning_report_' . $report_type . '_' . $date . '.json"');
        echo json_encode([
            'metadata' => [
                'report_type' => $report_type,
                'report_date' => $date,
                'total_assignments' => $summary_stats['total'],
                'completed' => $summary_stats['completed'],
                'in_progress' => $summary_stats['in_progress'],
                'missed' => $summary_stats['missed']
            ],
            'data' => $export_data
        ], JSON_PRETTY_PRINT);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Smart Cleaning</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #225b25;
            --secondary-color: #17a2b8;
            --success-color: #28a745;
            --warning-color: #fd7e14;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .header h1 {
            margin: 0;
            color: var(--primary-color);
            font-size: 28px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card h2 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 22px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a471c;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #138496;
            transform: translateY(-1px);
        }
        
        .btn-export {
            background: #6f42c1;
            color: white;
        }
        
        .btn-export:hover {
            background: #5a32a8;
            transform: translateY(-1px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        table tr:hover td {
            background-color: #f8f9fa;
        }
        
        .status-completed {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-in-progress {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .status-pending {
            color: #6c757d;
            font-weight: 600;
        }
        
        .status-missed {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .summary-card {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-bottom: 25px;
            gap: 15px;
        }
        
        .summary-item {
            padding: 20px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            flex: 1;
            transition: transform 0.3s;
        }
        
        .summary-item:hover {
            transform: translateY(-3px);
        }
        
        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .report-type-btn {
            padding: 10px 20px;
            background: #e9ecef;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .report-type-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .report-type-btn:hover:not(.active) {
            background: #dee2e6;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .summary-card {
                flex-direction: column;
            }
            
            .report-type-selector {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Cleaning Reports</h1>
            <a href="supervisor_dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-file-alt"></i> Generate Report</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar-alt"></i> Select Date</label>
                    <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
                </div>
                
                <div class="report-type-selector">
                    <button type="button" class="report-type-btn <?= $report_type === 'daily' ? 'active' : '' ?>" 
                            onclick="setReportType('daily')">
                        <i class="fas fa-calendar-day"></i> Daily
                    </button>
                    <button type="button" class="report-type-btn <?= $report_type === 'weekly' ? 'active' : '' ?>" 
                            onclick="setReportType('weekly')">
                        <i class="fas fa-calendar-week"></i> Weekly
                    </button>
                    <button type="button" class="report-type-btn <?= $report_type === 'missed' ? 'active' : '' ?>" 
                            onclick="setReportType('missed')">
                        <i class="fas fa-exclamation-triangle"></i> Missed Areas
                    </button>
                    <input type="hidden" name="report_type" id="report_type" value="<?= htmlspecialchars($report_type) ?>">
                </div>
                
                <button type="submit" name="generate_report" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Generate Report
                </button>
                
                <?php if (!empty($report_data)): ?>
                    <div class="export-options">
                        <a href="?export=csv" class="btn btn-secondary">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="?export=json" class="btn btn-export">
                            <i class="fas fa-file-code"></i> Export JSON
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (!empty($report_data)): ?>
            <div class="summary-card">
                <div class="summary-item">
                    <div class="summary-value"><?= $summary_stats['total'] ?></div>
                    <div class="summary-label">Total Assignments</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $summary_stats['completed'] ?></div>
                    <div class="summary-label">Completed</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $summary_stats['in_progress'] ?></div>
                    <div class="summary-label">In Progress</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $summary_stats['missed'] ?></div>
                    <div class="summary-label">Missed</div>
                </div>
            </div>
            
            <div class="card">
                <h3><i class="fas fa-list"></i> <?= ucfirst($report_type) ?> Report for <?= date('F j, Y', strtotime($date)) ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Cleaner</th>
                            <th>Status</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['route_name']) ?></td>
                                <td><?= htmlspecialchars($row['cleaner_name'] ?? 'Unassigned') ?></td>
                                <td class="status-<?= str_replace('-', '_', $row['status']) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </td>
                                <td><?= $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '-' ?></td>
                                <td><?= $row['end_time'] ? date('H:i', strtotime($row['end_time'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="card">
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3>No Data Available</h3>
                    <p>No records found for the selected date and report type.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function setReportType(type) {
            document.getElementById('report_type').value = type;
            
            // Update active button
            document.querySelectorAll('.report-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>