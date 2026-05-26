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
│                        │                                     │
│  ┌─────────────────────▼──────────────────────────────────┐  │
│  │              src/ (Lógica de negocio)                   │  │
│  │  ┌──────────┐ ┌───────────────┐ ┌──────────────────┐   │  │
│  │  │  auth/   │ │  compra/      │ │  includes/       │   │  │
│  │  │ registro │ │ procesar_compra│ │ session, functions│   │  │
│  │  │ login    │ │ + transacción  │ │ + CSRF           │   │  │
│  │  └──────────┘ └───────────────┘ └──────────────────┘   │  │
│  └─────────────────────┬──────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │ PDO (localhost)
┌────────────────────────▼────────────────────────────────────┐
│                        MySQL                                 │
│  ┌───────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐  │
│  │ usuarios  │ │peliculas │ │ funciones│ │  asientos      │  │
│  │ (auth)    │ │(catálogo)│ │(horarios)│ │  (inventario)  │  │
│  └───────────┘ └──────────┘ └──────────┘ └───────────────┘  │
│  ┌───────────┐ ┌──────────────┐                              │
│  │  compras  │ │detalle_compra│                              │
│  │ (ventas)  │ │ (asientos x  │                              │
│  │           │ │  compra)     │                              │
│  └───────────┘ └──────────────┘                              │
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

### 2. Lógica de Negocio (`src/`)
PHP puro sin HTML. Procesa formularios, valida datos, interactúa con la BD.

### 3. Datos (`sql/`)
Esquema MySQL con tablas, relaciones y procedimiento almacenado.

### 4. Automatización (`scripts/`)
Scripts Bash para operación del servidor.

### 5. Seguridad
- UFW: firewall con política DROP por defecto
- Rate-limiting SSH con `ufw limit`
- Contraseñas hasheadas con bcrypt

## Flujo de Compra

```
1. Usuario ve cartelera       → index.php (SELECT peliculas)
2. Selecciona película        → compra.php?pelicula_id=X
3. Ve función + asientos libres → consulta MySQL COUNT(asientos WHERE disponible=1)
4. Ingresa cantidad           → POST a procesar_compra.php
5. Transacción SQL:
   a. BEGIN TRANSACTION
   b. SELECT ... FOR UPDATE (bloquea filas)
   c. UPDATE asientos SET disponible=0
   d. INSERT INTO compras + detalle_compra
   e. COMMIT
6. Muestra ticket             → ticket.php?compra_id=X
```
