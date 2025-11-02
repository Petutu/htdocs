<?php
$servername = "localhost";
$username = "root"; // nebo jiný, pokud máš
$password = "";     // pokud máš prázdné heslo
$dbname = "phpmyadmin";

// Vytvoření připojení
$conn = new mysqli($servername, $username, $password, $dbname);

// Kontrola připojení
if ($conn->connect_error) {
    die("Připojení selhalo: " . $conn->connect_error);
}
?>
