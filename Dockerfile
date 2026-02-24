# =============================================================================
# Stage 1: Build the valkey_glide PHP extension from source
# =============================================================================
# Clones the extension from GitHub and builds following DEVELOPER.md:
#   1. Install deps (PHP dev, Rust, protoc, protobuf-c, cbindgen)
#   2. git clone --recurse-submodules
#   3. python3 utils/patch_proto_and_rust.py
#   4. cd valkey-glide/ffi && cargo build --release
#   5. phpize && ./configure --enable-valkey-glide
#   6. make build-modules-pre && make
# =============================================================================
FROM ubuntu:24.04 AS builder

ARG GLIDE_PHP_REPO=https://github.com/derrickschoen/valkey-glide-php.git
ARG GLIDE_PHP_BRANCH=main

ENV DEBIAN_FRONTEND=noninteractive

# Install build dependencies (per DEVELOPER.md Ubuntu section)
RUN apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y --no-install-recommends \
    # PHP development
    php8.3-dev \
    php8.3-cli \
    # Build tools
    gcc \
    g++ \
    make \
    autoconf \
    automake \
    libtool \
    pkg-config \
    # Protobuf C support
    libprotobuf-c-dev \
    libprotobuf-c1 \
    protobuf-c-compiler \
    # Other dependencies
    openssl \
    libssl-dev \
    git \
    unzip \
    python3 \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install Rust via rustup
RUN curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y
ENV PATH="/root/.cargo/bin:${PATH}"

# Install cbindgen (needed to generate C bindings from Rust FFI)
RUN cargo install cbindgen

# Install protoc >= 3.20 (per DEVELOPER.md)
RUN ARCH=$(dpkg --print-architecture) && \
    if [ "$ARCH" = "amd64" ]; then PROTOC_ARCH="x86_64"; \
    elif [ "$ARCH" = "arm64" ]; then PROTOC_ARCH="aarch_64"; \
    else echo "Unsupported architecture: $ARCH" && exit 1; fi && \
    curl -LO "https://github.com/protocolbuffers/protobuf/releases/download/v3.20.3/protoc-3.20.3-linux-${PROTOC_ARCH}.zip" && \
    unzip "protoc-3.20.3-linux-${PROTOC_ARCH}.zip" -d /usr/local && \
    rm "protoc-3.20.3-linux-${PROTOC_ARCH}.zip"

WORKDIR /build

# Clone the extension repo with submodules
RUN git clone --recurse-submodules --branch "${GLIDE_PHP_BRANCH}" "${GLIDE_PHP_REPO}" .

# Patch proto files for C compatibility
RUN python3 utils/patch_proto_and_rust.py

# Build the Rust FFI library
RUN cd valkey-glide/ffi && cargo build --release

# Build the PHP extension
RUN phpize \
    && ./configure --enable-valkey-glide \
    && make build-modules-pre \
    && make -j"$(nproc)"

# Verify the built extension
RUN php -d "extension=$(pwd)/modules/valkey_glide.so" -m | grep valkey_glide

# =============================================================================
# Stage 2: Runtime — PHP 8.3 CLI + Composer
# =============================================================================
FROM ubuntu:24.04 AS runtime

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common \
    curl \
    ca-certificates \
    gnupg \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y --no-install-recommends \
    php8.3-cli \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    git \
    unzip \
    # Protobuf-c runtime library (linked by the extension)
    libprotobuf-c1 \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy built extension from builder stage into PHP extension directory
COPY --from=builder /build/modules/valkey_glide.so /tmp/valkey_glide.so
RUN cp /tmp/valkey_glide.so "$(php -r 'echo ini_get("extension_dir");')/valkey_glide.so" \
    && rm /tmp/valkey_glide.so

# Copy extension test files from builder stage
COPY --from=builder /build/tests /app/extension-tests

# Enable the extension
RUN echo "extension=valkey_glide" > /etc/php/8.3/cli/conf.d/20-valkey_glide.ini

# Verify the extension loads
RUN php -m | grep valkey_glide

WORKDIR /app

CMD ["php", "-a"]
