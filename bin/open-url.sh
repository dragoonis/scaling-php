#!/usr/bin/env bash
#
# Open a URL in the host's default browser - cross-platform.
# Works on macOS, native Linux, WSL (Windows Subsystem for Linux), and Git Bash.
# If no opener is found (e.g. headless), it just prints a Ctrl+click-able URL.
#
#   bash bin/open-url.sh http://localhost:8088
#   make open                       (opens the app)
#
set -e

URL="${1:-http://localhost:8088}"

is_wsl() {
    # WSL1/WSL2 both report "microsoft" in /proc/version or /proc/sys/kernel/osrelease,
    # and WSL2 sets $WSL_DISTRO_NAME.
    [ -n "${WSL_DISTRO_NAME:-}" ] && return 0
    grep -qiE "microsoft|wsl" /proc/version 2>/dev/null && return 0
    grep -qiE "microsoft|wsl" /proc/sys/kernel/osrelease 2>/dev/null && return 0
    return 1
}

open_url() {
    local url="$1"

    # WSL -> hand off to Windows so the *Windows* default browser opens.
    if is_wsl; then
        if command -v wslview >/dev/null 2>&1; then wslview "$url"; return; fi
        if command -v explorer.exe >/dev/null 2>&1; then explorer.exe "$url"; return; fi
        if command -v cmd.exe >/dev/null 2>&1; then cmd.exe /c start "" "$url"; return; fi
        return 1
    fi

    case "$(uname -s 2>/dev/null)" in
        Darwin)
            open "$url"                                   # macOS
            ;;
        Linux)
            command -v xdg-open >/dev/null 2>&1 && xdg-open "$url" && return 0
            command -v gio >/dev/null 2>&1 && gio open "$url" && return 0
            return 1
            ;;
        MINGW*|MSYS*|CYGWIN*|Windows_NT)
            start "" "$url"                               # Git Bash / MSYS on Windows
            ;;
        *)
            return 1
            ;;
    esac
}

if open_url "$URL" >/dev/null 2>&1; then
    env_label="$(is_wsl && echo 'WSL' || uname -s)"
    echo "🌐 Opened in your default browser ($env_label):  $URL"
else
    # Fallback: most modern terminals make this Ctrl/Cmd+clickable.
    echo "🌐 Open this in your browser:  $URL"
fi
