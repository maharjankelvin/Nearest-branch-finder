<?php
// serve_image.php: Outputs image data from DB for a menu item
include 'includes/db.php';
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing id');
}
$id = intval($_GET['id']);
$result = getSingleResult("SELECT image_data, name FROM menu_items WHERE id = ?", [$id]);
if (!$result || empty($result['image_data'])) {
    http_response_code(404);
    exit('Image not found');
}
// Try to detect mime type (default to jpeg)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$type = $finfo->buffer($result['image_data']);
if (!$type) $type = 'image/jpeg';
header('Content-Type: ' . $type);
echo $result['image_data'];
