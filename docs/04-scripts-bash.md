# Scripts Bash

## 1. Watchdog — `scripts/watchdog.sh`

**Propósito:** Monitoreo constante de Apache y MySQL con auto-reinicio.

### Comportamiento

```
Cada 30 segundos:
  ├── ¿Apache corriendo? → No → reiniciar + log
  ├── ¿MySQL corriendo?  → No → reiniciar + log
  └── Todo bien → esperar 30s
```

### Instalación como servicio systemd

El `setup.sh` instala este script como un servicio systemd:

```bash
systemctl status cine-watchdog    # Ver estado
journalctl -u cine-watchdog -f    # Ver logs en vivo
```

### Formato del Log (`/var/log/cine_error.log`)

```
[2026-05-25 14:30:01] WATCHDOG: Apache caído — intentando reiniciar...
[2026-05-25 14:30:03] WATCHDOG: Apache reiniciado exitosamente.
```

### Ejecución manual

```bash
sudo ./scripts/watchdog.sh &      # Fondo manual
sudo pkill -f watchdog.sh          # Detener
```

---

## 2. Respaldo — `scripts/respaldo.sh`

**Propósito:** Backup de la base de datos `cine` con verificación de espacio.

### Flujo

```
1. Obtener % de uso del disco (df)
2. ¿Disponible > 15%?
   ├── Sí → mysqldump → /var/backups/cine/cine_YYYYMMDD_HHMMSS.sql
   └── No  → log error + exit 1
```

### Programación con cron

```bash
# Respaldo diario a las 3:00 AM
0 3 * * * /usr/local/bin/respaldo.sh
```

El `setup.sh` instala una copia en `/etc/cron.daily/` para ejecución automática diaria.

### Ver logs de respaldo

```bash
grep "RESPALDO" /var/log/cine_error.log
```

### Restaurar un backup

```bash
mysql -u cine_user -p cine < /var/backups/cine/cine_20260525_030000.sql
```

---

## 3. Gestión de Personal — `scripts/gestion_staff.sh`

**Propósito:** Alta masiva de usuarios del staff (vendedores, técnicos, admin) en el sistema operativo.

### Formato del archivo CSV

```csv
nombre,rol
juan.perez,vendedor
maria.lopez,tecnico
carlos.garcia,vendedor
ana.martinez,admin
```

### Uso

```bash
sudo ./scripts/gestion_staff.sh scripts/staff.csv
```

### Lo que hace

1. Lee cada línea del CSV (salta el encabezado)
2. Valida que el rol sea `vendedor`, `tecnico` o `admin`
3. Crea el usuario con:
   - Home en `/home/cine/{rol}s/{nombre}`
   - Shell `/bin/bash`
   - Grupo secundario según el rol
4. Si el usuario ya existe, lo salta (no duplica)

### Estructura de directorios generada

```
/home/cine/
├── vendedores/
│   ├── juan.perez/
│   └── carlos.garcia/
├── tecnicos/
│   └── maria.lopez/
└── admins/
    └── ana.martinez/
```
