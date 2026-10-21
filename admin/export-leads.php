<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_admin_login();

filename:
$filename = 'aviation-pm-leads-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    'Lead ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Experience',
    'Background', 'Preferred Market', 'Career Goal', 'Status', 'Admin Notes',
    'IP Address', 'Submitted At'
]);

$stmt = db()->query('SELECT * FROM leads ORDER BY created_at DESC');

while ($lead = $stmt->fetch()) {
    fputcsv($output, [
        $lead['id'], $lead['first_name'], $lead['last_name'], $lead['email'], $lead['phone'],
        $lead['experience'], $lead['background'], $lead['preferred_market'], $lead['career_goal'],
        $lead['status'], $lead['admin_notes'], $lead['ip_address'], $lead['created_at']
    ]);
}

fclose($output);
exit;
