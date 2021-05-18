<?php
session_start();
require_once 'config.php';
require_once 'classes/helper.php';
require_once 'api.php';

try {
    $api = new Api();
    $api->run();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
