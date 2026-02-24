#!/bin/bash
set -e

# Create TLS cert symlink so tests can find certs at the path they expect
# (tests reference valkey-glide/utils/tls_crts/ca.crt)
if [ -f /tls/ca.crt ]; then
    mkdir -p /app/valkey-glide/utils/tls_crts
    ln -sf /tls/ca.crt /app/valkey-glide/utils/tls_crts/ca.crt
fi

exec "$@"
