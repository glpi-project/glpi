#!/bin/sh
# @version $Id:
# Little script for stimulate the pseudo cron of glpi 
# YOU MUST EDIT THE URL 
# Vous devez editez l'URL pour l'adapter à votre installation

curl --silent --compressed http://YOURSERVER/glpi/front/cron.php
