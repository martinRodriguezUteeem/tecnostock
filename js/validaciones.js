document.addEventListener('DOMContentLoaded', () => {
    const formProducto = document.getElementById('formProducto');
    if (formProducto) {
        formProducto.addEventListener('submit', (e) => {
            const precio = parseFloat(document.getElementById('precio').value);
            const stockActual = parseInt(document.getElementById('stock_actual').value, 10);
            const stockMinimo = parseInt(document.getElementById('stock_minimo').value, 10);

            if (isNaN(precio) || precio < 0) {
                alert('El precio debe ser un número mayor o igual a 0.');
                e.preventDefault();
                return;
            }
            if (isNaN(stockActual) || stockActual < 0 || isNaN(stockMinimo) || stockMinimo < 0) {
                alert('Los valores de stock no pueden ser negativos.');
                e.preventDefault();
            }
        });
    }

    const formMovimiento = document.getElementById('formMovimiento');
    if (formMovimiento) {
        formMovimiento.addEventListener('submit', (e) => {
            const cantidad = parseInt(document.getElementById('cantidad').value, 10);
            if (isNaN(cantidad) || cantidad <= 0) {
                alert('La cantidad del movimiento debe ser mayor a 0.');
                e.preventDefault();
            }
        });
    }
});