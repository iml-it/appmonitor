#!/bin/bash
# ======================================================================
#
#   
#    _____ _____ __                   _____         _ _           
#   |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
#   |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
#   |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
#                            |_| |_|                              
#                             _ _         _                                            
#                         ___| |_|___ ___| |_                                          
#                        |  _| | | -_|   |  _|                                         
#                        |___|_|_|___|_|_|_|   
#                                                                 
#
#                         INSTALLER + UPDATER
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
# 2024-07-31  0.5  <axel.hahn@iml.unibe.ch>  Show more helpful information; wait on 1st install; added param -n
# 2024-12-23  0.6  <axel.hahn@iml.unibe.ch>  remove which command. Maybe it is not installed on a shared hoster.
# 2024-12-26  0.7  <axel.hahn@iml.unibe.ch>  rsync test with --version instead of -V (for compatibility with older versions)
# 2025-01-06  0.8  <axel.hahn@iml.unibe.ch>  git test with --version instead of -v (for compatibility with older versions)
# ======================================================================

# ----------------------------------------------------------------------
# CONFIG
# ----------------------------------------------------------------------

readonly git_repo_url="https://github.com/iml-it/appmonitor.git"
readonly docs_url="https://os-docs.iml.unibe.ch/appmonitor/PHP_client/index.html"
readonly line="______________________________________________________________________________"
readonly version="0.8"

git_target=/tmp/git_data__appmonitor
client_from="${git_target}/public_html/client"
client_to="."
isUpdate=0
wait=1


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
$line

    IML Appmonitor client   ::   installer + updater  v$version
$line


ENDOFHEADER

case "$1" in
    -h|--help)
        cat <<ENDOFHELP
    The IML Appmonitor is free software.

        Source: https://github.com/iml-it/appmonitor
        Docs: https://os-docs.iml.unibe.ch/appmonitor
        License: GNU GPL 3.0

    This is a helper script to get the files of the IML Appmonitor
    client part only.

    Below the document root of a website create a new directory, 
    i.e. [webroot]/appmonitor/ and copy this script there.

    This script clones and updates the repository in the /tmp 
    directory and syncs the client files of it to a given directory.

    In the first run it works like an installer.
    On additional runs it updates the files.

    USAGE:
        $0 [OPTIONS] [TARGET]

    OPTIONS:
        -h|--help
            Show this help and exit
        -n|--nowait
            Do not wait for RETURN on 1st installation.
            Use it for an unattended installation.

    PARAMETERS:
        TARGET 
            optional target path for the client files
            default target is "." (current directory)

ENDOFHELP
        exit 0
        ;;
    -n|--nowait)
        wait=0
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

# which rsync >/dev/null || exit 1
# which git >/dev/null || exit 1

rsync --version >/dev/null || exit 1
git --version >/dev/null || exit 1

test -f general_include.php && isUpdate=1

if [ $isUpdate -eq 0 ]; then
    cat <<WELCOME
    Welcome to the Appmonitor client installation!


    This is a helper script to get the client files of the IML Appmonitor.
    They will be installed into the directory "$client_to" $( test "$client_to" = "." && (echo; echo -n "    "; pwd) )

        If this is not correct, press Ctrl + C to abort and use a
        parameter to set another target directory.

        "$( basename "$0" ) -h" shows a help and more options.


WELCOME
    if [ $wait -eq 1 ]; then
        echo -n "    RETURN to continue ... "
        read -r
    fi
else
    echo "Updating local files ..."
fi
echo

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
    --exclude "local.php" \
    --exclude "git_update_appmonitor.sh" \
    $client_from/* "$client_to"
echo

_fileupdate general_include.sample.php

echo $line
echo ">>> #3 of 3 >>> Diff"
echo
diff --color -r "$client_from" "$client_to"
echo

if [ $isUpdate -eq 0 ]; then
    _fileupdate index.sample.php
    cat <<INTRODUCTION
$line


    DONE!
    The Appmonitor client was installed.

    - Please edit index.php and general_include.php.

    - If you have multiple applications below webroot then you can 
      rename the file index.php to check-[appname].php eg.
      check-cms.php, check-blog.php, ... 

    - Start "$( basename "$0" )" again to perform an update.
      Maybe you want to create a cronjob for this.

INTRODUCTION
else
    echo "Appmonitor client was updated."
fi
echo

echo "Documentation: $docs_url"
echo
echo $line
echo done.
cp -rp "$client_from/git_update_appmonitor.sh" "$client_to"

# ----------------------------------------------------------------------
