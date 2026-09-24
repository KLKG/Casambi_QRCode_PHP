#!/bin/sh
# Holt neue Quellen von GitHub und baut den App-Container neu, wenn sich etwas geändert hat.
#   docker/update.sh          -> nur bei neuen Commits
#   docker/update.sh --force  -> immer neu bauen und aktuelle Basis-Images (Sicherheitsupdates) ziehen
# config/, logs/, .env und die Datenbank bleiben unberührt; das Schema migriert die App selbst.
set -e
cd "$(dirname "$0")/.."

git fetch --quiet origin
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse '@{u}')

if [ "$LOCAL" = "$REMOTE" ] && [ "$1" != "--force" ]; then
    echo "$(date '+%F %T') keine Änderungen"
    exit 0
fi

git pull --ff-only --quiet
echo "$(date '+%F %T') Update $(git rev-parse --short "$LOCAL") -> $(git rev-parse --short HEAD)"

docker compose build --pull app
docker compose up -d
# Platz sparen: alten Build-Cache und ungenutzte Images entfernen (Volumes bleiben!)
docker builder prune -f >/dev/null
docker image prune -f >/dev/null
echo "$(date '+%F %T') fertig"
