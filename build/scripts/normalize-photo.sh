#!/usr/bin/env bash
# Normalize one photo to the GV "cool-neutral premium" look.
# Usage: normalize-photo.sh <input> <output.webp>
# Tunables land the image on: median luma ~48-55%, +~8% contrast,
# ~90% saturation, neutral/slightly-cool white balance, no sepia.
set -euo pipefail

IN="$1"
OUT="$2"
TMP="$(mktemp -t gvnorm).png"

magick "$IN" \
  -colorspace sRGB \
  -auto-level \
  -auto-gamma \
  -modulate 108,90,100 \
  -sigmoidal-contrast 3x50% \
  -channel R -evaluate multiply 0.985 +channel \
  -channel B -evaluate multiply 1.02  +channel \
  -strip \
  "$TMP"

cwebp -q 82 -metadata none "$TMP" -o "$OUT" >/dev/null 2>&1
rm -f "$TMP"
echo "wrote $OUT"
