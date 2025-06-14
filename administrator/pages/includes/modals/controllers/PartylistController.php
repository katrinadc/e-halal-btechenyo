<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../classes/Partylist.php';
require_once __DIR__ . '/../../../../classes/Logger.php';
require_once __DIR__ . '/../../../../classes/Admin.php';
require_once __DIR__ . '/../../../../classes/Elections.php';

// Check if admin is logged in
$admin = Admin::getInstance();
if (!$admin->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize classes
$partylist = Partylist::getInstance();
$logger = AdminLogger::getInstance();
$election = Elections::getInstance();

// Check if election is active
if ($election->isModificationLocked()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Modifications are not allowed while election is active']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['error' => false, 'message' => ''];
    
    try {
        // Determine if this is an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!isset($_POST['action'])) {
            throw new Exception('Action not specified');
        }

        switch ($_POST['action']) {
            case 'add':
                if (!isset($_POST['name'])) {
                    throw new Exception('Partylist name is required');
                }

                $result = $partylist->addPartylist($_POST['name']);
                
                if (!$result) {
                    throw new Exception('Failed to add partylist');
                }
                
                $logger->logAdminAction(
                    $admin->getUsername(),
                    $admin->getRole(),
                    "Added partylist: {$_POST['name']}"
                );
                
                $response['message'] = 'Partylist added successfully';
                break;

            case 'edit':
                if (!isset($_POST['id'], $_POST['name'])) {
                    throw new Exception('Missing required fields');
                }

                // Get old partylist data for logging
                $oldPartylist = $partylist->getPartylist($_POST['id']);
                if (!$oldPartylist) {
                    throw new Exception('Partylist not found');
                }

                $result = $partylist->updatePartylist($_POST['id'], $_POST['name']);
                
                if (!$result) {
                    throw new Exception('Failed to update partylist');
                }
                
                $logger->logAdminAction(
                    $admin->getUsername(),
                    $admin->getRole(),
                    "Updated partylist: {$oldPartylist['name']} to {$_POST['name']}"
                );
                
                $response['message'] = 'Partylist updated successfully';
                break;

            case 'delete':
                if (!isset($_POST['id'])) {
                    throw new Exception('Partylist ID not provided');
                }

                // Get partylist data for logging before deletion
                $partylistData = $partylist->getPartylist($_POST['id']);
                if (!$partylistData) {
                    throw new Exception('Partylist not found');
                }

                $result = $partylist->deletePartylist($_POST['id']);
                
                if ($result) {
                    $logger->logAdminAction(
                        $admin->getUsername(),
                        $admin->getRole(),
                        "Deleted partylist: {$partylistData['name']}"
                    );
                    $response['message'] = 'Partylist deleted successfully';
                } else {
                    throw new Exception('Cannot delete partylist with existing candidates');
                }
                break;

            case 'get':
                if (!isset($_POST['id'])) {
                    throw new Exception('Partylist ID not provided');
                }

                $partylistData = $partylist->getPartylist($_POST['id']);
                if (!$partylistData) {
                    throw new Exception('Partylist not found');
                }

                $response['data'] = $partylistData;
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $response['error'] = true;
        $response['message'] = $e->getMessage();
        
        // Log the error
        $logger->logAdminAction(
            $admin->getUsername(),
            $admin->getRole(),
            "Error in partylist management: {$e->getMessage()}"
        );
    }

    // Send response
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        // For form submissions, redirect with message
        if ($response['error']) {
            $_SESSION['error'] = $response['message'];
        } else {
            $_SESSION['success'] = $response['message'];
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
} else {
    // Handle non-POST requests
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Invalid request method'
    ]);
    exit();
} 