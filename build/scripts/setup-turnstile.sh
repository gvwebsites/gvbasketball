#!/usr/bin/env bash
# Provision a Cloudflare Turnstile (managed) widget for gvbasketball.com and
# store the keys: into local .env and as wp-config constants on the server.
# Never prints the secret. Idempotent: reuses an existing widget for the domain.
set -euo pipefail
cd "$(dirname "$0")/../.."            # repo root
set -a; . ./.env; set +a

API="https://api.cloudflare.com/client/v4/accounts/${CLOUDFLARE_ACCOUNT_ID}/challenges/widgets"
AUTH=(-H "Authorization: Bearer ${CLOUDFLARE_API_TOKEN}" -H "Content-Type: application/json")

# Reuse an existing widget for the domain if present, else create one.
EXISTING=$(curl -s "${API}" "${AUTH[@]}" \
  | python3 -c "import sys,json;[print(w['sitekey']) for w in (json.load(sys.stdin).get('result') or []) if 'gvbasketball.com' in (w.get('domains') or [])]" | head -n1)

if [ -n "${EXISTING}" ]; then
  SITEKEY="${EXISTING}"
  SECRET=$(curl -s -X POST "${API}/${SITEKEY}/rotate_secret" "${AUTH[@]}" -d '{}' \
    | python3 -c "import sys,json;print(json.load(sys.stdin)['result']['secret'])")
else
  RESP=$(curl -s -X POST "${API}" "${AUTH[@]}" \
    -d '{"name":"GV Basketball — Request Training","domains":["gvbasketball.com"],"mode":"managed"}')
  SITEKEY=$(echo "${RESP}" | python3 -c "import sys,json;print(json.load(sys.stdin)['result']['sitekey'])")
  SECRET=$(echo "${RESP}"  | python3 -c "import sys,json;print(json.load(sys.stdin)['result']['secret'])")
fi

# Persist into local .env (replace if already present).
grep -v '^GV_TURNSTILE_' .env > .env.tmp || true
{ echo "GV_TURNSTILE_SITEKEY=${SITEKEY}"; echo "GV_TURNSTILE_SECRET=${SECRET}"; } >> .env.tmp
mv .env.tmp .env

# Set on server as wp-config constants (quiet = no echo of values).
WPROOT="/home/u907133977/domains/gvbasketball.com/public_html"
ssh gvweb "cd ${WPROOT} && wp config set GV_TURNSTILE_SITEKEY '${SITEKEY}' --quiet && wp config set GV_TURNSTILE_SECRET '${SECRET}' --quiet" \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable" || true

echo "sitekey=${SITEKEY}"
echo "secret stored (not shown)"
