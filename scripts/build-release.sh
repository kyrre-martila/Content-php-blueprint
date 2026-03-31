#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT_DIR}/build"
RELEASE_DIR="${BUILD_DIR}/release"
DIST_DIR="${ROOT_DIR}/dist"
VERSION="${1:-dev}"
ARCHIVE_NAME="content-php-blueprint-${VERSION}.zip"
ARCHIVE_PATH="${DIST_DIR}/${ARCHIVE_NAME}"

rm -rf "${RELEASE_DIR}" "${ARCHIVE_PATH}"
mkdir -p "${RELEASE_DIR}" "${DIST_DIR}"

copy_path() {
  local source_path="$1"
  if [[ -e "${ROOT_DIR}/${source_path}" ]]; then
    cp -a "${ROOT_DIR}/${source_path}" "${RELEASE_DIR}/${source_path}"
  fi
}

mkdir -p "${RELEASE_DIR}/storage/logs"
mkdir -p "${RELEASE_DIR}/storage/exports/composition"
mkdir -p "${RELEASE_DIR}/storage/exports/ocf"

copy_path "public"
copy_path "src"
copy_path "templates"
copy_path "patterns"
copy_path "config"
copy_path "database"
copy_path "vendor"
copy_path "composer.json"
copy_path "composer.lock"
copy_path "phinx.php"
copy_path "README.md"
copy_path ".env.example"
copy_path "index.php"

(
  cd "${RELEASE_DIR}"
  zip -r "${ARCHIVE_PATH}" .
)

echo "Release archive created: ${ARCHIVE_PATH}"
