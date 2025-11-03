#!/usr/bin/env bash
set -e
cd /var/www/vhosts/mesfollow.com/httpdocs

git add -A
GIT_AUTHOR_NAME="Tobi Holyprof" GIT_AUTHOR_EMAIL="tobbest17@gmail.com" \
GIT_COMMITTER_NAME="Tobi Holyprof" GIT_COMMITTER_EMAIL="tobbest17@gmail.com" \
git commit -m "auto: $(date -u +%F_%T)" || true

# push (expects PAT in $1)
git push "https://$1@github.com/holyprof1/mesfollow.git" HEAD:main -v
