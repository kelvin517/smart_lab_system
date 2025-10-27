<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['symptoms'])) {
    $_SESSION['ai_error'] = "Please describe your symptoms.";
    header("Location: dashboard.php");
    exit;
}

$symptoms = strtolower($_POST['symptoms']);
$recommendations = [];

// Rule-based matching (can be replaced with ML model later)
if (preg_match('/(cough|chest pain|breath|pneumonia)/', $symptoms)) {
    $recommendations[] = "Chest X-Ray";
}
if (preg_match('/(fever|malaria|fatigue|chills|sweats)/', $symptoms)) {
    $recommendations[] = "Malaria Blood Test";
}
if (preg_match('/(diabetes|thirst|sugar|urination)/', $symptoms)) {
    $recommendations[] = "Blood Sugar Test";
}
if (preg_match('/(stomach|abdominal|diarrhea|vomit)/', $symptoms)) {
    $recommendations[] = "Stool Test";
}
if (preg_match('/(headache|dizziness|blurred vision)/', $symptoms)) {
    $recommendations[] = "Blood Pressure Test";
}
if (preg_match('/(infection|urine|burning|urination)/', $symptoms)) {
    $recommendations[] = "Urinalysis";
}

$_SESSION['ai_result'] = $recommendations ? implode(", ", $recommendations) : "No specific test found. Please consult your doctor.";

header("Location: dashboard.php");
exit;