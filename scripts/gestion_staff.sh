#!/usr/bin/env bash
#
# Script: gestion_staff.sh
# Descripción: Da de alta usuarios del staff (vendedores, técnicos)
#             en el sistema operativo con directorios específicos.
#
# Uso: sudo ./gestion_staff.sh staff.csv
#
# Formato del CSV:
#   nombre,rol
#   juan.perez,vendedor
#   maria.lopez,tecnico
#
# Los roles válidos son: vendedor, tecnico, admin
#

CSV="${1:-staff.csv}"
BASE_DIR="/home/cine"

if [ ! -f "$CSV" ]; then
    echo "Error: No se encuentra el archivo $CSV"
    echo "Uso: $0 <archivo.csv>"
    exit 1
fi

# Verificar que se ejecute como root
if [ "$EUID" -ne 0 ]; then
    echo "Error: Ejecuta con sudo o como root."
    exit 1
fi

while IFS=',' read -r nombre rol; do
    # Saltar encabezado
    [[ "$nombre" == "nombre" ]] && continue

    nombre=$(echo "$nombre" | xargs)
    rol=$(echo "$rol" | xargs)

    if [ -z "$nombre" ] || [ -z "$rol" ]; then
        echo "Línea inválida: $nombre,$rol — saltando."
        continue
    fi

    case "$rol" in
        vendedor|tecnico|admin)
            ;;
        *)
            echo "Rol desconocido '$rol' para $nombre — saltando."
            continue
            ;;
    esac

    USER_HOME="${BASE_DIR}/${rol}s/${nombre}"

    if id "$nombre" &>/dev/null; then
        echo "Usuario $nombre ya existe — saltando."
        continue
    fi

    useradd -m -d "$USER_HOME" -s /bin/bash -G "$rol" "$nombre"
    echo "✓ Creado: $nombre (rol: $rol, home: $USER_HOME)"
done < "$CSV"

echo "Proceso completado."
