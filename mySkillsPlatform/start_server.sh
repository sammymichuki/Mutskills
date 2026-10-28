#!/usr/bin/env bash
PORT=${1:-8000}
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

echo "========================================"
echo " Starting mutSkills Platform Server...  "
echo " URL: http://localhost:$PORT            "
echo "========================================"

php -c "$DIR/php.ini" -S "0.0.0.0:$PORT"
