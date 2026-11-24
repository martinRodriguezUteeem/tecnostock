<?php
require 'includes/auth_check.php';
require 'config/db.php';

$busqueda = trim($_GET['q'] ?? '');
$cat_filtro = $_GET['categoria'] ?? '';
$ver_inactivos = isset($_GET['inactivos']) ? 1 : 0;

$categorias = $pdo->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre")->fetchAll();

$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM producto p 
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria 
        WHERE p.estado = :estado";
$params = ['estado' => $ver_inactivos ? 0 : 1];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE :q OR p.codigo LIKE :q)";
    $params['q'] = "%$busqueda%";
}
if ($cat_filtro !== '') {
    $sql .= " AND p.id_categoria = :cat";
    $params['cat'] = $cat_filtro;
}
$sql .= " ORDER BY (p.stock_actual < p.stock_minimo) DESC, p.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$alertas_count = $pdo->query("SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 small">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($alertas_count > 0): ?>
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <strong>¡Atención!</strong>&nbsp;Hay <?= $alertas_count ?> producto(s) con stock por debajo del mínimo permitido.
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h3 class="mb-0">Catálogo de Productos</h3>
        <div>
            <a href="movimiento.php" class="btn btn-success me-2">+ Registrar Movimiento</a>
            <a href="producto_form.php" class="btn btn-primary">+ Nuevo Producto</a>
        </div>
    </div>

    <form method="GET" class="card card-body mb-4 shadow-sm">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($busqueda) ?>">
            </div>
            <div class="col-md-3">
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id_categoria'] ?>" <?= $cat_filtro == $c['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="inactivos" id="inactivos" <?= $ver_inactivos ? 'checked' : '' ?>>
                    <label class="form-check-label" for="inactivos">Ver desactivados</label>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-end">Precio</th>
                        <th class="text-center">Stock / Mín.</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron productos.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): 
                        $bajo_stock = ($p['estado'] == 1 && $p['stock_actual'] < $p['stock_minimo']);
                    ?>
                        <tr class="<?= $bajo_stock ? 'table-danger' : '' ?>">
                            <td class="fw-bold"><?= htmlspecialchars($p['codigo']) ?></td>
                            <td>
                                <?= htmlspecialchars($p['nombre']) ?>
                                <?php if ($bajo_stock): ?>
                                    <span class="badge bg-danger ms-1">Stock Bajo</span>
                                <?php endif; ?>
                                <div class="small text-muted"><?= htmlspecialchars($p['descripcion']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($p['categoria_nombre']) ?></td>
                            <td class="text-end">$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <span class="fw-bold"><?= $p['stock_actual'] ?></span> / <span class="text-muted"><?= $p['stock_minimo'] ?></span>
                            </td>
                            <td>
                                <?= $p['estado'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Desactivado</span>' ?>
                            </td>
                            <td class="text-end">
                                <a href="producto_form.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <?php if ($p['estado']): ?>
                                    <a href="producto_estado.php?id=<?= $p['id_producto'] ?>&accion=desactivar" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Desactivar este producto? No se eliminará el historial.');">Desactivar</a>
                                <?php else: ?>
                                    <a href="producto_estado.php?id=<?= $p['id_producto'] ?>&accion=activar" 
                                       class="btn btn-sm btn-outline-success">Reactivar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>