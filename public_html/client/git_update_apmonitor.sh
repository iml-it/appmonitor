#!/bin/bash
# ======================================================================
#
# UPDATE APPMONITOR CLIENT
#
# requires git, rsync
#
# ----------------------------------------------------------------------
# 2022-04-11  <axel.hahn@iml.unibe.ch>  first lines
# ======================================================================

# ----------------------------------------------------------------------
# CONFIG
# ----------------------------------------------------------------------

git_repo_url="https://github.com/iml-it/appmonitor.git"
git_target=/tmp/git_data__appmonitor

client_from="${git_target}/public_html/client"
client_to="."
line="____________________________________________________________"

cd $( dirname "$0" ) || exit 1

# ----------------------------------------------------------------------
# FUNCTIONS
# ----------------------------------------------------------------------

# get data from a repo with git clone or git pull
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
        _rc=1
    fi
    return $_rc
}


# ----------------------------------------------------------------------
# MAIN
# ----------------------------------------------------------------------

echo 
echo 
echo "          +--------------------------------+"
echo "          |  UPDATER :: Appmonitor client  |"
echo "          +--------------------------------+"
echo 
echo 

case "$1" in
    -h|-?|--help)
        echo "USAGE:"
        echo "$0 [target path]"
        echo "defautl target is [.] (current directory)"
        exit 1
        ;;
    *)
        if ! test -d "$1"
        then 
            echo "ERROR: target dir $1 does not exist."
            exit 1
        fi
        echo "set target to $1"
        client_to="$1"
esac


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
rsync -rav $client_from/* "$client_to"
echo

echo $line
echo ">>> #3 of 3 >>> Diff"
echo
diff -r "$client_from" "$client_to"
echo

echo $line
echo done.

# ----------------------------------------------------------------------
