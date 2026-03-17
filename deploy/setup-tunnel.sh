#!/bin/bash
# ============================================================
# Internet35 Billing - Cloudflare Tunnel Setup
# Jalankan setelah deploy-app.sh
# ============================================================

set -e

# ---- KONFIGURASI (UBAH SESUAI KEBUTUHAN) ----
DOMAIN="billing.domain-anda.com"
TUNNEL_NAME="internet35"
# -----------------------------------------------

echo "============================================"
echo " Cloudflare Tunnel Setup"
echo "============================================"
echo ""

# 1. Login ke Cloudflare
echo "[1/4] Login ke Cloudflare..."
echo "  Browser akan terbuka untuk autentikasi."
echo "  Jika server tanpa GUI, copy URL yang muncul ke browser lokal."
echo ""
cloudflared tunnel login

# 2. Buat tunnel
echo ""
echo "[2/4] Membuat tunnel '${TUNNEL_NAME}'..."
cloudflared tunnel create ${TUNNEL_NAME}

# Dapatkan Tunnel ID
TUNNEL_ID=$(cloudflared tunnel list | grep ${TUNNEL_NAME} | awk '{print $1}')
echo "  Tunnel ID: ${TUNNEL_ID}"

# 3. Buat config file
echo "[3/4] Membuat konfigurasi tunnel..."
mkdir -p /etc/cloudflared

cat > /etc/cloudflared/config.yml << EOF
tunnel: ${TUNNEL_ID}
credentials-file: /root/.cloudflared/${TUNNEL_ID}.json

ingress:
  - hostname: ${DOMAIN}
    service: http://localhost:80
  - service: http_status:404
EOF

echo "  Config: /etc/cloudflared/config.yml"

# 4. Route DNS
echo "[4/4] Routing DNS ${DOMAIN} → tunnel..."
cloudflared tunnel route dns ${TUNNEL_NAME} ${DOMAIN}

# Install as system service
echo ""
echo "Installing cloudflared as service..."
cloudflared service install
systemctl enable --now cloudflared

echo ""
echo "============================================"
echo " Cloudflare Tunnel berhasil!"
echo "============================================"
echo ""
echo " Tunnel  : ${TUNNEL_NAME}"
echo " Domain  : https://${DOMAIN}"
echo " Status  : systemctl status cloudflared"
echo " Logs    : journalctl -u cloudflared -f"
echo ""
echo " DNS CNAME record otomatis dibuat:"
echo "   ${DOMAIN} → ${TUNNEL_ID}.cfargotunnel.com"
echo ""
echo " Test: curl https://${DOMAIN}/up"
echo ""
