<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    
    // Fetch a club_id
    $clubId = $db->query("SELECT id FROM clubs LIMIT 1")->fetchColumn();
    if (!$clubId) {
        echo "No clubs found.\n";
        exit;
    }

    $eventId = 'evt_test_' . bin2hex(random_bytes(4));
    $title = 'Test Event Validation 2026';
    $tagline = 'Test Tagline';
    $slug = 'test-event-validation-2026-' . rand(100, 999);
    $banner = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop';
    $description = 'Test event description for database schema verification.';
    $venue = 'Computer Lab 1';
    $event_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    $reg_link = 'contact.html';
    $status = 'upcoming';
    $event_type = 'Hands-on Workshop';
    $outcomes_summary = 'Test outcomes';
    $speaker_name = 'Test Speaker';
    $speaker_designation = 'Test Designation';
    $agenda_timeline = 'Phase 1: Intro';
    $target_audience = 'All UIT Departments & Years';

    $stmtIns = $db->prepare("
        INSERT INTO events (
            id, club_id, title, tagline, slug, banner, description, venue, event_date, registration_link, status,
            event_type, outcomes_summary, speaker_name, speaker_designation, agenda_timeline, target_audience
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([
        $eventId, $clubId, $title, $tagline, $slug, $banner, $description, $venue, $event_date, $reg_link, $status,
        $event_type, $outcomes_summary, $speaker_name, $speaker_designation, $agenda_timeline, $target_audience
    ]);

    echo "SUCCESS: Event created with ID '$eventId'!\n";

    // Clean up test event
    $db->exec("DELETE FROM events WHERE id = '$eventId'");
    echo "Cleaned up test event.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
