<?php
require_once __DIR__ . '/../classes/View.php';
require_once __DIR__ . '/../classes/Elections.php';
Elections::enforceCompletedRedirect();
Elections::enforceSetupRedirect();
require_once __DIR__ . '/../classes/Position.php';
require_once __DIR__ . '/../classes/Candidate.php';
require_once __DIR__ . '/../classes/Voter.php';
require_once __DIR__ . '/../classes/Vote.php';
require_once __DIR__ . '/../classes/Admin.php';

// Initialize classes
$view = View::getInstance();
$position = Position::getInstance();
$candidate = Candidate::getInstance();
$voter = Voter::getInstance();
$vote = Vote::getInstance();
$admin = Admin::getInstance();

// Check if admin is logged in
if (!$admin->isLoggedIn()) {
    header('Location: ../administrator');
    exit();
}

// Get statistics
$stats = [
    'positions' => $position->getPositionCount(),
    'candidates' => $candidate->getCandidateCount(),
    'total_voters' => $voter->getVoterCount(),
    'voted' => $voter->getVotersWhoVoted()
];

// Get all positions
$positions = $position->getAllPositions();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>E-Halal BTECHenyo | Admin Dashboard</title>
    <?php echo $view->renderHeader(); ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>administrator/assets/css/admin.css">
    <!-- Dashboard Specific Styles -->
    <style>
        /* Modern UI Styles for Dashboard */
        .small-box {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .small-box .icon {
            font-size: 70px;
            right: 20px;
            top: 20px;
            opacity: 0.3;
            transition: all 0.3s ease;
        }
        
        .small-box:hover .icon {
            font-size: 75px;
            opacity: 0.4;
        }
        
        .small-box .inner {
            padding: 20px;
        }
        
        .small-box h3 {
            font-size: 38px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            color: #fff;
        }
        
        .small-box p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
        }
        
        .small-box .small-box-footer {
            background: rgba(0, 0, 0, 0.1);
            color: rgba(255, 255, 255, 0.9);
            padding: 8px 0;
            transition: all 0.3s ease;
        }
        
        .small-box:hover .small-box-footer {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Chart Controls */
        .chart-controls {
            float: right;
        }
        
        .chart-controls select {
            border-radius: 20px;
            padding: 5px 15px;
            border: 1px solid #ddd;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .chart-controls select:hover {
            border-color: #aaa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .chart-controls select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 2px 10px rgba(76,175,80,0.2);
        }

        .chart-container canvas {
            max-height: 400px;
        }

        /* Empty State Styles */
        .empty-state-container {
            padding: 40px 20px;
            text-align: center;
        }

        .empty-state-container i.fa-4x {
            display: block;
            margin-bottom: 20px;
            color: #999;
        }

        .empty-state-container h3 {
            margin-bottom: 15px;
            font-size: 24px;
            color: #666;
        }

        .empty-state-container p {
            color: #888;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .empty-state-container ul {
            display: inline-block;
            text-align: left;
            margin-bottom: 25px;
        }

        .empty-state-container ul li {
            margin-bottom: 10px;
            color: #666;
        }

        .empty-state-container ul li i {
            margin-right: 10px;
        }

        .empty-state-container .btn-group {
            margin-top: 20px;
        }

        .empty-state-container .btn {
            margin: 0 5px;
            padding: 8px 20px;
            font-size: 14px;
        }

        .empty-state-container .btn i {
            margin-right: 8px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .empty-state-container {
                padding: 30px 15px;
            }
            
            .empty-state-container h3 {
                font-size: 20px;
            }
            
            .empty-state-container p {
                font-size: 14px;
            }

            .empty-state-container .btn-group {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .empty-state-container .btn {
                margin: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <?php 
    echo $view->renderNavbar();
    echo $view->renderMenubar();
    ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <h1>
                Dashboard
                <small>Control Panel</small>
            </h1>
            <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i>Reports</a></li>
                <li class="active">Dashboard</li>
            </ol>
        </section>

        <!-- Main content -->
        <section class="content">
        <?php
            if(isset($_SESSION['error'])){
                echo "
                    <div class='alert alert-danger alert-dismissible'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <h4><i class='icon fa fa-warning'></i> Error!</h4>
                        ".$_SESSION['error']."
                    </div>
                ";
                unset($_SESSION['error']);
            }
            if(isset($_SESSION['success'])){
                echo "
                    <div class='alert alert-success alert-dismissible'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <h4><i class='icon fa fa-check'></i> Success!</h4>
                        ".$_SESSION['success']."
                    </div>
                ";
                unset($_SESSION['success']);
            }
            ?>
            <!-- Modern Stats Cards -->
            <div class="row">
                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-gradient" style="background: linear-gradient(135deg, #6B8DD6 0%, #8E37D7 100%);">
                        <div class="inner" style="color: white;">
                            <h3 style="color: white;"><?php echo $stats['positions']; ?></h3>
                            <p style="color: white;">Positions</p>
                        </div>
                        <div class="icon">
                            <i class="fa fa-list-alt"></i>
                        </div>
                        <a href="positions" class="small-box-footer" style="color: white;">
                            More info <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-gradient" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <div class="inner" style="color: white;">
                            <h3 style="color: white;"><?php echo $stats['candidates']; ?></h3>
                            <p style="color: white;">Candidates</p>
                        </div>
                        <div class="icon">
                            <i class="fa fa-user"></i>
                        </div>
                        <a href="candidates" class="small-box-footer" style="color: white;">
                            More info <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-gradient" style="background: linear-gradient(135deg, #FFB74D 0%, #FF9800 100%);">
                        <div class="inner" style="color: white;">
                            <h3 style="color: white;"><?php echo $stats['total_voters']; ?></h3>
                            <p style="color: white;">Total Voters</p>
                        </div>
                        <div class="icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <a href="voters" class="small-box-footer" style="color: white;">
                            More info <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-xs-6">
                    <div class="small-box bg-gradient" style="background: linear-gradient(135deg, #EF5350 0%, #D32F2F 100%);">
                        <div class="inner" style="color: white;">
                            <h3 style="color: white;"><?php echo $stats['voted']; ?></h3>
                            <p style="color: white;">Voters Voted</p>
                        </div>
                        <div class="icon">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <a href="votes" class="small-box-footer" style="color: white;">
                            More info <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Position Charts -->
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="box-title">Vote Distribution by Position</h3>
                                <div class="chart-controls">
                                    <select id="chartTypeSelector" class="form-control" style="width: 200px; display: inline-block;">
                                        <option value="bar">Bar Chart</option>
                                        <option value="line">Line Graph</option>
                                        <option value="pie">Pie Chart</option>
                                        <option value="doughnut">Doughnut Chart</option>
                                        <option value="polarArea">Polar Area</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-body">
                            <?php 
                            $chartData = $view->getChartData();
                            ?>
                            <div id="chartsContainer" class="row">
                                <div class="col-md-6">
                                    <canvas id="positionParticipationChart"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <canvas id="partylistChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart.js -->
            <script src="<?php echo BASE_URL; ?>node_modules/chart.js/dist/chart.umd.js"></script>

            <script>
                // Global variables for votes.js
                window.BASE_URL = '<?php echo BASE_URL; ?>';
                
                // Prepare data for partylist chart
                window.partylistData = <?php 
                    $partylistVotes = $vote->getVotesByPartylist();
                    error_log("Partylist votes data: " . json_encode($partylistVotes));
                    $partylistChartData = [
                        'labels' => array_column($partylistVotes, 'partylist_name'),
                        'data' => array_column($partylistVotes, 'total_votes')
                    ];
                    echo json_encode($partylistChartData); 
                ?>;

                // Prepare data for position participation chart
                window.positionData = <?php 
                    $positionData = [];
                    if (!empty($positions)) {
                        foreach ($positions as $pos) {
                            // Get candidates and their votes for this position
                            $candidates = $vote->getVotesByPosition($pos['id']);
                            
                            // Calculate total votes for this position
                            $totalVotes = array_sum(array_column($candidates, 'votes'));
                            
                            // Store position data
                            $positionData[] = [
                                'position' => $pos['description'],
                                'votes' => $totalVotes,
                                'candidates' => array_map(function($c) {
                                    return [
                                        'name' => $c['firstname'] . ' ' . $c['lastname'],
                                        'votes' => (int)$c['votes']
                                    ];
                                }, $candidates)
                            ];
                            error_log("Position {$pos['description']}: {$totalVotes} votes");
                            error_log("Candidates data: " . json_encode($candidates));
                        }
                    }
                    
                    $chartData = [
                        'labels' => array_column($positionData, 'position'),
                        'data' => array_column($positionData, 'votes'),
                        'candidateData' => array_map(function($pos) {
                            return [
                                'position' => $pos['position'],
                                'candidates' => $pos['candidates']
                            ];
                        }, $positionData)
                    ];
                    error_log("Position chart data: " . json_encode($chartData));
                    echo json_encode($chartData);
                ?>;

                // Debug data
                console.log('Position Data:', window.positionData);
                console.log('Partylist Data:', window.partylistData);
            </script>

            <!-- Custom scripts -->
            <script src="<?php echo BASE_URL; ?>administrator/pages/includes/scripts/votes.js"></script>

            <script>
                // Initialize charts with the updated data
                document.addEventListener('DOMContentLoaded', function() {
                    const chartTypeSelector = document.getElementById('chartTypeSelector');
                    const chartsContainer = document.getElementById('chartsContainer');

                    if (!window.positionData || !window.positionData.labels) {
                        // Handle no data scenario
                        chartsContainer.innerHTML = `
                            <div class="col-xs-12">
                                <div class="box box-default">
                                    <div class="box-body text-center">
                                        <div class="empty-state-container">
                                            <i class="fa fa-chart-bar fa-4x text-muted mb-3"></i>
                                            <h3 class="text-muted">No Voting Data Available</h3>
                                            <p class="text-muted mb-4">
                                                There are no votes recorded yet. Data will appear here once:
                                            </p>
                                            <ul class="list-unstyled text-muted" style="margin-bottom: 20px;">
                                                <li><i class="fa fa-check-circle text-success"></i> Positions are added</li>
                                                <li><i class="fa fa-check-circle text-success"></i> Candidates are assigned</li>
                                                <li><i class="fa fa-check-circle text-success"></i> Voters cast their votes</li>
                                            </ul>
                                            <div class="empty-state-actions">
                                                <a href="positions" class="btn btn-primary custom">
                                                    <i class="fa fa-list-alt mr-2"></i> Manage Positions
                                                </a>
                                                <a href="candidates" class="btn btn-success custom">
                                                    <i class="fa fa-user mr-2"></i> Manage Candidates
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                        return;
                    }

                    function renderCharts(type) {
                        chartsContainer.innerHTML = '';

                        // Create position chart
                        const positionChartDiv = document.createElement('div');
                        positionChartDiv.className = 'col-md-12';
                        positionChartDiv.style.marginBottom = '20px';
                        const positionCanvas = document.createElement('canvas');
                        positionChartDiv.appendChild(positionCanvas);
                        chartsContainer.appendChild(positionChartDiv);

                        new Chart(positionCanvas, {
                            type: type,
                            data: {
                                labels: window.positionData.labels,
                                datasets: [{
                                    label: 'Total Votes Cast',
                                    data: window.positionData.data,
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.7)',
                                        'rgba(54, 162, 235, 0.7)',
                                        'rgba(255, 206, 86, 0.7)',
                                        'rgba(75, 192, 192, 0.7)',
                                        'rgba(153, 102, 255, 0.7)'
                                    ],
                                    borderColor: [
                                        'rgb(255, 99, 132)',
                                        'rgb(54, 162, 235)',
                                        'rgb(255, 206, 86)',
                                        'rgb(75, 192, 192)',
                                        'rgb(153, 102, 255)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: type !== 'pie' && type !== 'doughnut' ? {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1
                                        }
                                    }
                                } : undefined,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Votes by Position',
                                        font: { size: 16 }
                                    }
                                }
                            }
                        });

                        // Create detailed charts for each position
                        window.positionData.candidateData.forEach((posData) => {
                            const detailChartDiv = document.createElement('div');
                            detailChartDiv.className = 'col-md-6';
                            detailChartDiv.style.marginBottom = '20px';
                            const detailCanvas = document.createElement('canvas');
                            detailChartDiv.appendChild(detailCanvas);
                            chartsContainer.appendChild(detailChartDiv);

                            new Chart(detailCanvas, {
                                type: type === 'line' ? 'bar' : type,
                                data: {
                                    labels: posData.candidates.map(c => c.name),
                                    datasets: [{
                                        label: posData.position + ' Candidates',
                                        data: posData.candidates.map(c => c.votes),
                                        backgroundColor: [
                                            'rgba(255, 99, 132, 0.7)',
                                            'rgba(54, 162, 235, 0.7)',
                                            'rgba(255, 206, 86, 0.7)',
                                            'rgba(75, 192, 192, 0.7)',
                                            'rgba(153, 102, 255, 0.7)',
                                            'rgba(255, 159, 64, 0.7)'
                                        ],
                                        borderColor: [
                                            'rgb(255, 99, 132)',
                                            'rgb(54, 162, 235)',
                                            'rgb(255, 206, 86)',
                                            'rgb(75, 192, 192)',
                                            'rgb(153, 102, 255)',
                                            'rgb(255, 159, 64)'
                                        ],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: type !== 'pie' && type !== 'doughnut' ? {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1
                                            }
                                        }
                                    } : undefined,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: posData.position + ' - Candidate Votes',
                                            font: { size: 16 }
                                        }
                                    }
                                }
                            });
                        });
                    }

                    // Initial render
                    renderCharts(chartTypeSelector.value);

                    // Handle chart type changes
                    chartTypeSelector.addEventListener('change', function() {
                        renderCharts(this.value);
                    });
                });
            </script>
        </section>
    </div>
    <!-- /.content-wrapper -->
    <?php echo $view->renderFooter(); ?>
</div>
<!-- ./wrapper -->

<?php echo $view->renderScripts(); ?>
</body>
</html>