#!/bin/sh
# # @version $Id:
# Little script for stimulate the pseudo cron of glpi
# YOU MUST EDIT THE URL 
# Vous devez editez l'URL selon votre installation

/usr/bin/lynx -source http://YOURSERVER/glpi/front/cron.php > /dev/null 2>&1
