#!/bin/bash
grep msgid ../locales/glpi.pot | grep -v 'msgid ""' | sort | uniq -c | sort -n
