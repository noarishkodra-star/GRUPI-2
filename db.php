<?php
$conn = new mysqli("localhost", "root", "", "bibloteka");

if ($conn->connect_error) {
    die("Gabim ne lidhjen me databzen.");
}

$conn->set_charset("utf8mb4");
?>