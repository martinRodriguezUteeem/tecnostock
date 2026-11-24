# TecnoStock - Sistema Web de Control de Inventario

Aplicación web desarrollada en PHP (PDO), MySQL y Bootstrap 5 para gestionar el catálogo de productos, alertas de stock mínimo y trazabilidad de entradas y salidas mediante transacciones atómicas.

## Requisitos
- XAMPP (Apache + MySQL/MariaDB) con PHP 8.x
- Navegador web moderno

## Instalación y puesta en marcha
1. Clonar o copiar la carpeta `tecnostock` dentro de `C:\xampp\htdocs\`.
2. Iniciar los servicios **Apache** y **MySQL** desde el Panel de Control de XAMPP.
3. Abrir `http://localhost/phpmyadmin/`, ir a la pestaña **SQL** e importar o ejecutar el archivo `database.sql` incluido en la raíz del proyecto.
4. Verificar las credenciales de conexión en `config/db.php` (por defecto: usuario `root`, sin contraseña).
5. Acceder desde el navegador a `http://localhost/tecnostock/login.php`.

## Credenciales de prueba
- **Correo:** `admin@tecnostock.cl`
- **Contraseña:** `password`

## Estructura del proyecto
- `config/db.php`: Conexión PDO segura con manejo de excepciones.
- `includes/auth_check.php`: Middleware de verificación de sesión activa.
- `login.php` / `logout.php`: Autenticación con `password_verify` y cierre de sesión.
- `index.php`: Catálogo principal con búsqueda, filtros por categoría/estado y alertas visuales de stock bajo.
- `producto_form.php`: Registro y edición de productos con validación de unicidad de código.
- `producto_estado.php`: Desactivación y reactivación lógica (conserva integridad referencial).
- `movimiento.php`: Registro de entradas y salidas mediante `beginTransaction()` / `commit()` / `rollBack()` e historial reciente.
- `js/validaciones.js`: Validaciones de integridad en cliente.
- `database.sql`: Script DDL y DML con restricciones `CHECK` y claves foráneas.