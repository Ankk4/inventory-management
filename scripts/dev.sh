#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

WIFI=0
UFW_OPENED=0
UFW_COMMENT="inventory-dev-wifi"
LAN_IP=""

usage() {
    cat <<'EOF'
Usage:
  composer run dev
  composer run dev:wifi
  composer run dev -- --wifi

Composer treats unknown flags as its own options, so `composer run dev --wifi`
does not work. Use `dev:wifi`, or pass `--wifi` after `--`.

dev        Bind Laravel and Vite to localhost only.
dev:wifi   Bind them on the LAN, point Vite HMR at this machine's IP, and
           temporarily allow TCP 8000 and 5173 in UFW when it is active.
EOF
}

for arg in "$@"; do
    case "$arg" in
        --wifi|--lan)
            WIFI=1
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $arg" >&2
            usage >&2
            exit 1
            ;;
    esac
done

lan_ip() {
    local ip=""

    if command -v ip >/dev/null 2>&1; then
        ip="$(ip -4 route get 1.1.1.1 2>/dev/null | awk '{
            for (i = 1; i <= NF; i++) {
                if ($i == "src") {
                    print $(i + 1)
                    exit
                }
            }
        }')"
    fi

    if [[ -z "$ip" ]] && command -v hostname >/dev/null 2>&1; then
        ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
    fi

    printf '%s\n' "$ip"
}

lan_subnet() {
    local ip="$1"
    local a b c

    IFS=. read -r a b c _ <<< "$ip"
    printf '%s.%s.%s.0/24\n' "$a" "$b" "$c"
}

ufw_delete_wifi_rules() {
    local numbers number

    mapfile -t numbers < <(
        sudo ufw status numbered 2>/dev/null | awk -v comment="$UFW_COMMENT" '
            index($0, comment) {
                if (match($0, /\[[[:space:]]*[0-9]+\]/)) {
                    n = substr($0, RSTART, RLENGTH)
                    gsub(/[^0-9]/, "", n)
                    print n
                }
            }
        ' | sort -nr
    )

    if [[ ${#numbers[@]} -eq 0 ]]; then
        return 0
    fi

    for number in "${numbers[@]}"; do
        sudo ufw --force delete "$number" >/dev/null
    done
}

open_firewall() {
    local subnet="$1"

    if ! command -v ufw >/dev/null 2>&1; then
        return 0
    fi

    if ! sudo ufw status 2>/dev/null | grep -q "Status: active"; then
        echo "UFW is not active; skipped opening ports."
        return 0
    fi

    echo "Allowing LAN access to ports 8000 and 5173 from ${subnet} (UFW)."
    sudo ufw allow from "$subnet" to any port 8000 proto tcp comment "$UFW_COMMENT" >/dev/null || return 1
    UFW_OPENED=1
    sudo ufw allow from "$subnet" to any port 5173 proto tcp comment "$UFW_COMMENT" >/dev/null || return 1
}

cleanup() {
    if [[ "$UFW_OPENED" -eq 1 ]]; then
        echo "Removing temporary UFW rules (${UFW_COMMENT})."
        ufw_delete_wifi_rules || true
    fi
}

if [[ "$WIFI" -eq 1 ]]; then
    LAN_IP="$(lan_ip)"

    if [[ -z "$LAN_IP" ]]; then
        echo "Could not detect a LAN IPv4 address. Binding on 0.0.0.0 anyway." >&2
        LAN_IP="0.0.0.0"
    fi

    export DEV_WIFI=1
    export VITE_HMR_HOST="$LAN_IP"

    trap cleanup EXIT INT TERM

    echo
    echo "Phone / LAN:  http://${LAN_IP}:8000"
    echo "Vite HMR:     http://${LAN_IP}:5173"
    echo "Localhost:    http://127.0.0.1:8000"
    echo

    if [[ "$LAN_IP" != "0.0.0.0" ]]; then
        open_firewall "$(lan_subnet "$LAN_IP")" || {
            echo "Could not update UFW. If the phone cannot connect, allow TCP 8000 and 5173 from your LAN, e.g.:" >&2
            echo "  sudo ufw allow from $(lan_subnet "$LAN_IP") to any port 8000 proto tcp" >&2
            echo "  sudo ufw allow from $(lan_subnet "$LAN_IP") to any port 5173 proto tcp" >&2
        }
    fi
fi

if [[ "$WIFI" -eq 1 ]]; then
    serve_cmd="php artisan serve --host=0.0.0.0 --port=8000 --tries=1"
else
    serve_cmd="php artisan serve"
fi

npx concurrently \
    -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
    --names=server,queue,logs,vite \
    --kill-others \
    "$serve_cmd" \
    "php artisan queue:listen --tries=1 --timeout=0" \
    "php artisan pail --timeout=0" \
    "npm run dev"
