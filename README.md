# Cine Sendera

Plataforma web de taquilla digital para el centro comercial Sendera.
Sistema basado en pila LAMP con automatización Bash y seguridad perimetral.

## Estructura del Proyecto

```
cine/
├── public/              # Frontend (punto de entrada Apache)
│   ├── index.php        # Cartelera
│   ├── compra.php       # Compra de boletos
│   ├── ticket.php       # Ticket digital + QR
│   ├── admin.php        # Panel de administración
│   ├── login.php        # Inicio de sesión
│   ├── register.php     # Registro de usuarios
│   ├── logout.php       # Cerrar sesión
│   └── assets/          # CSS, imágenes
├── src/                 # Lógica de negocio (PHP)
│   ├── config/          # Conexión a BD
│   ├── auth/            # Handlers de login/registro
│   ├── compra/          # Procesar compra
│   └── includes/        # Funciones, sesión
├── sql/                 # Esquema de base de datos
├── scripts/             # Bash: watchdog, respaldo, staff
├── config/              # Apache, UFW, SSH configs
├── libs/                # QR code library
└── docs/                # Documentación de entrega
```

## Instalación Rápida

```bash
git clone <repo> /var/www/html/cine
cd /var/www/html/cine
sudo bash setup.sh
```

## Puertos

| Puerto | Servicio | Acceso     |
|--------|----------|------------|
| 80     | HTTP     | Público    |
| 2222   | SSH      | Admin      |

## Requisitos Cubiertos

- Registro y autenticación segura (bcrypt + sesiones)
- Cartelera con 3 películas y asientos desde MySQL
- Compra con transacciones SQL y bloqueo FOR UPDATE
- Ticket digital con código QR
- Cupones de descuento y precio matiné (-30% antes 12pm entre semana)
- Watchdog: monitoreo Apache/MySQL con auto-reinicio
- Backup condicional de BD (solo si disco > 15% libre)
- Gestión de personal (alta masiva desde CSV)
- Firewall UFW (política DROP, rate-limit SSH)
- Panel admin con estadísticas

## Créditos

Proyecto escolar — Administración de Sistemas Linux
