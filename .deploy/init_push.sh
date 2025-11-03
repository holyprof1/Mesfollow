#!/usr/bin/env bash
set -e
cd /var/www/vhosts/mesfollow.com/httpdocs

git init
git config user.name "Tobi Holyprof"
git config user.email "tobbest17@gmail.com"
git checkout -B main

# --- Clean any nested git traces (dirs or files), but don't die on odd cases ---
set +e
find . -type d -name ".git" ! -path "./.git" -exec rm -rf {} + 2>/dev/null
find . -type f -name ".git" ! -path "./.git" -exec rm -f {} + 2>/dev/null
rm -f .gitmodules 2>/dev/null
set -e

# --- .gitignore (only once) ---
if [ ! -f .gitignore ]; then
  printf "%s\n" \
    "/uploads" \
    "vendor/" \
    "**/vendor/**" \
    ".env" \
    "requests/logs" \
    "*.log" \
    "node_modules/" > .gitignore
fi

git add -A
git commit -m "initial import from live" || true

# push (expects PAT as $1)
git push -u "https://$1@github.com/holyprof1/mesfollow.git" main -v
