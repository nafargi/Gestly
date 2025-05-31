<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT firstname, lastname, email FROM guests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $guest = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($guest);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Guest not found']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Guest ID not provided']);
}
?>