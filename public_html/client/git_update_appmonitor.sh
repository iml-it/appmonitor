#!/bin/bash
# ======================================================================
#
#   A P P M O N I T O R  ::  CLIENT - UPDATE
#
# This script will install or update the appmonitor client only.
#
# Below the document root of a website create a new directory, 
# i.e. [webroot]/appmonitor/ and copy this script there.
# Change the directory "cd [webroot]/appmonitor/" and execute it.
#
# ----------------------------------------------------------------------
# requires git, rsync
# ----------------------------------------------------------------------
# 2022-04-11  0.1  <axel.hahn@iml.unibe.ch>  first lines
# 2022-04-12  0.2  <axel.hahn@iml.unibe.ch>  add help; exclude unneeded files
# 2022-05-03  0.3  <axel.hahn@iml.unibe.ch>  create general_include.php
# 2024-07-25  0.4  <axel.hahn@iml.unibe.ch>  update quoting and comments
# ======================================================================

# ----------------------------------------------------------------------
# CONFIG
# ----------------------------------------------------------------------

readonly git_repo_url="https://github.com/iml-it/appmonitor.git"
readonly line="______________________________________________________________________________"
readonly version="0.4"

git_target=/tmp/git_data__appmonitor
client_from="${git_target}/public_html/client"
client_to="."

cd "$( dirname "$0" )" || exit 1

# ----------------------------------------------------------------------
# FUNCTIONS
# ----------------------------------------------------------------------

# Create a missing file from sample file
#
# global $client_from  source dir with git repo data
# global $client_to    target dir
#
# param  string  source file (containing .sample); relative to $client_from
function _fileupdate(){
    local _myfile=$1
    local _newfile=${_myfile//.sample/}
    echo -n "Update $client_from/$_myfile --> $client_to/$_newfile ... "
    
    if [ ! -f "$client_to/$_newfile" ]; then
        echo -n "copy ... "
        cp "$client_from/$_myfile" "$client_to/$_newfile" || exit 2
        echo "OK"
    else
        echo "already exists - SKIP "
    fi

}

# get data from a repo with git clone or git pull
# param string  url of public .git repo
# param string  local directory where to clone it
function _gitUpdate(){
    local _url=$1
    local _dirgit=$2
    local _rc=0
    if [ -d "$_dirgit" ]; then
        cd "$_dirgit" || exit 1
        _logBefore=$( git log -1 );
        echo "Update local data from repo... with git pull "
        git pull
        _logAfter=$( git log -1 ); 
        if [ "$_logBefore" != "$_logAfter" ]; then
            _rc=1
        fi
        cd - >/dev/null || exit 1
    else
        echo "Cloning..."
        git clone "$_url" "$_dirgit"
        _rc=$?
    fi
    return $_rc
}


# ----------------------------------------------------------------------
# MAIN
# ----------------------------------------------------------------------

cat <<ENDOFHEADER

          +-----------------------------------+
          |                                   |
          |  INSTALLER  |                     |
          |      +      |  Appmonitor client  |
          |   UPDATER   |                     |
          |                                   |
          +--------------------------- v$version --+

ENDOFHEADER

case "$1" in
    -h|--help)
        cat <<ENDOFHELP

    This is a helper script to get the files of the IML Appmonitor
    client part only.

    Below the document root of a website create a new directory, 
    i.e. [webroot]/appmonitor/ and copy this script there.

    This script clones and updates the repository in the /tmp 
    directory and syncs the client files of it to a given directory.

    In the first run it works like an installer.
    On additional runs it updates the files.

    USAGE:

    $0 [target path]

        default target is [.] (current directory)

    $0 -h|--help

        Show this help.

ENDOFHELP
        exit 0
        ;;
    *)
        if test -n "$1" 
            then
            if  ! test -d "$1"
            then 
                echo "ERROR: target dir [$1] does not exist."
                exit 1
            fi
            echo "set target to $1"
            client_to="$1"
        fi
esac

which rsync >/dev/null || exit 1
which git >/dev/null || exit 1

echo $line
echo ">>> #1 of 3 >>> update local git data"
echo
echo "URL $git_repo_url"
echo "TO  $git_target"
if ! _gitUpdate "$git_repo_url" "$git_target"
then 
    echo ERROR occured :-/
    exit 1
fi
echo


echo $line
echo ">>> #2 of 3 >>> Sync files of Appmonitor client"
echo
echo "FROM $client_from/*" 
echo "TO   $client_to"
rsync -rav \
    --exclude "build" \
    --exclude "*.sample.*" \
    --exclude "example.json" \
    --exclude "check-appmonitor-server.php" \
    $client_from/* "$client_to"
echo

_fileupdate general_include.sample.php

echo $line
echo ">>> #3 of 3 >>> Diff"
echo
diff --color -r "$client_from" "$client_to"
echo


echo $line
echo done.

# ----------------------------------------------------------------------
