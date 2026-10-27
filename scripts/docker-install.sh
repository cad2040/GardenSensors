#!/usr/bin/env bash
# Thin Docker-based installer for GardenSensors (Windows/macOS/Linux via Docker Desktop / Engine).
# Usage: ./scripts/docker-install.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== GardenSensors Docker installer ==="

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is not installed."
  echo "Install Docker Desktop (Windows/macOS) or Docker Engine (Linux), then re-run."
  echo "  https://docs.docker.com/get-docker/"
  exit 1
fi

if ! docker compose version >/dev/null 2>&1 && ! docker-compose version >/dev/null 2>&1; then
  echo "Docker Compose plugin not found. Install Compose v2 and re-run."
  exit 1
fi

COMPOSE=(docker compose)
if ! docker compose version >/dev/null 2>&1; then
  COMPOSE=(docker-compose)
fi

if [ ! -f .env ]; then
  echo "Creating .env from .env.example..."
  cp .env.example .env
fi

read -r -p "Admin username [admin]: " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}
read -r -p "Admin email [admin@example.com]: " ADMIN_EMAIL
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}
read -r -s -p "Admin password: " ADMIN_PASS
echo
if [ -z "${ADMIN_PASS}" ]; then
  echo "Password is required."
  exit 1
fi
read -r -p "HTTP port [8080]: " HTTP_PORT
HTTP_PORT=${HTTP_PORT:-8080}

# Persist first-run answers for the web setup container to consume
mkdir -p docker/first-run
cat > docker/first-run/answers.env <<EOF
ADMIN_USER=${ADMIN_USER}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_PASS=${ADMIN_PASS}
APP_URL=http://localhost:${HTTP_PORT}
EOF
chmod 600 docker/first-run/answers.env

# Patch published web port in a local override (does not modify committed compose)
cat > docker-compose.override.yml <<EOF
services:
  web:
    ports:
      - "${HTTP_PORT}:80"
    environment:
      APP_URL: http://localhost:${HTTP_PORT}
EOF

echo "Starting containers..."
"${COMPOSE[@]}" up -d --build

echo "Waiting for web health..."
for i in $(seq 1 40); do
  if curl -fsS "http://127.0.0.1:${HTTP_PORT}/login.php" >/dev/null 2>&1; then
    break
  fi
  if [ $i -eq 40 ]; then
    echo "Health check failed after 40 attempts. Exiting with code 1."
    exit 1
  fi
  sleep 3
done

echo "Applying first-run admin (if needed)..."

# Prefer HTTP first-run endpoint inside the stack
if curl -fsS -X POST "http://127.0.0.1:${HTTP_PORT}/setup/first_run.php" \
  -d "username=${ADMIN_USER}" \
  -d "email=${ADMIN_EMAIL}" \
  -d "password=${ADMIN_PASS}" \
  -d "token=local-install" >/dev/null 2>&1; then
  echo "First-run admin configured."
else
  echo "Open http://localhost:${HTTP_PORT}/setup/ to finish creating your admin account."
fi

echo
echo "Dashboard: http://localhost:${HTTP_PORT}/"
echo "Login with the admin account you just set."
echo "Configure SMTP in .env (MAIL_*) then Settings → Send test email."

