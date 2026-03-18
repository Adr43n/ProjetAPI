<?php
// Script de test pour déboguer l'appel API

$url = 'http://localhost/ProjetAPI/ProjetAPI-Backend/api/joueurs';

echo "Test d'appel API vers: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: " . ($error ?: "Aucune") . "\n";
echo "Response length: " . strlen($response) . "\n\n";
echo "Response:\n";
echo $response;
