<?php
require_once __DIR__ . '/../classes/View.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Logger.php';

// Initialize classes
$view = View::getInstance();
$admin = Admin::getInstance();
$logger = AdminLogger::getInstance();

// Check if admin is logged in
if (!$admin->isLoggedIn()) {
    header('Location: ../administrator');
    exit();
}

// Handle log clearing if requested
if (isset($_POST['clear_logs']) && $admin->isAdmin()) {
    $type = $_POST['log_type'] ?? 'all';
    $logger->clearLogs($type);
    $_SESSION['success'] = 'Logs cleared successfully';
    header('Location: logs.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>E-Halal Voting System | System Logs</title>
    <?php echo $view->renderHeader(); ?>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <?php 
    echo $view->renderNavbar();
    echo $view->renderMenubar();
    ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <h1>
                System Logs
                <small>View all system activities</small>
            </h1>
            <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i> Admin Actions</a></li>
                <li class="active">Logs</li>
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

            <div class="row">
                <!-- Admin Logs -->
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Administrator Logs</h3>
                            <?php if ($admin->isAdmin()): ?>
                            <div class="pull-right">
                                <form method="POST" class="form-inline" style="display: inline;">
                                    <input type="hidden" name="log_type" value="admin">
                                    <button type="submit" name="clear_logs" class="btn btn-danger btn-sm btn-flat" onclick="return confirm('Are you sure you want to clear admin logs?');">
                                        <i class="fa fa-trash"></i> Clear Admin Logs
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Admin ID</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $admin_logs = $logger->getAdminLogs();
                                        if (empty($admin_logs)) {
                                            echo "<tr><td colspan='4' class='text-center'>No logs found</td></tr>";
                                        } else {
                                            foreach ($admin_logs as $log) {
                                                if (preg_match('/\[(.*?)\] Admin ID: (.*?) \| Action: (.*?)(?:\| Details: (.*?))?$/', $log, $matches)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($matches[1]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($matches[2]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($matches[3]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($matches[4] ?? '') . "</td>";
                                                    echo "</tr>";
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voter Logs -->
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Voter Logs</h3>
                            <?php if ($admin->isAdmin()): ?>
                            <div class="pull-right">
                                <form method="POST" class="form-inline" style="display: inline;">
                                    <input type="hidden" name="log_type" value="voters">
                                    <button type="submit" name="clear_logs" class="btn btn-danger btn-sm btn-flat" onclick="return confirm('Are you sure you want to clear voter logs?');">
                                        <i class="fa fa-trash"></i> Clear Voter Logs
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Voter ID</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $voter_logs = $logger->getVoterLogs();
                                        if (empty($voter_logs)) {
                                            echo "<tr><td colspan='4' class='text-center'>No logs found</td></tr>";
                                        } else {
                                            foreach ($voter_logs as $log) {
                                                if (preg_match('/\[(.*?)\] Voter ID: (.*?) \| Action: (.*?)(?:\| Details: (.*?))?$/', $log, $matches)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($matches[1]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($matches[2]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($matches[3]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($matches[4] ?? '') . "</td>";
                                                    echo "</tr>";
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php echo $view->renderFooter(); ?>
</div>

<?php echo $view->renderScripts(); ?>

<script>
$(function() {
    $('.table').DataTable({
        'order': [[0, 'desc']],
        'pageLength': 25,
        'columns': [
            { 'data': 'timestamp' },
            { 'data': 'id' },
            { 'data': 'action' },
            { 'data': 'details' }
        ],
        'autoWidth': false
    });
});
</script>
</body>
</html> 