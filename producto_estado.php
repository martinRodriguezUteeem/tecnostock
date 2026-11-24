<?php
require 'includes/auth_check.php';
require 'config/db.php';

$id = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? '';

if ($id && in_array($accion, ['activar', 'desactivar'])) {
    $nuevo_estado = ($accion === 'activar') ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE producto SET estado = ? WHERE id_producto = ?");
    $stmt->execute([$nuevo_estado, $id]);
    $texto = $nuevo_estado ? 'Producto reactivado.' : 'Producto desactivado (historial conservado).';
    header("Location: index.php?msg=" . urlencode($texto));
    exit;
}
header("Location: index.php");