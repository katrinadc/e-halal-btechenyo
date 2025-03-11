<?php
require_once __DIR__ . '/../classes/View.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Logger.php';

// Initialize classes
$view = View::getInstance();
$admin = Admin::getInstance();
$logger = AdminLogger::getInstance();

// Check if admin is logged in and is superadmin
if (!$admin->isLoggedIn() || !$admin->isAdmin()) {
    $_SESSION['error'] = 'You do not have permission to access this page.';
    header('Location: ../administrator');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>E-Halal Voting System | Manage Officers</title>
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
                Manage Officers
                <small>Add, Edit, Delete Election Officers</small>
            </h1>
            <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i> Admin Actions</a></li>
                <li class="active">Officers</li>
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
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm btn-flat">
                                <i class="fa fa-plus"></i> New Officer
                            </a>
                        </div>
                        <div class="box-body">
                            <table id="officerTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Role</th>
                                        <th>Created On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $officers = $admin->getAllAdmins();
                                    foreach($officers as $row){
                                        $actions = "";
                                        if($row['id'] != $_SESSION['admin']){
                                            $actions = "
                                                <button class='btn btn-success btn-sm edit btn-flat' data-id='".$row['id']."'>
                                                    <i class='fa fa-edit'></i> Edit
                                                </button>
                                                <button class='btn btn-danger btn-sm delete btn-flat' data-id='".$row['id']."'>
                                                    <i class='fa fa-trash'></i> Delete
                                                </button>
                                            ";
                                        }
                                        echo "
                                        <tr>
                                            <td>".$row['username']."</td>
                                            <td>".$row['firstname'].' '.$row['lastname']."</td>
                                            <td>".$row['gender']."</td>
                                            <td>".ucfirst($row['role'])."</td>
                                            <td>".date('M d, Y', strtotime($row['created_on']))."</td>
                                            <td>".$actions."</td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php echo $view->renderFooter(); ?>
</div>

<?php include 'includes/officers_modal.php'; ?>
<?php echo $view->renderScripts(); ?>

<script>
$(function() {
    $('#officerTable').DataTable({
        responsive: true,
        "order": [[ 4, "desc" ]]
    });

    // Initialize all tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Edit officer
    $(document).on('click', '.edit', function(e){
        e.preventDefault();
        $('#edit').modal('show');
        var id = $(this).data('id');
        getRow(id);
    });

    // Delete officer
    $(document).on('click', '.delete', function(e){
        e.preventDefault();
        $('#delete').modal('show');
        var id = $(this).data('id');
        getRow(id);
    });
});

function getRow(id){
    $.ajax({
        type: 'POST',
        url: 'includes/officers_row.php',
        data: {id:id},
        dataType: 'json',
        success: function(response){
            $('.admin_id').val(response.id);
            $('#edit_username').val(response.username);
            $('#edit_firstname').val(response.firstname);
            $('#edit_lastname').val(response.lastname);
            $('#edit_gender').val(response.gender);
            $('#edit_role').val(response.role);
            $('.fullname').html(response.firstname + ' ' + response.lastname);
        }
    });
}
</script>
</body>
</html> 