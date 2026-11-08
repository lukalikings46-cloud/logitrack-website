<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDB();

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    json_error('Provide a tracking id, e.g. ?id=TRK001.');
}

$stmt = $pdo->prepare('SELECT * FROM shipments WHERE id = ?');
$stmt->execute([strtoupper($id)]);
$shipment = $stmt->fetch();

if (!$shipment) {
    json_out(['success' => true, 'found' => false]);
}

$events = $pdo->prepare('SELECT title, location, event_status, event_time FROM tracking_updates WHERE shipment_id = ? ORDER BY event_time ASC');
$events->execute([$shipment['id']]);
$timeline = $events->fetchAll();

json_out([
    'success'  => true,
    'found'    => true,
    'shipment' => $shipment,
    'timeline' => $timeline,
]);
