<?php
require 'includes/auth_check.php';
require 'config/db.php';

$id = $_GET['id'] ?? null;
$errores = [];
$producto = [
    'codigo' => '', 'nombre' => '', 'descripcion' => '',
    'precio' => '', 'stock_actual' => 0, 'stock_minimo' => 5, 'id_categoria' => ''
];

$categorias = $pdo->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1")->fetchAll();

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM producto WHERE id_producto = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();
    if (!$producto) { header("Location: index.php"); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = $_POST['precio'] ?? '';
    $stock_actual = $_POST['stock_actual'] ?? 0;
    $stock_minimo = $_POST['stock_minimo'] ?? 0;
    $id_categoria = $_POST['id_categoria'] ?? '';

    if ($codigo === '' || $nombre === '' || $id_categoria === '') {
        $errores[] = "Código, nombre y categoría son obligatorios.";
    }
    if (!is_numeric($precio) || $precio < 0) {
        $errores[] = "El precio no puede ser negativo.";
    }
    if (!is_numeric($stock_actual) || $stock_actual < 0 || !is_numeric($stock_minimo) || $stock_minimo < 0) {
        $errores[] = "El stock actual y mínimo deben ser mayores o iguales a 0.";
    }

    $sqlCheck = "SELECT id_producto FROM producto WHERE codigo = ?" . ($id ? " AND id_producto != ?" : "");
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute($id ? [$codigo, $id] : [$codigo]);
    if ($stmtCheck->fetch()) {
        $errores[] = "El código '$codigo' ya existe en otro producto.";
    }

    if (empty($errores)) {
        if ($id) {
            $sql = "UPDATE producto SET codigo=?, nombre=?, descripcion=?, precio=?, stock_actual=?, stock_minimo=?, id_categoria=? WHERE id_producto=?";
            $pdo->prepare($sql)->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria, $id]);
            header("Location: index.php?msg=Producto actualizado correctamente");
        } else {
            $sql = "INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria]);
            header("Location: index.php?msg=Producto registrado correctamente");
        }
        exit;
    }
    $producto = compact('codigo', 'nombre', 'descripcion', 'precio', 'stock_actual', 'stock_minimo', 'id_categoria');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Editar' : 'Nuevo' ?> Producto - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= $id ? 'Editar Producto' : 'Registrar Nuevo Producto' ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="formProducto" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Código Único *</label>
                                <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($producto['codigo']) ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto *</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría *</label>
                            <select name="id_categoria" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id_categoria'] ?>" <?= $producto['id_categoria'] == $c['id_categoria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Precio ($) *</label>
                                <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" value="<?= htmlspecialchars($producto['precio']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Actual *</label>
                                <input type="number" min="0" name="stock_actual" id="stock_actual" class="form-control" value="<?= htmlspecialchars($producto['stock_actual']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Mínimo *</label>
                                <input type="number" min="0" name="stock_minimo" id="stock_minimo" class="form-control" value="<?= htmlspecialchars($producto['stock_minimo']) ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/validaciones.js"></script>
</body>
</html>