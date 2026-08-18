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

# Already installed and recent enough? Nothing to do. The 'make ember' target
# relies on --stdin-logs (Ember >= 1.5): older Embers hot-register a net_writer
# in Caddy's config that a Dockerized FrankenPHP can't dial back to, and the
# resulting config reload wedges the server.
if command -v ember >/dev/null 2>&1; then
    if ember --help 2>/dev/null | grep -q -- --stdin-logs; then
        echo "✅ Ember already installed: $(ember version 2>/dev/null | head -1)"
        exit 0
    fi
    echo "⬆️  Ember $(ember --version 2>/dev/null || echo '(unknown)') lacks --stdin-logs (needs >= 1.5) - upgrading..."
fi

OS="$(uname -s 2>/dev/null || echo unknown)"
echo "🔍 Detected OS: $OS"

via_brew() { echo "🍺 Installing via Homebrew..."; brew install "$REPO" 2>/dev/null || brew install alexandre-daubois/tap/ember; }
via_curl() { echo "📥 Installing via the official install script..."; curl -fsSL "https://raw.githubusercontent.com/$REPO/main/install.sh" | sh; }
via_go()   { echo "🐹 Installing via 'go install'...";   go install "github.com/$REPO/cmd/ember@latest"; }

# The official script is preferred over Homebrew: the tap's 1.6.0 cask ships
# without the binary (dangling symlink), while the release tarballs are fine.
case "$OS" in
    Darwin)
        if command -v curl >/dev/null 2>&1; then via_curl
        elif command -v brew >/dev/null 2>&1; then via_brew
        else echo "❌ Need curl or Homebrew. Install one, or grab a binary: $RELEASES"; exit 1
        fi
        ;;
    Linux)
        if command -v curl >/dev/null 2>&1; then via_curl
        elif command -v brew >/dev/null 2>&1; then via_brew
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
