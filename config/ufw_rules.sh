#!/usr/bin/env bash
#
# ufw_rules.sh — Configuración del firewall para el servidor Cine
#
# Uso: sudo bash ufw_rules.sh
#

set -euo pipefail

echo "Configurando UFW — Cine Sendera"

# Resetear a valores por defecto
ufw --force reset

# Política por defecto: DENY todo el tráfico entrante
ufw default deny incoming
ufw default allow outgoing

# Puerto web (Apache)
ufw allow 80/tcp comment "Web (HTTP)"

# Puerto personalizado para SSH (cambiar según configuración)
ufw allow 2222/tcp comment "SSH personalizado"

# Rate-limit en SSH para mitigar fuerza bruta
ufw limit 2222/tcp comment "Rate-limit SSH (6 conexiones/30s)"

# Permitir ICMP (ping) para diagnóstico — opcional
# ufw allow icmp

# Habilitar firewall
ufw --force enable

echo "=============================="
echo "  UFW configurado:"
ufw status verbose
echo "=============================="
