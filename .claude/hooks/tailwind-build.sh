#!/usr/bin/env bash
# Bouwt de Tailwind/Vite-assets opnieuw zodra er een Blade- of CSS-bestand van
# TripCrew is gewijzigd. Draait als async PostToolUse-hook, want de build duurt
# ruim een minuut op deze WSL/DrvFs-opstelling.
set -uo pipefail

PROJECT=/mnt/c/xampp/htdocs/tripcrew
payload=$(cat)

# Het hookpayload bevat het pad van het gewijzigde bestand (Write/Edit) of het
# commando dat het wijzigde (Bash). Beide vangen we met één grep af.
if ! grep -Eq '\.blade\.php|\.css' <<<"$payload"; then
  exit 0
fi
if ! grep -q 'tripcrew' <<<"$payload"; then
  exit 0
fi

cd "$PROJECT" || exit 0
npm run build >/tmp/tripcrew-vite-build.log 2>&1 \
  && echo '{"systemMessage":"Tailwind/Vite-assets opnieuw gebouwd."}' \
  || echo '{"systemMessage":"npm run build faalde — zie /tmp/tripcrew-vite-build.log"}'
