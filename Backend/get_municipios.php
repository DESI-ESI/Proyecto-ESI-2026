<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
require 'config/Conexion.php';

try {
    $stmt = $pdo->query("SELECT Id_Municipio AS id_municipio, Nombre AS nombre FROM municipio ORDER BY Nombre");
    $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $municipios]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>