<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CustomSessionHandler.php';
require_once __DIR__ . '/Votes.php';
require_once __DIR__ . '/Receipt.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
}

class Ballot {
    private $db;
    private $session;
    private $votes;
    private $mail_config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->session = CustomSessionHandler::getInstance();
        $this->votes = new Votes();
        $this->mail_config = mail_config();
    }

    public function getPositions() {
        $sql = "SELECT * FROM positions ORDER BY priority ASC";
        return $this->db->query($sql);
    }

    public function getCandidates($position_id) {
        $sql = "SELECT candidates.*, partylists.name AS partylist_name 
                FROM candidates 
                LEFT JOIN partylists ON candidates.partylist_id = partylists.id 
                WHERE position_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $position_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getCandidate($candidate_id) {
        $sql = "SELECT * FROM candidates WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $candidate_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPosition($position_id) {
        $sql = "SELECT * FROM positions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $position_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function validateVotes($votes) {
        $errors = [];
        $positions = $this->getPositions();
        
        while ($position = $positions->fetch_assoc()) {
            $pos_id = $position['id'];
            if (isset($votes[$pos_id])) {
                $candidate_votes = is_array($votes[$pos_id]) ? $votes[$pos_id] : [$votes[$pos_id]];
                
                if (count($candidate_votes) > $position['max_vote']) {
                    $errors[] = 'You may only choose ' . $position['max_vote'] . ' candidates for ' . $position['description'];
                    continue;
                }
                
                foreach ($candidate_votes as $candidate_id) {
                    $candidate = $this->getCandidate($candidate_id);
                    if (!$candidate || $candidate['position_id'] != $pos_id) {
                        $errors[] = 'Invalid candidate selected for ' . $position['description'];
                    }
                }
            }
        }
        
        return $errors;
    }

    public function generateReceipt($vote_ref, $voter, $votes_data, $election) {
        try {
            // Get voter's email - student number is used for email
            $email = $voter['student_number'] . '@btech.ph.education';

            // Build receipt HTML
            $receipt = $this->buildReceiptHTML($voter, $vote_ref, $votes_data, $election);

            // Send email using PHPMailer
            return $this->sendReceiptEmail(
                $email, 
                $receipt, 
                $vote_ref, 
                $election['election_name'], 
                $voter['firstname'] . ' ' . $voter['lastname']
            );

        } catch (Exception $e) {
            error_log("Receipt generation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function sendReceiptEmail($email, $html_content, $vote_ref, $election_name, $voter_name) {
        $mail = new PHPMailer(true);
        
        try {
            // Configure mail
            if ($this->mail_config['use_smtp']) {
                $mail->isSMTP();
                $mail->Host = $this->mail_config['smtp']['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->mail_config['smtp']['username'];
                $mail->Password = $this->mail_config['smtp']['password'];
                $mail->SMTPSecure = $this->mail_config['smtp']['encryption'] === 'tls' ? 
                    PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = $this->mail_config['smtp']['port'];
            } else {
                $mail->isMail();
            }
            
            // Recipients
            $mail->setFrom(
                $this->mail_config['mail_from'], 
                $this->mail_config['mail_from_name']
            );
            $mail->addAddress($email, $voter_name);
            $mail->addReplyTo(
                $this->mail_config['mail_reply_to'], 
                $this->mail_config['mail_from_name']
            );
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $election_name . ' - Vote Receipt [Ref: ' . $vote_ref . ']';
            $mail->Body = $html_content;
            
            // Plain text alternative
            $mail->AltBody = "Your vote has been recorded.\nVote Reference: " . $vote_ref;
            
            // Send the email
            $mail->send();
            
            return [
                'success' => true,
                'message' => "Receipt sent successfully to $email"
            ];
            
        } catch (Exception $e) {
            error_log("Failed to send receipt email: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Could not send receipt. Error: {$mail->ErrorInfo}"
            ];
        }
    }

    private function buildReceiptHTML($voter, $vote_ref, $votes_data, $election) {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vote Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #1d7c39;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 20px;
                    border-radius: 5px;
                    margin-top: 20px;
                }
                .vote-ref {
                    font-size: 24px;
                    font-weight: bold;
                    color: #1d7c39;
                    text-align: center;
                    margin: 20px 0;
                }
                .position {
                    margin: 15px 0;
                    padding: 10px;
                    background: #fff;
                    border-radius: 5px;
                }
                .position-title {
                    color: #1d7c39;
                    margin-bottom: 10px;
                }
                .candidate {
                    padding: 5px 10px;
                }
                .footer {
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{$election['election_name']}</h1>
                </div>
                <div class="content">
                    <h2>Vote Receipt</h2>
                    <div class="vote-ref">Reference: {$vote_ref}</div>
                    
                    <div class="voter-info">
                        <p><strong>Student Number:</strong> {$voter['student_number']}</p>
                        <p><strong>Date:</strong> " . date('F j, Y g:i A') . "</p>
                    </div>

                    <h3>Votes Cast:</h3>
HTML;
        
        // Get all positions
        $positions = $this->getPositions();
        while ($position = $positions->fetch_assoc()) {
            $html .= '<div class="position">';
            $html .= '<h4 class="position-title">' . htmlspecialchars($position['description']) . '</h4>';
            
            if (isset($votes_data[$position['id']])) {
                $position_votes = $votes_data[$position['id']];
                if (!is_array($position_votes)) {
                    $position_votes = [$position_votes];
                }
                
                foreach ($position_votes as $candidate_id) {
                    $candidate = $this->getCandidate($candidate_id);
                    if ($candidate) {
                        $html .= '<div class="candidate">';
                        $html .= '• ' . htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']);
                        if (!empty($candidate['partylist_name'])) {
                            $html .= ' (' . htmlspecialchars($candidate['partylist_name']) . ')';
                        }
                        $html .= '</div>';
                    }
                }
            } else {
                $html .= '<div class="candidate">No vote cast</div>';
            }
            $html .= '</div>';
        }

        $html .= <<<HTML
                </div>
                <div class="footer">
                    <p>&copy; 2025 E-Halal BTECHenyo Voting System. All rights reserved.</p>
                    <p>This is an automated receipt, please keep for your records.</p>
                </div>
            </div>
        </body>
        </html>
HTML;

        return $html;
    }

    public function submitVote($voter_id, $votes) {
        try {
            $this->db->beginTransaction();
            
            // Validate votes
            $errors = $this->validateVotes($votes);
            if (!empty($errors)) {
                throw new Exception(implode("\n", $errors));
            }
            
            // Check if voter has already voted
            $sql = "SELECT has_voted FROM voters WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $voter_id);
            $stmt->execute();
            $voter = $stmt->get_result()->fetch_assoc();
            
            if (!$voter) {
                throw new Exception("Voter not found.");
            }

            if ($voter['has_voted'] == 1) {
                throw new Exception("You have already submitted your votes.");
            }
            
            // Submit votes using Votes class
            $voteResult = $this->votes->submitVotes($voter_id, $votes);
            
            if (!$voteResult['success']) {
                throw new Exception("Failed to submit votes. Please try again.");
            }
            
            $this->db->commit();
            return $voteResult;
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getVoterVotes($voter_id) {
        return $this->votes->getVoterVotes($voter_id);
    }

    public function getElectionName() {
        $parse = parse_ini_file("admin/config.ini", false, INI_SCANNER_RAW);
        return isset($parse["election_name"]) ? $parse["election_name"] : "Election";
    }

    public function slugify($text) {
        // Remove non-letter or digits, replace with -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim
        $text = trim($text, '-');
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // Lowercase
        $text = strtolower($text);
        return $text;
    }

    public function renderBallot() {
        ?>
        <!-- Voting Ballot -->
        <form method="POST" id="ballotForm" action="submit_ballot.php">
            <?php
            $positions = $this->getPositions();
            while ($row = $positions->fetch_assoc()) {
                $pos_id = $row['id'];
                ?>
                <div class="position-section" data-max-vote="<?php echo $row['max_vote']; ?>">
                    <div class="position-header">
                        <div class="title-and-instruction">
                            <h3 class="position-title"><?php echo $row['description']; ?></h3>
                            <p class="position-instruction">
                                <?php echo $row['max_vote'] > 1 ? "You may select up to " . $row['max_vote'] . " candidates" : "Select only one candidate"; ?>
                            </p>
                        </div>
                    </div>
                    <div class="candidates-grid">
                        <?php
                        $candidates = $this->getCandidates($pos_id);
                        while ($candidate = $candidates->fetch_assoc()) {
                            ?>
                            <div class="candidate-card" data-candidate-id="<?php echo $candidate['id']; ?>">
                                <input type="<?php echo $row['max_vote'] > 1 ? 'checkbox' : 'radio'; ?>" 
                                       class="candidate-input" 
                                       name="votes[<?php echo $pos_id; ?>]<?php echo $row['max_vote'] > 1 ? '[]' : ''; ?>" 
                                       value="<?php echo $candidate['id']; ?>"
                                       style="display: none !important;visibility: hidden !important;">
                                <div class="card-content">
                                    <div class="candidate-photo-container">
                                        <img src="<?php echo !empty($candidate['photo']) ? 'images/' . $candidate['photo'] : 'images/profile.jpg'; ?>" 
                                             alt="Candidate Photo" 
                                             class="candidate-photo">
                                    </div>
                                    <div class="candidate-info">
                                        <strong class="candidate-name"><?php echo $candidate['firstname'] . ' ' . $candidate['lastname']; ?></strong>
                                        <?php if (!empty($candidate['partylist_name'])): ?>
                                            <p class="candidate-party"><?php echo $candidate['partylist_name']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="clear-selection-container">
                        <a href="#" class="clear-selection" data-position="<?php echo $pos_id; ?>">
                            Clear selection
                        </a>
                    </div>
                </div>
            <?php } ?>
            <div class="text-center ballot-actions">
                <button type="button" class="btn btn-success btn-flat" id="preview" name="preview"><i class="fa fa-file-text"></i> Preview</button>
                <button type="submit" class="btn btn-primary btn-flat" name="vote"><i class="fa fa-check"></i> Submit</button>
            </div>
        </form>

        <style>
        /* Common styles for all devices */
        .position-section {
            margin-bottom: 30px;
            background:#f5f5f5;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 234px;
        }
        
        .position-header {
            display: flex;
        }
        
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .candidate-card {
            cursor: pointer;
        }
        
        .candidate-card.selected {
            border-color: #249646;
            background-color: rgb(226, 252, 229);
        }
        
        .candidate-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .candidate-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .candidate-party {
            color: #666;
            margin: 0;
        }
        
        .ballot-actions {
            margin-top: 30px;
        }
        
        .ballot-actions button {
            margin: 0 10px;
        }
        
        /* Clear selection */
        .clear-selection-container {
            text-align: right;
            margin-top: 8px;
        }
        
        .clear-selection {
            color: #6c757d;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }
        
        .clear-selection:hover {
            text-decoration: underline;
            color: #5a6268;
        }
        
        .clear-selection:focus {
            color: rgb(47, 168, 67);
            text-decoration: underline;
            outline: none;
        }
        
        /* Button styles */
        button[name="vote"], button[name="preview"] {
            background-color: #259646 !important;
            border: none !important;
        }
        
        button[name="vote"]:hover, button[name="preview"]:hover, 
        button[name="vote"]:active, button[name="preview"]:active {
            background-color: #1e7e34 !important;
        }
        
        /* Mobile styles */
        @media (max-width: 767px) {
            .candidates-grid {
                display: block;
            }
            
            .candidate-card {
                display: block;
                margin-bottom: 10px;
                height: 60px;
                border-radius: 5px;
                background-color: #ffffff;
                border: 1px solid #ddd;
                overflow: hidden;
            }
            
            .card-content {
                display: flex;
                align-items: center;
                padding: 8px;
                height: 100%;
                box-sizing: border-box;
            }
            
            .candidate-photo-container {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                overflow: hidden;
                margin-right: 12px;
                flex-shrink: 0;
            }
            
            .candidate-info {
                display: flex;
                flex-direction: column;
                justify-content: center;
                text-align: left;
                margin-bottom: 0;
            }
            
            .candidate-name {
                font-size: 14px;
                margin-bottom: 2px;
                font-weight: bold;
                line-height: 1.1;
            }
            
            .candidate-party {
                font-size: 12px;
                margin: 0;
                line-height: 1.1;
            }
            
            .position-section {
                padding: 12px;
                margin-bottom: 40px;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 12px;
            }
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .candidate-card {
                cursor: pointer;
                transition: all 0.3s ease;
                border: 2px solid transparent;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .card-content {
                padding: 15px;
            }
            
            .candidate-photo-container {
                width: 150px;
                height: 150px;
                margin: 0 auto 15px;
                border-radius: 50%;
                overflow: hidden;
            }
            
            .candidate-info {
                text-align: center;
                margin-bottom: 15px;
            }
            
            .candidate-name {
                display: block;
                font-size: 1.1em;
                margin-bottom: 5px;
                color: #333;
            }
        }
        
        /* Small device adjustments */
        @media (max-width: 400px) {
            .position-section {
                padding: 10px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            
            #ballotForm {
                padding: 10px;
            }
        }
        
        @media (max-width: 350px) {
            .position-section {
                padding: 5px;
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle candidate card selection
            document.querySelectorAll('.candidate-card').forEach(card => {
                const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                
                // Add touch event for mobile devices
                if (isTouch) {
                    card.addEventListener('touchstart', handleSelection);
                }
                
                // Keep click for non-touch devices
                card.addEventListener('click', handleSelection);
                
                // Shared handler function for both touch and click
                function handleSelection(e) {
                    e.preventDefault();
                    
                    const input = card.querySelector('.candidate-input');
                    const positionSection = card.closest('.position-section');
                    const maxVote = parseInt(positionSection.dataset.maxVote);
                    const isRadio = input.type === 'radio';
                    
                    if (card.classList.contains('disabled') && !input.checked) return;
                    
                    if (isRadio) {
                        // Single selection handling
                        positionSection.querySelectorAll('.candidate-card').forEach(c => {
                            c.classList.remove('selected');
                        });
                        card.classList.add('selected');
                        input.checked = true;
                    } else {
                        // Multiple selection handling
                        const selectedCount = positionSection.querySelectorAll('.candidate-input:checked').length;
                        
                        if (!input.checked && selectedCount >= maxVote) {
                            return; // Max selections reached
                        }
                        
                        input.checked = !input.checked;
                        card.classList.toggle('selected');
                        
                        // Update disabled state for other cards
                        const remainingSlots = maxVote - (input.checked ? selectedCount + 1 : selectedCount - 1);
                        positionSection.querySelectorAll('.candidate-card').forEach(otherCard => {
                            const cardInput = otherCard.querySelector('.candidate-input');
                            if (!cardInput.checked) {
                                otherCard.classList.toggle('disabled', remainingSlots === 0);
                            }
                        });
                    }
                }
            });

            // Handle clear selection links
            document.querySelectorAll('.clear-selection').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const positionSection = this.closest('.position-section');
                    positionSection.querySelectorAll('.candidate-card').forEach(card => {
                        card.classList.remove('selected', 'disabled');
                        card.querySelector('.candidate-input').checked = false;
                    });
                });
            });

            // Form validation
            document.getElementById('ballotForm').addEventListener('submit', function(e) {
                const sections = this.querySelectorAll('.position-section');
                let valid = true;
                
                sections.forEach(section => {
                    const selectedCount = section.querySelectorAll('.candidate-input:checked').length;
                    const maxVote = parseInt(section.dataset.maxVote);
                    if (selectedCount > maxVote) {
                        valid = false;
                        alert(`You can only select up to ${maxVote} candidate(s) for ${section.querySelector('.position-title').textContent}`);
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
        </script>
        <?php
    }
}
