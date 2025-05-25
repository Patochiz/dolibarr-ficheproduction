<?php
/**
 * Script de diagnostic pour identifier les problèmes de chargement
 */

// Afficher les erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNOSTIC FICHEPRODUCTION ===";
echo "<br>";

// Test 1: Vérifier l'inclusion des fichiers
echo "1. Test des inclusions:<br>";

$files_to_test = [
    'includes/ficheproduction-permissions.php',
    'includes/ficheproduction-ajax.php', 
    'includes/ficheproduction-actions.php',
    'includes/ficheproduction-header.php',
    'includes/ficheproduction-display.php'
];

foreach ($files_to_test as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file existe<br>";
        try {
            include_once __DIR__ . '/' . $file;
            echo "✅ $file inclus sans erreur<br>";
        } catch (Exception $e) {
            echo "❌ $file erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $file manquant<br>";
    }
}

echo "<br>2. Test des fonctions définies:<br>";
$functions_to_test = [
    'checkPermissionsAndLoadOrder',
    'handleFicheProductionActions', 
    'prepareFicheProductionHeader',
    'displayFicheProductionContent',
    'getDeliveryInformation'
];

foreach ($functions_to_test as $func) {
    if (function_exists($func)) {
        echo "✅ Fonction $func définie<br>";
    } else {
        echo "❌ Fonction $func manquante<br>";
    }
}

echo "<br>3. Test des fichiers JavaScript:<br>";
$js_files = [
    'js/ficheproduction-core.js',
    'js/ficheproduction-utils.js',
    'js/ficheproduction-ajax.js',
    'js/ficheproduction-inventory.js',
    'js/ficheproduction-colis.js',
    'js/ficheproduction-dragdrop.js',
    'js/ficheproduction-ui.js',
    'js/ficheproduction-libre.js'
];

foreach ($js_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file manquant<br>";
    }
}

echo "<br>=== FIN DIAGNOSTIC ===";
?>