# 🎬 Cine — Sistema de Ventas de Boletos

Aplicación web LAMP (Linux, Apache, MySQL, PHP) con API REST, automatización Bash y hardening de seguridad.

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

Esto instala todo automáticamente: Apache, PHP, MySQL, importa la BD, configura el firewall, el watchdog y los backups.

## Instalación Manual Paso a Paso

### 1. Instalar dependencias

```bash
sudo apt-get update
sudo apt-get install -y apache2 php libapache2-mod-php php-mysql \
    php-curl php-mbstring php-xml mysql-server mysql-client ufw
```

### 2. Configurar base de datos

```bash
# Importar esquema con tablas, datos de prueba y procedimiento almacenado
sudo mysql < sql/schema.sql

# Crear usuario de aplicación
mysql -u root -e "
  CREATE USER 'cine_user'@'localhost' IDENTIFIED BY 'cine_pass';
  GRANT ALL PRIVILEGES ON cine.* TO 'cine_user'@'localhost';
  FLUSH PRIVILEGES;
"

# Generar asientos (40 por función)
for fid in $(mysql -N -e 'SELECT id FROM cine.funciones'); do
  mysql -e "CALL cine.generar_asientos($fid)"
done
```

### 3. Configurar Apache

```bash
sudo tee /etc/apache2/sites-available/cine.conf > /dev/null <<'APACHE'
<VirtualHost *:80>
    DocumentRoot /home/gabyyy/cine/public
    <Directory /home/gabyyy/cine/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/cine_error.log
    CustomLog ${APACHE_LOG_DIR}/cine_access.log combined
</VirtualHost>
APACHE

sudo a2dissite 000-default.conf
sudo a2ensite cine.conf
sudo systemctl restart apache2
```

### 4. Firewall (UFW)

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 80/tcp
sudo ufw allow 2222/tcp
sudo ufw limit 2222/tcp
sudo ufw --force enable
```

---

## API REST

Todas las rutas bajo `http://localhost/api/index.php/`.

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `GET` | `/peliculas` | ❌ | Lista todas las películas |
| `GET` | `/peliculas/{id}` | ❌ | Película con funciones y asientos libres |
| `GET` | `/funciones/{id}` | ❌ | Función con matriz de 40 asientos |
| `POST` | `/auth/register` | ❌ | Registro: `{nombre, email, password}` → token |
| `POST` | `/auth/login` | ❌ | Login: `{email, password}` → token |
| `POST` | `/compras` | ✅ | Comprar: `{funcion_id, cantidad}` |
| `GET` | `/compras` | ✅ | Compras del usuario autenticado |
| `GET` | `/compras/{id}` | ✅ | Detalle de una compra |

### Ejemplos de uso

```bash
# Registrar usuario
curl -s -X POST http://localhost/api/index.php/auth/register \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Juan","email":"juan@mail.com","password":"secreta"}'

# Iniciar sesión y guardar token
TOKEN=$(curl -s -X POST http://localhost/api/index.php/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@mail.com","password":"secreta"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# Comprar 2 boletos
curl -s -X POST http://localhost/api/index.php/compras \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"funcion_id":1,"cantidad":2}'

# Ver compras
curl -s http://localhost/api/index.php/compras \
  -H "Authorization: Bearer $TOKEN"
```

---

## Guía de Implementación

### I. Desarrollo Web (Pila LAMP)

#### Registro y Autenticación

- **`public/register.php`** — Formulario con validación HTML5 (`required`, `minlength`).
- **`src/auth/register_handler.php`** — Procesa el POST: sanitiza entradas, valida email con `filter_var()`, verifica duplicados, hashea con `password_hash(PASSWORD_BCRYPT, ['cost' => 12])` e inicia sesión.
- **`public/login.php`** — Formulario de inicio de sesión.
- **`src/auth/login_handler.php`** — Busca usuario por email, verifica con `password_verify()`, regenera ID de sesión con `session_regenerate_id(true)` para prevenir session fixation.
- **`src/includes/session.php`** — Sesión configurada con `httponly`, `samesite=Strict`, y `use_strict_mode`.
- **`src/includes/functions.php`** — Token CSRF con `random_bytes(32)` + `hash_equals()`, helper `h()` para escapar HTML.

#### Cartelera

- **`public/index.php`** — Consulta `SELECT * FROM peliculas` y renderiza cards con poster, título, sinopsis, precio y enlace a compra.
- Las imágenes de los posters se almacenan en `public/assets/img/`.

#### Módulo de Compra

- **`public/compra.php`** — Muestra el detalle de la película, un `<select>` con funciones disponibles y asientos libres (`COUNT(asientos WHERE disponible=TRUE)`), y un input para cantidad de boletos.
- **`src/compra/procesar_compra.php`** — Ejecuta una **transacción SQL**:
  1. `BEGIN TRANSACTION`
  2. `SELECT ... FOR UPDATE` sobre la función (bloquea filas)
  3. Toma `N` asientos disponibles con `LIMIT ? FOR UPDATE`
  4. `UPDATE asientos SET disponible = FALSE`
  5. `INSERT INTO compras` y `detalle_compra`
  6. `COMMIT`
  - Si algo falla: `ROLLBACK` + mensaje de error.
  - `FOR UPDATE` evita condiciones de carrera (dos usuarios comprando el mismo asiento simultáneamente).
