<?php
// Database connection - Include guard to prevent redeclaration
if (!defined('DB_INCLUDED')) {
    define('DB_INCLUDED', true);

    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'food_ordering';

    try {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            throw new Exception('Connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset("utf8");
    } catch (Exception $e) {
        die('Database connection failed: ' . $e->getMessage());
    }

    // Function to safely execute queries
    if (!function_exists('executeQuery')) {
        function executeQuery($query, $params = []) {
            global $conn;
            $stmt = $conn->prepare($query);
            if ($params) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt;
        }
    }

    // Function to get single result
    if (!function_exists('getSingleResult')) {
        function getSingleResult($query, $params = []) {
            $stmt = executeQuery($query, $params);
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }
    }

    // Function to get multiple results
    if (!function_exists('getMultipleResults')) {
        function getMultipleResults($query, $params = []) {
            $stmt = executeQuery($query, $params);
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}
