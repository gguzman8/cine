# Arquitectura del Sistema

## Vista General

El sistema Cine es una aplicación web basada en pila LAMP (Linux, Apache, MySQL, PHP) con scripts de automatización en Bash y hardening de seguridad perimetral.

```
┌─────────────────────────────────────────────────────────────┐
│                     Cliente (Navegador)                      │
└─────────────────────┬───────────────────────────────────────┘
                      │ HTTP (puerto 80)
┌─────────────────────▼───────────────────────────────────────┐
│                     Apache / PHP                             │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌─────────┐  │
│  │ index.php │  │ compra.php│  │ ticket.php│  │ login/  │  │
│  │ (cartelera)│  │ (compra)  │  │ (ticket)  │  │ register│  │
│  └───────────┘  └───────────┘  └───────────┘  └─────────┘  │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐               │
│  │ admin.php │  │ vender.php│  │checkin.php│               │
│  │ (admin)   │  │ (staff)   │  │ (staff)   │               │
│  └───────────┘  └───────────┘  └───────────┘               │
│                        │                                     │
│  ┌─────────────────────▼──────────────────────────────────┐  │
│  │              src/ (Lógica de negocio)                   │  │
│  │  ┌──────────┐ ┌───────────────┐ ┌──────────────────┐   │  │
│  │  │  auth/   │ │  compra/      │ │  includes/       │   │  │
│  │  │ registro │ │ procesar_compra│ │ session, functions│   │  │
│  │  │ login    │ │ + transacción  │ │ + CSRF          │   │  │
│  │  └──────────┘ └───────────────┘ └──────────────────┘   │  │
│  │  ┌──────────────────────────────────────────────────┐   │  │
│  │  │  api/ (REST, token Bearer)                       │   │  │
│  │  │  auth.php, peliculas.php, funciones.php,         │   │  │
│  │  │  compras.php, middleware.php, response.php       │   │  │
│  │  └──────────────────────────────────────────────────┘   │  │
│  └─────────────────────┬──────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │ PDO (localhost)
┌────────────────────────▼────────────────────────────────────┐
│                        MySQL                                 │
│  ┌───────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐  │
│  │ usuarios  │ │peliculas │ │ funciones│ │  asientos      │  │
│  │ (auth)    │ │(catálogo)│ │(horarios)│ │  (inventario)  │  │
│  └───────────┘ └──────────┘ └──────────┘ └───────────────┘  │
│  ┌───────────┐ ┌──────────────┐ ┌───────────┐               │
│  │  compras  │ │detalle_compra│ │  cupones  │               │
│  │ (ventas)  │ │ (asientos x  │ │(descuentos)│              │
│  │           │ │  compra)     │ └───────────┘               │
│  └───────────┘ └──────────────┘                             │
└──────────────────────────────────────────────────────────────┘
         ▲                            ▲
         │                            │
┌────────▼────────┐     ┌────────────▼────────────┐
│  watchdog.sh    │     │   respaldo.sh (cron)     │
│  (systemd)      │     │   mysqldump condicional  │
└─────────────────┘     └─────────────────────────┘
```

## Capas del Sistema

### 1. Presentación (`public/`)
Archivos PHP con HTML/CSS. Punto de entrada del usuario.

| Sección | Archivo(s) | Rol |
|---|---|---|
| Cartelera pública | `index.php` | Todos (público) |
| Compra de boletos | `compra.php` | cliente, vendedor |
| Ticket digital | `ticket.php` | Autenticados (dueño o staff) |
| Admin panel | `admin.php` | admin |
| Venta (staff) | `staff/vender.php` | admin, vendedor |
| Check-in | `staff/checkin.php` | admin, vendedor |
| REST API | `api/index.php` | Token Bearer |

### 2. Lógica de Negocio (`src/`)
PHP puro sin HTML. Procesa formularios, valida datos, interactúa con la BD.

### 3. Datos (`sql/`)
Esquema MySQL con tablas, relaciones, procedimiento almacenado y datos de prueba.

### 4. Automatización (`scripts/`)
Scripts Bash para operación del servidor + PHP para limpieza de funciones expiradas.

### 5. Seguridad
- UFW: firewall con política DROP por defecto
- Rate-limiting SSH con `ufw limit`
- Contraseñas hasheadas con bcrypt cost=12
- Sesiones con httponly + samesite=Strict
- CSRF en formularios de compra y check-in
- Prepared statements en todas las consultas

## Roles del Sistema

| Rol | Acceso | Descripción |
|---|---|---|
| `cliente` | index, compra, ticket propio | Compra boletos en línea |
| `vendedor` | staff/vender, staff/checkin, compra | Vende boletos en mostrador, hace check-in |
| `admin` | admin.php, staff/vender, staff/checkin | Administra películas, funciones, usuarios, cupones; monitorea servidor |

## Flujo de Compra

```
1. Usuario ve cartelera       → index.php (SELECT peliculas + funciones)
2. Selecciona película        → compra.php?pelicula_id=X
3. Elige función + asientos   → mapa interactivo (AJAX obtener_asientos.php)
4. Ingresa datos (cupón, nombre para staff) → POST a procesar_compra.php
5. Transacción SQL:
   a. BEGIN TRANSACTION
   b. SELECT ... FOR UPDATE (bloquea filas)
   c. UPDATE asientos SET disponible=0
   d. INSERT INTO compras + detalle_compra
   e. COMMIT
6. Muestra ticket             → ticket.php?compra_id=X
```
