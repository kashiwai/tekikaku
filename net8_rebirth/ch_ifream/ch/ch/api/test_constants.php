<?php
require_once('../../_etc/require_files.php');

header('Content-Type: application/json');
echo json_encode([
    'API_PROXY' => defined('API_PROXY') ? API_PROXY : 'NOT DEFINED',
    'POINT_CALC_MODE' => defined('POINT_CALC_MODE') ? POINT_CALC_MODE : 'NOT DEFINED',
    'timestamp' => date('Y-m-d H:i:s')
]);
