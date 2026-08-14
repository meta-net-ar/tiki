#!/bin/bash
 
# This script renames _htaccess files to .htaccess 
# http://doc.tiki.org/Clean+URLs
# 
# This script was more useful before, because there were many files to rename. More recently, Tiki 
# ships with already named .htaccess files in all subdirectories. Thus, only one file needs to be 
# renamed, the one at the root directory.
#
# You can simply rename _htaccess to .htaccess in your root directory, instead of using this script.
# Nonetheless, the script can be useful if you want to put in a cron job.
#
# usage:
# sh doc/devtools/htaccess.sh
#

OLD=_htaccess
NEW=.htaccess
ACTION=activating
COMMAND="cp"
 
if [ "$1" = "off" ]; then
        OLD=.htaccess
        NEW=_htaccess
        ACTION=deactivating
    COMMAND="mv"
fi
 
find . -name "$OLD" -print0 | while IFS= read -r -d '' i; do
    chmod 644 "$i"
    echo "${ACTION} $(dirname "$i")/${NEW}"
    $COMMAND "$i" "$(dirname "$i")/${NEW}"
done
