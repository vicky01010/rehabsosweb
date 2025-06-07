<?php
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];
$status = $data['status'];

$file = 'data_status.json';
$statusData = json_decode(file_get_contents($file), true);

foreach ($statusData as &$pengajuan) {
    if ($pengajuan['id'] == $id) {
        $pengajuan['status'] = $status;
        break;
    }
}

file_put_contents($file, json_encode($statusData, JSON_PRETTY_PRINT));
echo json_encode(['success' => true, 'message' => "Status pengajuan ID $id diperbarui ke $status."]);
