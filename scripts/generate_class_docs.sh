#!/bin/bash
# ======================================================================
#
# Generate markdown docs for selected php classes
#
# ----------------------------------------------------------------------
# 2024-07-23  v0.1  <axel.hahn@unibe.ch>  initual version
# ======================================================================

# ----------------------------------------------------------------------
# CONFIG
# ----------------------------------------------------------------------

cd "$( dirname $0)/.."
APPDIR=$(pwd)
DOCDIR=$APPDIR/docs/90_PHP-classes

# works on axels dev env only ... this class is not published yet
cd /home/axel/data/opensource/php-class/class-phpdoc || exit

# list of files to generate a class documentation
clientClassfiles="
    public_html/client/classes/appmonitor-checks.class.php
    public_html/client/classes/appmonitor-client.class.php

"

serverClassfiles="
    public_html/server/classes/appmonitor-server-api.class.php
    public_html/server/classes/appmonitor-server-gui.class.php
    public_html/server/classes/appmonitor-server.class.php
    public_html/server/classes/counteritems.class.php
    public_html/server/classes/lang.class.php
    public_html/server/classes/notificationhandler.class.php
    public_html/server/classes/simplerrd.class.php
    public_html/server/classes/tinyapi.class.php
    public_html/server/classes/tinyrouter.class.php
    public_html/server/classes/tinyservice.class.php

"

# ----------------------------------------------------------------------
# FUNCTIONS
# ----------------------------------------------------------------------

# generate a doc file for a single class file
# global DOCDIR  path for doc page
# param  string  filename of class file
# param  string  target directory
function docgen(){
    local myfile=$1
    local mytarget=$2
    outfile=$DOCDIR/$mytarget/$( basename "$myfile" ).md

    echo "----- $myfile"
    echo "      $outfile"
    ./parse-class.php --out md "$APPDIR/$myfile" > "$outfile"
    echo
}

# ----------------------------------------------------------------------
# MAIN
# ----------------------------------------------------------------------


# ./parse-class.php --out md "$APPDIR/public_html/server/classes/appmonitor-server.class.php"; exit 

# generate all docs
for myfile in $clientClassfiles
do
    docgen "$myfile" client
done


for myfile in $serverClassfiles
do
    docgen "$myfile" server
done

echo "Done"

# ----------------------------------------------------------------------
