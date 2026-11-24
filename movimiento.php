<?php
require 'includes/auth_check.php';
require 'config/db.php';

$errores = [];
$productos = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM producto WHERE estado = 1 ORDER BY nombre")->fetchAll();
$historial = $pdo->query("SELECT m.*, p.codigo, p.nombre AS prod_nombre, u.nombre AS user_nombre 
                          FROM movimiento m 
                          INNER JOIN producto p ON m.id_producto = p.id_producto 
                          INNER JOIN usuario u ON m.id_usuario = u.id_usuario 
                          ORDER BY m.fecha_hora DESC LIMIT 10")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = $_POST['id_producto'] ?? '';
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = (int)($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');

    if ($id_producto === '' || !in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        $errores[] = "Seleccione un producto y un tipo de movimiento válido.";
    }
    if ($cantidad <= 0) {
        $errores[] = "La cantidad debe ser mayor a cero.";
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT stock_actual FROM producto WHERE id_producto = ? FOR UPDATE");
            $stmt->execute([$id_producto]);
            $prod = $stmt->fetch();

            if (!$prod) {
                throw new Exception("Producto no encontrado.");
            }
            if ($tipo === 'SALIDA' && $prod['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente. Disponible: {$prod['stock_actual']} unidades.");
            }

            $ins = $pdo->prepare("INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$tipo, $cantidad, $observacion, $id_producto, $_SESSION['id_usuario']]);

            $operador = ($tipo === 'ENTRADA') ? '+' : '-';
            $upd = $pdo->prepare("UPDATE producto SET stock_actual = stock_actual $operador ? WHERE id_producto = ?");
            $upd->execute([$cantidad, $id_producto]);

            $pdo->commit();
            header("Location: index.php?msg=Movimiento ($tipo) registrado y stock actualizado.");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Movimiento - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Registrar Entrada / Salida</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="formMovimiento">
                        <div class="mb-3">
                            <label class="form-label">Producto *</label>
                            <select name="id_producto" class="form-select" required>
                                <option value="">Seleccione un producto...</option>
                                <?php foreach ($productos as $p): ?>
                                    <option value="<?= $p['id_producto'] ?>">
                                        [<?= htmlspecialchars($p['codigo']) ?>] <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Movimiento *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="ENTRADA">ENTRADA (Compra / Reposición)</option>
                                <option value="SALIDA">SALIDA (Venta / Merma)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" name="cantidad" id="cantidad" min="1" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <input type="text" name="observacion" class="form-control" placeholder="Ej: Factura #104, Venta mostrador...">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-secondary">Volver</a>
                            <button type="submit" class="btn btn-success">Confirmar Movimiento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Últimos 10 Movimientos Registrados</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cant.</th>
                                <th>Responsable</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $h): ?>
                                <tr>
                                    <td class="small"><?= date('d/m H:i', strtotime($h['fecha_hora'])) ?></td>
                                    <td><?= htmlspecialchars($h['prod_nombre']) ?></td>
                                    <td>
                                        <span class="badge <?= $h['tipo'] === 'ENTRADA' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= $h['tipo'] ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= $h['cantidad'] ?></td>
                                    <td class="small"><?= htmlspecialchars($h['user_nombre']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/validaciones.js"></script>
</body>
</html>