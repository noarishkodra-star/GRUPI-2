<?php
$conn = new mysqli(
    "sql301.infinityfree.com",
    "if0_43067023",
    "Ajla20210101",
    "if0_43067023_bibloteka"
);

if ($conn->connect_error) {
    die("Gabim ne lidhjen me databazen.");
}

$conn->set_charset("utf8mb4");
?>

