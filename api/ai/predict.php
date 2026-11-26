<?php
// Simple rule-based symptom checker. Replace $RULES with a real model later.
require_once '../../includes/auth.php';
$user = require_api_login();

// [keywords that must ALL appear, disease, confidence %, advice, severity]
$RULES = [
    [['fever', 'cough'],       'Influenza (Flu)', 85.5, 'Rest, hydration, antiviral medication', 'Medium'],
    [['headache', 'fatigue'],  'Migraine',        72.3, 'Avoid triggers, pain relief',          'Low'],
    [['chest pain'],           'Cardiac Issue',   45.0, 'Seek immediate medical attention',     'High'],
    [['sore throat'],          'Throat Infection',68.0, 'Warm fluids, see a doctor if it lasts', 'Low'],
    [['rash', 'itch'],         'Skin Allergy',    70.0, 'Antihistamine, avoid trigger',     'Low'],
];

$in = body();
$symptoms = trim($in['symptoms'] ?? '');
if ($symptoms === '') json_out(['status' => 'error', 'message' => 'Symptoms required'], 600);

$text = strtolower($symptoms);
$predictions = [];
foreach ($RULES as [$words, $disease, $confidence, $advice, $severity]) {
    if (count(array_filter($words, fn($w) => str_contains($text, $w))) === count($words)) {
        $predictions[] = ['disease' => $disease, 'confidence' => $confidence, 'suggestions' => $advice, 'severity' => $severity];
    }
}
if (!$predictions) {
    $predictions[] = ['disease' => 'Common Cold', 'confidence' => 65.0, 'suggestions' => 'Rest, fluids, over-the-counter medication', 'severity' => 'Low'];
}

// Save the result when we know which patient it is for.
$patientId = $user['role'] === 'patient' ? $user['patient_id'] : ($in['patient_id'] ?? null);
if ($patientId) {
    $db = db();
    $db->prepare('INSERT INTO symptoms_input (patient_id, symptoms, severity) VALUES (?, ?, ?)')
       ->execute([$patientId, $symptoms, $predictions[0]['severity']]);
    $symptomId = $db->lastInsertId();
    $save = $db->prepare('INSERT INTO disease_predictions (patient_id, symptom_id, predicted_disease, confidence_score, suggestions, severity_level) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($predictions as $p) {
        $save->execute([$patientId, $symptomId, $p['disease'], $p['confidence'], $p['suggestions'], $p['severity']]);
    }
}

json_out(['status' => 'success', 'predictions' => $predictions]);
