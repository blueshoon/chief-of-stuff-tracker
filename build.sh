#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -d node_modules ]; then
  npm install --no-audit --no-fund
fi

npx tailwindcss \
  -c tailwind/tailwind.config.js \
  -i tailwind/input.css \
  -o public/assets/app.css \
  --minify

echo "Built public/assets/app.css"
