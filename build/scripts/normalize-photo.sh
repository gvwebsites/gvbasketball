#!/usr/bin/env bash
# Normalize one photo to the GV "cool-neutral premium" look.
# Usage: normalize-photo.sh [--no-stretch] [--quality Q] <input> <output.webp>
# Tunables land the image on: median luma ~48-55%, +~8% contrast,
# ~90% saturation, neutral/slightly-cool white balance, no sepia.
set -euo pipefail

NO_STRETCH=0
QUALITY=82

while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --no-stretch) NO_STRETCH=1; shift ;;
    --quality) QUALITY="$2"; shift 2 ;;
    -*) echo "Unknown option: $1" >&2; exit 1 ;;
    *) break ;;
  esac
done

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 [--no-stretch] [--quality Q] <input> <output.webp>" >&2
  exit 1
fi

IN="$1"
OUT="$2"
TMP="$(mktemp -t gvnorm).png"

if [ "$NO_STRETCH" -eq 1 ]; then
  # For dark/moody silhouettes, skip luma auto-stretching to prevent grey wash & blocky noise.
  magick "$IN" \
    -colorspace sRGB \
    -modulate 100,90,100 \
    -channel R -evaluate multiply 0.985 +channel \
    -channel B -evaluate multiply 1.02  +channel \
    -strip \
    "$TMP"
else
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
fi

cwebp -q "$QUALITY" -metadata none "$TMP" -o "$OUT" >/dev/null 2>&1
rm -f "$TMP"
echo "wrote $OUT"
