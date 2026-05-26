#!/usr/bin/env bash
#
# Script: watchdog.sh
# Descripción: Monitorea Apache y MySQL. Si algún servicio cae, lo reinicia
#             y registra el evento en /var/log/cine_error.log.
#
# Uso: sudo ./watchdog.sh &
# O mejor: instalarlo como servicio systemd (ver setup.sh).
#

LOG="/var/log/cine_error.log"
SLEEP_INTERVAL=30

while true; do
    # -- Apache2 --
    if ! systemctl is-active --quiet apache2 2>/dev/null; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] WATCHDOG: Apache caído — intentando reiniciar..." >> "$LOG"
        systemctl restart apache2 2>/dev/null
        sleep 2
        if systemctl is-active --quiet apache2 2>/dev/null; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] WATCHDOG: Apache reiniciado exitosamente." >> "$LOG"
        else
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] WATCHDOG: ERROR — No se pudo reiniciar Apache." >> "$LOG"
        fi
    fi

    # -- MySQL --
    if ! systemctl is-active --quiet mysql 2>/dev/null; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] WATCHDOG: MySQL caído — intentando reiniciar..." >> "$LOG"
        systemctl restart mysql 2>/dev/null
        sleep 2
        if systemctl is-active --quiet mysql 2>/dev/null; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] WATCHDOG: MySQL reiniciado exitosamente." >> "$LOG"
        else
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] WATCHDOG: ERROR — No se pudo reiniciar MySQL." >> "$LOG"
        fi
    fi

    sleep "$SLEEP_INTERVAL"
done
