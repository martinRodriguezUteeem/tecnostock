# TecnoStock - Sistema Web de Control de Inventario

Aplicación web desarrollada en PHP 8 (PDO), MySQL y Bootstrap 5 para gestionar el catálogo de productos, alertas de stock mínimo y trazabilidad de entradas y salidas mediante transacciones atómicas.

---

## 1. Análisis y Requerimientos

### Problema y Alcance
TecnoStock registraba su inventario en planillas manuales, provocando códigos duplicados, desfase en existencias y falta de visibilidad sobre productos críticos. Este MVP centraliza el catálogo, controla entradas/salidas en tiempo real y alerta visualmente cuando el stock cae bajo el mínimo definido.

### Actor Principal
* **Encargado de inventario:** Administra el ciclo de vida de productos (crear, consultar, buscar, editar, desactivar/reactivar) y registra movimientos de entrada y salida de stock.

### Requerimientos Funcionales y Reglas de Negocio Implementadas
| Regla de Negocio | Implementación Técnica en el Sistema |
| :--- | :--- |
| **Código de producto único** | Restricción `UNIQUE` en MySQL (`producto.codigo`) y validación previa mediante consulta `SELECT` en `producto_form.php`. |
| **Sin valores negativos** | Validación en cliente (`js/validaciones.js`), validación en servidor (`PHP`) y restricciones `CHECK` en MySQL (`precio >= 0`, `stock_actual >= 0`, `stock_minimo >= 0`, `cantidad > 0`). |
| **Trazabilidad de movimientos** | Cada registro en `movimiento` guarda automáticamente `fecha_hora`, `tipo`, `cantidad`, `id_producto` y el `id_usuario` de la sesión activa. |
| **Bloqueo de stock negativo en salidas** | Transacción atómica (`beginTransaction`, `SELECT ... FOR UPDATE`, `commit`/`rollBack`) en `movimiento.php` que impide descontar más unidades de las disponibles. |
| **Integridad referencial (sin borrado físico)** | Eliminación lógica mediante cambio de columna `estado` (`1` = Activo, `0` = Inactivo) en `producto_estado.php`, conservando el historial de movimientos. |
| **Actualización automática y alertas** | Recálculo inmediato de `stock_actual` tras cada movimiento y resaltado visual (`table-danger` + badge `Stock bajo`) en `index.php` cuando `stock_actual < stock_minimo`. |

---

## 2. Arquitectura y Base de Datos

* **Entidades:** `usuario`, `categoria`, `producto`, `movimiento` (normalizadas en 3FN con claves primarias `PK` y foráneas `FK` con `ON DELETE RESTRICT`).
* **Estructura del proyecto:**
  * `config/db.php`: Conexión PDO con manejo de excepciones y modo fetch asociativo.
  * `includes/auth_check.php`: Protección de rutas privadas mediante verificación de sesión.
  * `login.php` / `logout.php`: Autenticación segura con `password_verify()` y destrucción de sesión.
  * `index.php`: Listado responsivo con filtros por texto (código/nombre), categoría y estado, más indicador de stock bajo.
  * `producto_form.php`: Formulario unificado de creación y edición de productos.
  * `producto_estado.php`: Activación y desactivación lógica de productos.
  * `movimiento.php`: Registro transaccional de entradas/salidas e historial de los últimos movimientos.
  * `js/validaciones.js`: Validaciones inmediatas en el navegador antes del envío.
  * `database.sql`: Script DDL y DML reproducible con datos iniciales.

---

## 3. Instalación y Puesta en Marcha

1. Clonar o descomprimir la carpeta `tecnostock` dentro de `C:\xampp\htdocs\`.
2. Iniciar **Apache** y **MySQL** en el Panel de Control de XAMPP.
3. Ingresar a `http://localhost/phpmyadmin/`, abrir la pestaña **SQL** y ejecutar el archivo `database.sql`.
4. Verificar credenciales de BD en `config/db.php` (por defecto: usuario `root`, sin contraseña).
5. Abrir `http://localhost/tecnostock/login.php` en el navegador.

### Credenciales de Prueba
* **Correo:** `admin@tecnostock.cl`
* **Contraseña:** `password`

---

## 4. Plan y Evidencia de Pruebas Ejecutadas

| ID | Caso de Prueba | Tipo | Datos de Entrada | Resultado Esperado y Verificado |
| :--- | :--- | :--- | :--- | :--- |
| **CP-01** | Inicio de sesión válido | Normal | `admin@tecnostock.cl` / `password` | Redirige a `index.php` e inicia sesión del encargado. |
| **CP-02** | Inicio de sesión inválido | Error | `admin@tecnostock.cl` / `123456` | Bloquea acceso y muestra alerta *"Credenciales incorrectas"*. |
| **CP-03** | Registro de producto válido | Normal | Código `TEC-099`, Precio `15990`, Stock `10`, Mín `3` | Inserta en BD y muestra confirmación en el catálogo. |
| **CP-04** | Código de producto duplicado | Error | Código existente (ej. `TEC-001`) | Detiene el guardado y alerta que el código ya está registrado. |
| **CP-05** | Valores negativos en formulario | Límite / Error | Precio `-500` o Cantidad `0` | Bloqueado por `validaciones.js` en cliente y por PHP en servidor. |
| **CP-06** | Salida mayor al stock disponible | Límite | Producto con stock `3`, Salida de `10` unidades | Transacción revertida (`rollBack`); muestra *"Stock insuficiente"*. |
| **CP-07** | Entrada válida y alerta de stock | Normal | Producto en alerta (`3 < 5`), Entrada de `10` unidades | Stock sube a `13`, desaparece el resaltado rojo de stock bajo. |
| **CP-08** | Desactivación lógica de producto | Normal | Clic en **Desactivar** sobre un producto con movimientos | Cambia `estado = 0` sin eliminar su historial en `movimiento`. |