#!/bin/zsh
# Daily ADE refresh, run by launchd on Hiren's Mac (ninja.hiren.ade-sync). ADE keeps adding
# events and publishing ADE Pro times until the week itself; after it, this does nothing.
# Install: cp scripts/ninja.hiren.ade-sync.plist ~/Library/LaunchAgents/ && launchctl load ~/Library/LaunchAgents/ninja.hiren.ade-sync.plist
set -euo pipefail

if [[ $(date +%Y%m%d) -gt 20261026 ]]; then
  echo "$(date '+%F %T') ADE is over, nothing to sync"
  exit 0
fi

export PATH="$HOME/Library/Application Support/Herd/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin"
cd "$(dirname "$0")/.."
echo "$(date '+%F %T') ade:sync --reuse-pages"
php artisan ade:sync --reuse-pages --no-ansi 2>&1 | grep -v 'PHP Startup'
echo "$(date '+%F %T') done"
