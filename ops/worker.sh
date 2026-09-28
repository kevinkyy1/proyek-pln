#!/usr/bin/env bash
# Worker queue untuk project pln.
#
# Penting: --memory=1024 karena default queue:work hanya 128 MB dan worker akan
# keluar sendiri (exit code 12) di tengah job chunk yang besar, sehingga job
# dianggap gagal setelah percobaan habis.
#
# Jalankan: bash ops/worker.sh
set -euo pipefail

PROYEK="${PROYEK:-/home/deck/Documents/pln}"
KONTAINER="${KONTAINER:-my-ubuntu}"

export PATH="$HOME/.local/bin:/usr/local/bin:/usr/bin:/bin"
export HOME="${HOME:-/home/deck}"

exec "$HOME/.local/bin/distrobox-enter" -n "$KONTAINER" -- bash -lc \
    "cd '$PROYEK' && exec php artisan queue:work --tries=3 --sleep=1 --memory=1024 --timeout=3600"
