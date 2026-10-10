#!/usr/bin/env bash
# Startet den eingebauten PHP-Webserver und fuehrt die Smoke-Tests aus.
# Die Datenbank muss bereits laufen (siehe .github/workflows/ci.yml).
set -euo pipefail

cd "$(dirname "$0")/.."

PORT="${PORT:-8000}"
export BASE_URL="http://127.0.0.1:${PORT}"
export PHP_ERROR_LOG="${PHP_ERROR_LOG:-$(mktemp)}"
: > "$PHP_ERROR_LOG"

php -d display_errors=1 -d error_reporting=E_ALL -d log_errors=1 -d error_log="$PHP_ERROR_LOG" \
	-S "127.0.0.1:${PORT}" >/dev/null 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null || true' EXIT

for _ in $(seq 1 50); do
	if php -r "exit(@fsockopen('127.0.0.1', ${PORT}) ? 0 : 1);"; then
		break
	fi
	sleep 0.1
done

php tests/smoke.php
