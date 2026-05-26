#!/usr/bin/env bash
#
# Script: respaldo.sh
# Descripción: Respaldo de la base de datos 'cine'.
#             Solo procede si el almacenamiento disponible es > 15%.
#
# Uso: ./respaldo.sh
#

LOG="/var/log/cine_error.log"
BACKUP_DIR="/var/backups/cine"
DB_NAME="cine"
DB_USER="cine_user"
DB_PASS="cine_pass"

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

# Obtener % de uso del disco donde está el backup
USO=$(df "$BACKUP_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
DISPONIBLE=$((100 - USO))

echo "[$(date '+%Y-%m-%d %H:%M:%S')] RESPALDO: Uso del disco = ${USO}% (disponible = ${DISPONIBLE}%)" >> "$LOG"

if [ "$DISPONIBLE" -gt 15 ]; then
    FILENAME="${BACKUP_DIR}/cine_$(date '+%Y%m%d_%H%M%S').sql"
    if mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$FILENAME" 2>> "$LOG"; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] RESPALDO: Backup exitoso → $FILENAME" >> "$LOG"
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] RESPALDO: ERROR — mysqldump falló." >> "$LOG"
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] RESPALDO: ERROR — Espacio insuficiente (${DISPONIBLE}% libre). Backup omitido." >> "$LOG"
    exit 1
fi
