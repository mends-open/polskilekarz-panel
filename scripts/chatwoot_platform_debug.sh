#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 2 ]]; then
    cat >&2 <<'USAGE'
Usage: CHATWOOT_ENDPOINT=... CHATWOOT_API_ACCESS_TOKEN=... \\
       ./scripts/chatwoot_platform_debug.sh <account_id> <user_id> [conversation_id]
USAGE
    exit 1
fi

: "${CHATWOOT_ENDPOINT:?CHATWOOT_ENDPOINT must be set}"
: "${CHATWOOT_API_ACCESS_TOKEN:?CHATWOOT_API_ACCESS_TOKEN must be set}"

ACCOUNT_ID=$1
USER_ID=$2
CONVERSATION_ID=${3:-}

BASE_URL=${CHATWOOT_ENDPOINT%/}
API_TOKEN=${CHATWOOT_API_ACCESS_TOKEN}

platform_headers=(
    -H "Accept: application/json"
    -H "Content-Type: application/json"
    -H "api_access_token: ${API_TOKEN}"
)

call() {
    local label=$1
    shift
    echo
    echo "### ${label}"
    printf '+'
    for arg in "$@"; do
        printf ' %q' "$arg"
    done
    echo
    "$@"
    echo
}

call "GET platform user" \
    curl -sS -D - "${platform_headers[@]}" \
    "${BASE_URL}/platform/api/v1/users/${USER_ID}"

call "GET account-scoped user" \
    curl -sS -D - "${platform_headers[@]}" \
    "${BASE_URL}/platform/api/v1/accounts/${ACCOUNT_ID}/users/${USER_ID}"

login_payload=$(printf '{"account_id":%d}\n' "${ACCOUNT_ID}")

call "POST global login" \
    curl -sS -D - "${platform_headers[@]}" \
    -d "${login_payload}" \
    "${BASE_URL}/platform/api/v1/users/${USER_ID}/login"

call "POST account login" \
    curl -sS -D - "${platform_headers[@]}" \
    -d '{}' \
    "${BASE_URL}/platform/api/v1/accounts/${ACCOUNT_ID}/users/${USER_ID}/login"

if [[ -n ${CONVERSATION_ID} ]]; then
    cat <<'NOTE'

# To send a message once you have an access token, run:
#   curl -sS -D - -H "Accept: application/json" \
#       -H "Content-Type: application/json" \
#       -H "api_access_token: ${CHATWOOT_API_ACCESS_TOKEN}" \
#       -H "Authorization: Bearer <USER_ACCESS_TOKEN>" \
#       -d '{"content":"Test private reply","private":true}' \
#       "${CHATWOOT_ENDPOINT%/}/api/v1/accounts/${ACCOUNT_ID}/conversations/${CONVERSATION_ID}/messages"
# Replace <USER_ACCESS_TOKEN> with the value returned by one of the login calls above.
NOTE
fi