- **`public/ticket.php`** — Muestra el ticket digital con datos de la compra, película, horario, sala, asientos y total. Solo accesible por el comprador.

### II. Automatización con Bash

#### Watchdog (`scripts/watchdog.sh`)

- Bucle infinito cada 30 segundos.
- Verifica `systemctl is-active --quiet apache2` y `mysql`.
- Si algún servicio está caído: lo reinicia y registra en `/var/log/cine_error.log`.
- Se instala como servicio systemd (`cine-watchdog.service`) desde `setup.sh`.

#### Respaldo (`scripts/respaldo.sh`)

- Obtiene el porcentaje de uso del disco con `df`.
- Si el espacio disponible es **mayor al 15%**: ejecuta `mysqldump` y guarda en `/var/backups/cine/`.
- Si no: registra error y sale con código 1.
- Se instala en `/etc/cron.daily/` para ejecución automática diaria.

#### Gestión de Personal (`scripts/gestion_staff.sh`)

- Lee un archivo CSV con formato `nombre,rol`.
- Roles válidos: `vendedor`, `tecnico`, `admin`.
- Crea usuarios en el sistema con `useradd -m -d /home/cine/{rol}s/{nombre}`.
- Asigna grupo según el rol.
- Omite usuarios ya existentes.

### III. Hardening y Seguridad

| Capa | Implementación |
|---|---|
| **Firewall** | `ufw default deny incoming`, solo puertos 80 (HTTP) y 2222 (SSH) abiertos |
| **Rate-limit SSH** | `ufw limit 2222/tcp` — máximo 6 conexiones por 30 segundos |
| **Contraseñas** | `password_hash(PASSWORD_BCRYPT, cost=12)` — nunca en texto plano |
| **SQL Injection** | Prepared statements con PDO en todas las consultas |
| **Sesiones** | `httponly`, `samesite=Strict`, `session_regenerate_id()` en login |
| **CSRF** | Token de 32 bytes aleatorios verificado con `hash_equals()` |
| **Listado de directorios** | `Options -Indexes` en Apache |
| **Mínimo privilegio** | Usuario BD `cine_user` con acceso solo a `cine.*` desde localhost |

---

## Estructura del Proyecto

```
cine/
├── public/                        # DocumentRoot de Apache
│   ├── index.php                  # Cartelera (3 películas)
│   ├── login.php / register.php   # Autenticación
│   ├── compra.php                 # Selección de función y boletos
│   ├── ticket.php                 # Ticket digital post-compra
│   ├── logout.php                 # Cierre de sesión
│   ├── api/
│   │   └── index.php              # Router de la API REST
│   └── assets/
│       ├── css/style.css          # Estilo oscuro
│       └── img/                   # Posters de películas
├── src/
│   ├── config/database.php        # Conexión PDO
│   ├── includes/
│   │   ├── session.php            # Sesión segura
│   │   └── functions.php          # CSRF, helpers
│   ├── auth/
│   │   ├── register_handler.php   # password_hash + insert
│   │   └── login_handler.php      # password_verify + sesión
│   ├── compra/
│   │   └── procesar_compra.php    # Transacción SQL
│   └── api/
│       ├── response.php           # JSON output helpers
│       ├── middleware.php          # Bearer token auth
│       ├── auth.php               # Register/login endpoint
│       ├── peliculas.php          # Películas endpoint
│       ├── funciones.php          # Funciones endpoint
│       └── compras.php            # Compras endpoint
├── sql/
│   └── schema.sql                 # DDL + procedimiento generar_asientos()
├── scripts/
│   ├── watchdog.sh                # Auto-healing Apache/MySQL
│   ├── respaldo.sh                # Backup condicional de BD
│   ├── gestion_staff.sh           # Alta masiva de personal
│   └── staff.csv                  # Ejemplo de plantilla
├── setup.sh                       # Provisioning completo
├── docs/                          # Documentación detallada
└── README.md
```

## Scripts Disponibles

| Script | Propósito | Frecuencia |
|---|---|---|
| `setup.sh` | Provisiona todo el servidor desde cero | Una vez |
| `scripts/watchdog.sh` | Monitorea y reinicia Apache/MySQL si caen | Cada 30s (systemd) |
| `scripts/respaldo.sh` | Backup de la BD si hay >15% de espacio libre | Diario (cron) |
| `scripts/gestion_staff.sh` | Crea usuarios del staff en el SO | Bajo demanda |

## Seguridad — Checklist

- [ ] Cambiar contraseña por defecto de MySQL (`cine_pass`)
- [ ] Cambiar contraseña en `setup.sh` y `scripts/respaldo.sh`
- [ ] Configurar SSH para usar puerto 2222
- [ ] Deshabilitar login root por SSH
- [ ] Revisar logs periódicamente: `/var/log/cine_error.log`
- [ ] Mantener sistema actualizado: `sudo apt-get update && sudo apt-get upgrade`
