# Cine Sendera — Sistema de Ventas de Boletos

Aplicación web LAMP (Linux, Apache, MySQL, PHP 8) con panel admin, ventas staff, check-in digital, mapa interactivo de asientos, API REST, automatización Bash y hardening de seguridad.

## Funcionalidades

- **3 roles:** `cliente` (compra web), `vendedor` (venta en mostrador + check-in), `admin` (gestión + monitoreo)
- **Mapa de asientos interactivo:** 40 asientos por función (5 filas × 8 columnas), selección visual con AJAX
- **Descuento matiné:** 30% off si la compra se realiza antes de las 12:00
- **Cupones de descuento:** Códigos promocionales con límite de usos
- **Check-in digital:** Staff registra la entrada del cliente por ID de compra o desde el ticket
- **Panel admin:** CRUD de películas/funciones/staff/productos, monitoreo de servidor (CPU, RAM, disco, uptime, servicios), gestión de cupones, tabla de todas las funciones con boletos vendidos e ingresos
- **Dulcería:** Catálogo de productos con compra en línea y para llevar, entrega registrada por staff, productos editables y activables/desactivables desde admin
- **API REST:** 8 endpoints con autenticación Bearer
- **Transacciones SQL con FOR UPDATE:** Evita condiciones de carrera en compra y check-in
- **Limpieza automática:** Funciones expiradas se marcan automáticamente (cada carga + cron cada 15 min)

## Requisitos

- Ubuntu/Debian (o cualquier Linux con `apt`)
- Apache 2.4+
- PHP 8.x + extensiones `mysql`, `curl`, `mbstring`, `xml`
- MySQL 8.0+
- `ufw` para firewall

## Instalación Rápida

```bash
git clone <repo> cine
cd cine
sudo bash setup.sh
```

Esto instala todo automáticamente: Apache, PHP, MySQL, importa la BD, configura el firewall, el watchdog, los backups y las cuentas por defecto.

## Estructura del Proyecto

```
cine/
├── public/                        # DocumentRoot de Apache
│   ├── index.php                  # Cartelera (películas + funciones)
│   ├── login.php / register.php   # Autenticación
│   ├── compra.php                 # Compra con mapa de asientos
│   ├── obtener_asientos.php       # AJAX: estado de asientos (JSON)
│   ├── ticket.php                 # Ticket digital con check-in
│   ├── logout.php                 # Cierre de sesión
│   ├── admin.php                  # Dashboard admin + CRUD
│   ├── admin_pelicula_*.php       # Handlers de películas
│   ├── admin_producto_*.php       # CRUD de productos dulcería
│   ├── admin_funcion_handler.php  # Crear funciones
│   ├── admin_staff_handler.php    # Crear staff
│   ├── dulceria.php               # Tienda de dulcería
│   ├── dulceria_recibo.php        # Recibo de compra dulcería
│   ├── validar_boleto.php         # AJAX: valida boleto ID
│   ├── procesar_compra.php        # Bridge a src/compra/
│   ├── procesar_dulceria.php      # Bridge a src/dulceria/
│   ├── login_handler.php          # Bridge a src/auth/
│   ├── register_handler.php       # Bridge a src/auth/
│   ├── staff/
│   │   ├── vender.php             # Venta en mostrador
│   │   ├── checkin.php            # Búsqueda y registro check-in
│   │   ├── checkin_handler.php    # Procesa check-in
│   │   └── entregar_dulces_handler.php  # Marca entrega de dulcería
│   ├── api/
│   │   └── index.php              # Router REST
│   └── assets/
│       ├── css/style.css          # Tema oscuro + componentes
│       └── img/                   # Posters de películas
├── src/
│   ├── config/database.php        # Conexión PDO
│   ├── config/config.php.example  # Config template
│   ├── includes/
│   │   ├── session.php            # Sesión segura + helpers
│   │   └── functions.php          # CSRF, matiné, cupones, limpieza
│   ├── auth/
│   │   ├── register_handler.php   # Registro con bcrypt
│   │   └── login_handler.php      # Login con password_verify
│   ├── compra/
│   │   └── procesar_compra.php    # Transacción SQL con FOR UPDATE
│   ├── dulceria/
│   │   └── procesar_dulceria.php  # Transacción SQL dulcería
│   ├── api/
│   │   ├── response.php           # JSON output
│   │   ├── middleware.php          # Bearer token auth
│   │   ├── auth.php               # Register/login API
│   │   ├── peliculas.php          # Películas endpoint
│   │   ├── funciones.php          # Funciones endpoint
│   │   └── compras.php            # Compras endpoint
│   └── seed.php                   # Crea usuarios por defecto
├── sql/
│   └── schema.sql                 # DDL + procedimiento + datos
├── libs/
│   └── phpqrcode/                 # Librería QR (integrada)
├── scripts/
│   ├── watchdog.sh                # Auto-healing Apache/MySQL
│   ├── respaldo.sh                # Backup condicional de BD
│   ├── gestion_staff.sh           # Alta masiva de personal OS
│   ├── limpiar_funciones.php      # Marca funciones expiradas
│   ├── crontab.txt                # Tareas programadas
│   └── staff.csv                  # Ejemplo CSV
├── config/
│   ├── apache-cine.conf           # VirtualHost template
│   ├── sshd_config.example        # SSH hardening reference
│   └── ufw_rules.sh               # Reglas de firewall
├── docs/                          # Documentación detallada
├── setup.sh                       # Provisioning completo
└── README.md
```

## Cuentas por Defecto

| Usuario | Email | Contraseña | Rol |
|---|---|---|---|
| Admin | admin@cine.com | admin123 | admin |
| Staff | staff@cine.com | staff123 | vendedor |
| Cliente | carlos@email.com | cliente123 | cliente |

## API REST

Todas las rutas bajo `http://localhost/api/index.php/`.

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `GET` | `/peliculas` | ❌ | Lista películas |
| `GET` | `/peliculas/{id}` | ❌ | Película + funciones |
| `GET` | `/funciones/{id}` | ❌ | Función con matriz de asientos |
| `POST` | `/auth/register` | ❌ | Registro → token |
| `POST` | `/auth/login` | ❌ | Login → token |
| `POST` | `/compras` | ✅ | Comprar boletos |
| `GET` | `/compras` | ✅ | Mis compras |
| `GET` | `/compras/{id}` | ✅ | Detalle de compra |

## Scripts Disponibles

| Script | Propósito | Frecuencia |
|---|---|---|
| `setup.sh` | Provisiona todo el servidor | Una vez |
| `scripts/watchdog.sh` | Monitorea y reinicia Apache/MySQL | Cada 30s (systemd) |
| `scripts/respaldo.sh` | Backup de BD (>15% espacio libre) | Diario (cron) |
| `scripts/limpiar_funciones.php` | Marca funciones pasadas | Cada 15 min (cron) |
| `scripts/gestion_staff.sh` | Crea usuarios staff en el SO | Bajo demanda |

## Seguridad

- [x] Contraseñas con bcrypt cost=12
- [x] Prepared statements en todas las queries
- [x] Sesión con httponly + samesite=Strict
- [x] CSRF en compra y check-in
- [x] Firewall UFW (deny incoming por defecto)
- [x] SSH en puerto 2222 con rate-limit
- [x] Transacciones FOR UPDATE (race conditions)
- [x] Options -Indexes en Apache
- [x] Usuario BD mínimo privilegio
- [x] Backups con verificación de espacio
