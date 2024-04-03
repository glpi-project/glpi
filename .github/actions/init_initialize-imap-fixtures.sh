#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

docker compose exec -T --user root dovecot doveadm expunge -u glpi mailbox 'INBOX' all
docker compose exec -T --user root dovecot doveadm purge -u glpi
for f in `ls $ROOT_DIR/tests/emails-tests/*.eml`; do
  cat $f | docker compose exec -T --user glpi dovecot getmail_maildir /home/glpi/Maildir/
done
