<?php
session_start();

$isLocal = (php_sapi_name() === 'cli') || (
    isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
    )
);

if ($isLocal) {
    define("BASE_URL", "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/SriKumaran/public_html");
    $conn = mysqli_connect("localhost", "root", "", "srikumaran");
} else {
    define("BASE_URL", "https://srikumaranmanamaalai.com");
    $conn = mysqli_connect("localhost", "u891495087_db_matrimony", "Matrimony@2026", "u891495087_db_matrimony");
}
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to get database connection
if (!function_exists('getDb')) {
    function getDb() {
        global $conn;
        return $conn;
    }
}

// Helper function for safe queries
if (!function_exists('db_query')) {
    function db_query($sql) {
        global $conn;
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            die("Query failed: " . mysqli_error($conn));
        }
        return $result;
    }
}

// Helper function to fetch single value
if (!function_exists('db_fetch_single')) {
    function db_fetch_single($sql) {
        $result = db_query($sql);
        $row = mysqli_fetch_array($result);
        return $row[0] ?? 0;
    }
}

// Helper function to fetch all rows
if (!function_exists('db_fetch_all')) {
    function db_fetch_all($sql) {
        $result = db_query($sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
}