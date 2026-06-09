#!/usr/bin/env bash
#
# Install Ember (https://github.com/alexandre-daubois/ember) - the FrankenPHP
# terminal dashboard. Auto-detects the OS and picks the best install method.
#
#   make ember-install        (or: bash bin/install-ember.sh)
#
set -e

REPO="alexandre-daubois/ember"
RELEASES="https://github.com/$REPO/releases"

# Already installed? Nothing to do.
if command -v ember >/dev/null 2>&1; then
    echo "✅ Ember already installed: $(ember version 2>/dev/null | head -1)"
    exit 0
fi

OS="$(uname -s 2>/dev/null || echo unknown)"
echo "🔍 Detected OS: $OS"

via_brew() { echo "🍺 Installing via Homebrew..."; brew install "$REPO" 2>/dev/null || brew install alexandre-daubois/tap/ember; }
via_curl() { echo "📥 Installing via the official install script..."; curl -fsSL "https://raw.githubusercontent.com/$REPO/main/install.sh" | sh; }
via_go()   { echo "🐹 Installing via 'go install'...";   go install "github.com/$REPO/cmd/ember@latest"; }

case "$OS" in
    Darwin)
        if command -v brew >/dev/null 2>&1; then via_brew
        elif command -v curl >/dev/null 2>&1; then via_curl
        else echo "❌ Need Homebrew or curl. Install one, or grab a binary: $RELEASES"; exit 1
        fi
        ;;
    Linux)
        if command -v brew >/dev/null 2>&1; then via_brew
        elif command -v curl >/dev/null 2>&1; then via_curl
        elif command -v go >/dev/null 2>&1; then via_go
        else echo "❌ Need curl, brew, or go. Install one, or grab a binary: $RELEASES"; exit 1
        fi
        ;;
    MINGW*|MSYS*|CYGWIN*|Windows_NT)
        echo "🪟 Windows detected."
        if command -v scoop >/dev/null 2>&1; then echo "Run: scoop install ember"; exit 1
        elif command -v go >/dev/null 2>&1; then via_go
        else echo "Grab the Windows binary from: $RELEASES"; exit 1
        fi
        ;;
    *)
        echo "❓ Unknown OS - trying the universal install script..."
        via_curl
        ;;
esac

# macOS Gatekeeper: drop the quarantine flag so it runs first time.
if [ "$OS" = "Darwin" ] && command -v ember >/dev/null 2>&1; then
    xattr -d com.apple.quarantine "$(command -v ember)" 2>/dev/null || true
fi

if command -v ember >/dev/null 2>&1; then
    echo "✅ Ember installed: $(ember version 2>/dev/null | head -1)"
else
    echo "⚠️  Installed, but 'ember' isn't on your PATH yet - open a new terminal (or add it to PATH)."
fi
