#!/usr/bin/env bash
# Download micromdm/scep server binary (not scepclient).
set -euo pipefail

LAB="${NANOMDM_LAB:-$HOME/nanomdm-lab}"
VERSION="${SCEP_VERSION:-v2.3.0}"
ARCH="darwin-amd64"
URL="https://github.com/micromdm/scep/releases/download/${VERSION}/scepserver-${ARCH}-${VERSION}.zip"

mkdir -p "$LAB/bin"
cd "$LAB/bin"

if [[ -x "$LAB/bin/scep" ]]; then
  echo "SCEP already installed at $LAB/bin/scep"
  exit 0
fi

echo "Downloading SCEP server from $URL"
curl -fsSL -o scep.zip "$URL"
unzip -o scep.zip
mv -f "scepserver-${ARCH}-${VERSION}" scep 2>/dev/null || mv -f "scepserver-${ARCH}" scep 2>/dev/null || mv -f "scep-${ARCH}" scep 2>/dev/null || true
rm -f scep.zip
xattr -cr scep 2>/dev/null || true
chmod +x scep
echo "Installed: $LAB/bin/scep"
./scep -h | head -3
