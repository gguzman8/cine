# Manual de Red — Cine Sendera

## Topología

```
                    ┌─────────────────────────┐
                    │    Internet / LAN       │
                    └────────┬────────────────┘
                             │
                    ┌────────▼─────────┐
                    │   Servidor Cine  │
                    │   Ubuntu Server  │
                    │   192.168.1.100  │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
     ┌────────▼────┐ ┌──────▼──────┐ ┌─────▼──────┐
     │  Apache 80  │ │  SSH 2222   │ │  MySQL 3306 │
     │  (pública)  │ │ (admin)     │ │ (local)     │
     └─────────────┘ └─────────────┘ └─────────────┘
```

## Tabla de Puertos

| Puerto | Servicio    | Protocolo | Acceso     | Descripción                     |
|--------|-------------|-----------|------------|---------------------------------|
| 80     | HTTP (Apache)| TCP      | Público    | Página web del cine             |
| 2222   | SSH          | TCP      | Admin      | Acceso remoto seguro            |
| 3306   | MySQL        | TCP      | Localhost  | Base de datos (no expuesta)     |

## Direcciones IP

| Equipo           | IP             | Rol                  |
|------------------|----------------|----------------------|
| Servidor Cine    | 192.168.1.100  | Apache + MySQL       |
| Cliente (admin)  | 192.168.1.x    | Administración SSH   |
| Cliente (usuario)| 192.168.1.x    | Navegador web        |

*Nota: Las IPs pueden variar según la asignación DHCP de la red del laboratorio.
Se recomienda usar IP fija en el servidor para consistencia.*

## Reglas de Firewall (UFW)

| Dirección | Acción  | Puerto   | Límite              |
|-----------|---------|----------|---------------------|
| Incoming  | DENY    | (todos)  | Política por defecto|
| Incoming  | ALLOW   | 80/tcp   | —                   |
| Incoming  | ALLOW   | 2222/tcp | —                   |
| Incoming  | LIMIT   | 2222/tcp | 6 conexiones/30s    |
| Outgoing  | ALLOW   | (todos)  | Política por defecto|

## Acceso a la Plataforma

- **URL Web:** `http://192.168.1.100` (o `http://cine.sendera.local` si está configurado DNS)
- **SSH:** `ssh -p 2222 usuario@192.168.1.100`
- **Admin panel:** `http://192.168.1.100/admin.php`
- **Staff ventas:** `http://192.168.1.100/staff/vender.php`
- **Staff check-in:** `http://192.168.1.100/staff/checkin.php`
- **API REST:** `http://192.168.1.100/api/index.php/`
- **phpMyAdmin:** `http://192.168.1.100/phpmyadmin/`

## Cuentas por Defecto

| Usuario  | Email             | Contraseña  | Rol       |
|----------|-------------------|-------------|-----------|
| Admin    | admin@cine.com    | admin123    | admin     |
| Staff    | staff@cine.com    | staff123    | vendedor  |
| Cliente  | carlos@email.com  | cliente123  | cliente   |

*Cambiar contraseñas en producción.*

## Estructura de URLs (Rutas Relativas)

| Ruta | Descripción | Roles permitidos |
|---|---|---|
| `/` | Cartelera pública | Todos |
| `/login.php` | Inicio de sesión | Todos |
| `/register.php` | Registro | Todos |
| `/compra.php` | Compra de boletos | cliente, vendedor |
| `/ticket.php` | Ticket digital | Dueño de compra o staff |
| `/admin.php` | Panel de administración | admin |
| `/staff/vender.php` | Venta en mostrador | admin, vendedor |
| `/staff/checkin.php` | Check-in de clientes | admin, vendedor |
| `/api/index.php/` | API REST | Token Bearer |
