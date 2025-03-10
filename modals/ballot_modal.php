<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ballot.php';
require_once __DIR__ . '/../classes/Votes.php';

$user = new User();
$ballot = new Ballot();
$votes = new Votes();
$currentVoter = $user->getCurrentUser();
?>

<!-- Preview -->
<div class="modal fade" id="preview_modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title text-center">Your Vote Preview</h4>
            </div>
            <div class="modal-body">
                <div id="preview_body" class="scrollable-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left" name="closePreview" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
                <button type="submit" form="ballotForm" class="btn btn-success btn-flat" name="submitPreview"><i class="fa fa-check"></i> Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- View Ballot -->
<div class="modal fade" id="view">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Your Ballot</h4>
            </div>
            <div class="modal-body">
                <div class="scrollable-content">
                    <?php
                    $voterVotes = $votes->getVoterVotes($currentVoter['id']);
                    $currentPosition = '';

                    foreach ($voterVotes as $vote) {
                        // Start new position section if position changes
                        if ($currentPosition !== $vote['position']) {
                            if ($currentPosition !== '') {
                                echo '</div>'; // Close previous position div
                            }
                            $currentPosition = $vote['position'];
                            echo '<div class="well">
                              <h4 class="position-heading">' . htmlspecialchars($vote['position']) . '</h4>';
                        }
                    ?>
                        <div class="row candidate-row">
                            <div class="col-sm-3 candidate-image-container">
                                <div class="circular-image">
                                    <img src="<?php echo !empty($vote['photo']) ? 'images/' . $vote['photo'] : 'images/profile.jpg'; ?>"
                                        class="img candidate-image">
                                </div>
                            </div>
                            <div class="col-sm-9">
                                <h4><?php echo htmlspecialchars($vote['firstname'] . ' ' . $vote['lastname']); ?></h4>
                                <?php if (!empty($vote['partylist'])): ?>
                                    <p><strong>Partylist:</strong> <?php echo htmlspecialchars($vote['partylist']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php
                    }
                    if ($currentPosition !== '') {
                        echo '</div>'; // Close last position div
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="close-ballot" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Modal styling */
    .modal-title{
        text-align: center;
        color: #218838;
        letter-spacing: 3px;
    }
    .modal-content {
        display: flex;
        flex-direction: column;
        height: 90vh;
        max-height: 90vh;
    }

    .modal-header {
        flex: 0 0 auto;
        background: #fff;
        border-bottom: 1px solid #e5e5e5;
        padding: 15px;
        position: relative;
        z-index: 1;
    }

    .modal-body {
        flex: 1 1 auto;
        position: relative;
        padding: 0;
    }

    .scrollable-content {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        overflow-y: auto;
        padding: 15px;
    }

    .modal-footer {
        display: flex;
        justify-content: center;
        padding: 15px;
        border-top: 1px solid #e5e5e5;
    }

    /* Content styling */
    .well {
        margin-bottom: 20px;
        background-color: #f9f9f9;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
        padding: 15px;
    }

    .well h4 {
        margin-top: 0;
        margin-bottom: 15px;
        border-bottom: 1px solid #e3e3e3;
        padding-bottom: 10px;
    }
    
    
    .well h4.position-heading {
        color: rgb(0, 0, 0);
        letter-spacing: 3px;
    }

    /* Candidate row styling */
    .candidate-row h4 {
        color: #28a745;
        margin-top: 0;
        margin-bottom: 5px;
    }

    .candidate-row {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        background: #f9f9f9;
        padding: 10px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .candidate-image-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .circular-image {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f5f5f5;
        border: 3px solid #ffffff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .candidate-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Close button styling */
    .close-ballot {
        background-color: #dc3545 !important;
        color: white !important;
        border: none;
        padding: 12px;
        border-radius: 5px;
        width: 100%;
        font-size: 16px;
        cursor: pointer;
        text-align: center;
    }
    
    .close-ballot:hover {
        background-color: #c82333 !important;
    }
    
    .close-ballot i {
        margin-right: 5px;
    }

    /* Preview button styling */
    button[name="closePreview"], button[name="closePlatform"] {
        background-color: #dc3545;
        color: white;
        border: none;
    }

    button[name="closePreview"]:hover, button[name="closePlatform"]:hover {
        background-color: #c82333;
    }

    button[name="submitPreview"] {
        background-color: #28a745;
    }

    button[name="submitPreview"]:hover {
        background-color: #218838;
    }
</style>

<script>
    $(document).ready(function() {
        // Reset when modal is hidden
        $('#platform').on('hide.bs.modal', function() {
            $(this).find('.modal-body').scrollTop(0);
        });
        
        // Platform click handler
        $('.platform').on('click', function() {
            var platform = $(this).data('platform');
            var fullname = $(this).data('fullname');
            var image = $(this).data('image');

            // Update the modal content
            $('.candidate').text(fullname);
            $('#plat_view').text(platform);
            $('#platform_image').attr('src', 'images/' + image);

            // Show the modal
            $('#platform').modal('show');
        });
    });
</script>