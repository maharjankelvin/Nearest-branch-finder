<?php
if (!defined('FUNCTIONS_INCLUDED')) {
    define('FUNCTIONS_INCLUDED', true);

if (!function_exists('calculateDistance')) {
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);
        
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;
        
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
}

// Find nearest branch to user location
if (!function_exists('findNearestBranch')) {
    function findNearestBranch($userLat, $userLon) {
        $branches = getMultipleResults("SELECT * FROM branches WHERE status = 'active'");
        $nearestBranch = null;
        $shortestDistance = PHP_FLOAT_MAX;
        
        foreach ($branches as $branch) {
            $distance = calculateDistance($userLat, $userLon, $branch['latitude'], $branch['longitude']);
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestBranch = $branch;
            }
        }
        
        return $nearestBranch;
    }
}

// Format currency
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '$' . number_format($amount, 2);
    }
}

// Sanitize input
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Get order status badge class
if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch ($status) {
            case 'pending': return 'badge-warning';
            case 'confirmed': return 'badge-info';
            case 'preparing': return 'badge-primary';
            case 'out_for_delivery': return 'badge-secondary';
            case 'delivered': return 'badge-success';
            case 'cancelled': return 'badge-danger';
            default: return 'badge-light';
        }
    }
}

// Generate random order ID
if (!function_exists('generateOrderId')) {
    function generateOrderId() {
        return 'ORD' . date('Ymd') . rand(1000, 9999);
    }
}

} // End include guard
