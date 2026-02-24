#!/bin/bash
set -e

echo "=== Cluster Init: Generating TLS certificates ==="

TLS_DIR="/tls"
mkdir -p "$TLS_DIR"

# Generate CA key and cert
openssl genrsa -out "$TLS_DIR/ca.key" 2048 2>/dev/null
openssl req -new -x509 -days 365 -key "$TLS_DIR/ca.key" \
    -out "$TLS_DIR/ca.crt" -subj "/CN=Valkey-Test-CA" 2>/dev/null

# Generate server key
openssl genrsa -out "$TLS_DIR/server.key" 2048 2>/dev/null

# Generate server CSR with SAN covering all TLS node IPs and loopback
cat > "$TLS_DIR/san.cnf" <<EOF
[req]
distinguished_name = req_dn
req_extensions = v3_req
prompt = no

[req_dn]
CN = valkey-tls

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = valkey-tls-standalone
IP.1 = 10.100.0.21
IP.2 = 10.100.0.22
IP.3 = 10.100.0.23
IP.4 = 127.0.0.1
IP.5 = 10.100.0.40
EOF

openssl req -new -key "$TLS_DIR/server.key" \
    -out "$TLS_DIR/server.csr" -config "$TLS_DIR/san.cnf" 2>/dev/null

openssl x509 -req -in "$TLS_DIR/server.csr" \
    -CA "$TLS_DIR/ca.crt" -CAkey "$TLS_DIR/ca.key" -CAcreateserial \
    -out "$TLS_DIR/server.crt" -days 365 \
    -extensions v3_req -extfile "$TLS_DIR/san.cnf" 2>/dev/null

# Make certs readable by valkey user
chmod 644 "$TLS_DIR"/*.crt "$TLS_DIR"/*.key

echo "=== TLS certificates generated ==="

# Signal that TLS certs are ready
touch "$TLS_DIR/.ready"

echo "=== Waiting for non-TLS cluster nodes ==="
for node in 10.100.0.11:7001 10.100.0.12:7002 10.100.0.13:7003 10.100.0.14:7004 10.100.0.15:7005 10.100.0.16:7006; do
    host="${node%:*}"
    port="${node#*:}"
    until valkey-cli -h "$host" -p "$port" ping 2>/dev/null | grep -q PONG; do
        echo "  Waiting for $node..."
        sleep 1
    done
    echo "  $node is ready"
done

echo "=== Creating non-TLS cluster (3 primaries + 3 replicas) ==="
valkey-cli --cluster create \
    10.100.0.11:7001 10.100.0.12:7002 10.100.0.13:7003 \
    10.100.0.14:7004 10.100.0.15:7005 10.100.0.16:7006 \
    --cluster-replicas 1 --cluster-yes

echo "=== Non-TLS cluster created ==="

echo "=== Waiting for TLS cluster nodes ==="
for node in 10.100.0.21:7011 10.100.0.22:7012 10.100.0.23:7013; do
    host="${node%:*}"
    port="${node#*:}"
    until valkey-cli --tls --cacert "$TLS_DIR/ca.crt" \
        -h "$host" -p "$port" ping 2>/dev/null | grep -q PONG; do
        echo "  Waiting for $node (TLS)..."
        sleep 1
    done
    echo "  $node (TLS) is ready"
done

echo "=== Creating TLS cluster ==="
valkey-cli --tls --cacert "$TLS_DIR/ca.crt" --cluster create \
    10.100.0.21:7011 10.100.0.22:7012 10.100.0.23:7013 \
    --cluster-replicas 0 --cluster-yes

echo "=== TLS cluster created ==="

echo "=== Waiting for auth cluster nodes ==="
for node in 10.100.0.31:7031 10.100.0.32:7032 10.100.0.33:7033; do
    host="${node%:*}"
    port="${node#*:}"
    until valkey-cli -a dummy_password -h "$host" -p "$port" ping 2>/dev/null | grep -q PONG; do
        echo "  Waiting for $node (auth)..."
        sleep 1
    done
    echo "  $node (auth) is ready"
done

echo "=== Creating auth cluster ==="
valkey-cli -a dummy_password --cluster create \
    10.100.0.31:7031 10.100.0.32:7032 10.100.0.33:7033 \
    --cluster-replicas 0 --cluster-yes

echo "=== Auth cluster created ==="
echo "=== All clusters initialized successfully ==="
