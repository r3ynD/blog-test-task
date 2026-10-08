#!/bin/sh

set -eu

base_url="${1:-http://localhost:8080}"

curl -fsS --retry 10 --retry-connrefused --retry-delay 1 --max-time 5 -o /dev/null "$base_url/"

test "$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/")" = 200
test "$(curl -sS -I -o /dev/null -w '%{http_code}' "$base_url/")" = 200
test "$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/missing")" = 404
test "$(curl -sS -X POST -o /dev/null -w '%{http_code}' "$base_url/")" = 405
test "$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/other.php")" = 404
test "$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/.env")" = 404
test "$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/assets/app.css")" = 200
test "$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/assets/theme.js")" = 200

printf 'HTTP smoke checks passed.\n'
