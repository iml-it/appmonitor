#!/bin/bash
# ======================================================================
#
# DOCKER PHP DEV ENVIRONMENT :: INIT
#
# ----------------------------------------------------------------------
# 2021-11-nn  v1.0  <axel.hahn@iml.unibe.ch>
# 2022-07-19  v1.1  <axel.hahn@iml.unibe.ch>  support multiple dirs for setfacl
# 2022-11-16  v1.2  <www.axel-hahn.de>        use docker-compose -p "$APP_NAME"
# 2022-12-18  v1.3  <www.axel-hahn.de>        add -p "$APP_NAME" in other docker commands
# 2022-12-20  v1.4  <axel.hahn@unibe.ch>      replace fgrep with grep -F
# 2023-03-06  v1.5  <www.axel-hahn.de>        up with and without --build
# 2023-08-17  v1.6  <www.axel-hahn.de>        menu selection with single key (without return)
# 2023-11-10  v1.7  <axel.hahn@unibe.ch>      replace docker-compose with "docker compose"
# 2023-11-13  v1.8  <axel.hahn@unibe.ch>      UNDO "docker compose"; update infos
# 2023-11-15  v1.9  <axel.hahn@unibe.ch>      add help; execute multiple actions by params; new menu item: open app
# 2023-12-07  v1.10 <www.axel-hahn.de>        simplyfy console command; add php linter
# 2024-07-01  v1.11 <www.axel-hahn.de>        diff with colored output; suppress errors on port check
# 2024-07-19  v1.12 <axel.hahn@unibe.ch>      apply shell fixes
# 2024-07-22  v1.13 <axel.hahn@unibe.ch>      show info if there is no database container; speedup replacements
# 2024-07-22  v1.14 <axel.hahn@unibe.ch>      show colored boxes with container status
# 2024-07-24  v1.15 <axel.hahn@unibe.ch>      update menu output
# ======================================================================

cd "$( dirname "$0" )" || exit 1

# init used vars
gittarget=
frontendurl=

_self=$( basename "$0" )

# shellcheck source=/dev/null
. "${_self}.cfg" || exit 1

_version="1.15"

# git@git-repo.iml.unibe.ch:iml-open-source/docker-php-starterkit.git
selfgitrepo="docker-php-starterkit.git"

fgGray="\e[1;30m"
fgRed="\e[31m"
fgGreen="\e[32m"
fgBrown="\e[33m"
fgBlue="\e[34m"
fgReset="\e[0m"


# ----------------------------------------------------------------------
# FUNCTIONS
# ----------------------------------------------------------------------

# draw a headline 2
function h2(){
    echo
    echo -e "$fgBrown>>>>> $*$fgReset"
}

# draw a headline 3
function h3(){
    echo
    echo -e "$fgBlue----- $*$fgReset"
}

# show help for param -h
function showMenu(){
    cat <<EOM

    $( _key g ) - remove git data of starterkit
    
    $( _key i ) - init application: set permissions
    $( _key t ) - generate files from templates
    $( _key T ) - remove generated files
 
    $( _key u ) - startup containers    docker-compose ... up -d
    $( _key U ) - startup containers    docker-compose ... up -d --build
    $( _key s ) - shutdown containers   docker-compose stop
    $( _key r ) - remove containers     docker-compose rm -f
 
    $( _key m ) - more infos
    $( _key o ) - open app [${APP_NAME}] $frontendurl
    $( _key c ) - console (bash)
    $( _key p ) - console check with php linter
 
    $( _key q ) - quit
EOM
}
function showHelp(){
    cat <<EOH
INITIALIZER FOR DOCKER APP v$_version

A helper script written in Bash to bring up a PHP+Mysql application in docker.

Source : https://git-repo.iml.unibe.ch/iml-open-source/docker-php-starterkit
Docs   : https://os-docs.iml.unibe.ch/docker-php-starterkit/
License: GNU GPL 3.0
(c) Institute for Medical Education; University of Bern


SYNTAX:
  $_self [-h|-v]
  $_self [menu key]

OPTIONS:
  -h   show this help and exit
  -v   show version exit

MENU KEYS:
  In the interactive menu are some keys to init an action.
  The same keys can be put as parameter to start this action.
  You can add multiples keys to apply multiple actions.

$( showMenu )

EXAMPLES:

  $_self           starts interactive mode
  $_self u         bring up docker container(s) and stay in interactive mode
  $_self i q       set write permissions and quit
  $_self p q       start php linter and exit

EOH
}

# set acl on local directory
function _setWritepermissions(){
    h2 "set write permissions on ${gittarget} ..."

    local _user; _user=$( id -gn )
    local _user_uid; typeset -i _user_uid=0

    test -f /etc/subuid && _user_uid=$( grep "$_user" /etc/subuid 2>/dev/null | cut -f 2 -d ':' )-1
    local DOCKER_USER_OUTSIDE; typeset -i DOCKER_USER_OUTSIDE=$_user_uid+$DOCKER_USER_UID

    set -vx

    for mywritedir in ${WRITABLEDIR}
    do 

        echo "--- ${mywritedir}"
        # remove current acl
        sudo setfacl -bR "${mywritedir}"

        # default permissions: both the host user and the user with UID 33 (www-data on many systems) are owners with rwx perms
        sudo setfacl -dRm "u:${DOCKER_USER_OUTSIDE}:rwx,${_user}:rwx" "${mywritedir}"

        # permissions: make both the host user and the user with UID 33 owner with rwx perms for all existing files/directories
        sudo setfacl -Rm "u:${DOCKER_USER_OUTSIDE}:rwx,${_user}:rwx" "${mywritedir}"
    done

    set +vx
}

# cleanup starterkit git data
function _removeGitdata(){
    h2 "Remove git data of starterkit"
    echo -n "Current git remote url: "
    git config --get remote.origin.url
    if git config --get remote.origin.url 2>/dev/null | grep $selfgitrepo >/dev/null; then
        echo
        echo -n "Delete local .git and .gitignore? [y/N] > "
        read -r answer
        test "$answer" = "y" && ( echo "Deleting ... " && rm -rf ../.git ../.gitignore )
    else
        echo "It was done already - $selfgitrepo was not found."
    fi

}

# helper function: cut a text file starting from database start marker
# see _generateFiles()
function _fix_no-db(){
    local _file=$1
    if [ "$DB_ADD" = "false" ]; then
        local iStart; typeset -i iStart
        iStart=$( grep -Fn "$CUTTER_NO_DATABASE" "${_file}" | cut -f 1 -d ':' )-1
        if [ $iStart -gt 0 ]; then
            sed -ni "1,${iStart}p" "${_file}"
        fi
    fi
}

# helper functiion to generate replacements using sed
# it loops over all vars in the config file
# used in _generateFiles
function _getreplaces(){
    # loop over vars to make the replacement
    grep "^[a-zA-Z]" "$_self.cfg" | while read -r line
    do
        # echo replacement: $line
        mykey=$( echo "$line" | cut -f 1 -d '=' )
        myvalue="$( eval echo \"\$"$mykey"\" )"

        # TODO: multiline values fail here in replacement with sed 
        echo -e "s#{{$mykey}}#${myvalue}#g"

    done
}

# loop over all files in templates subdir make replacements and generate
# a target file.
# It skips if 
#   - 1st line is not starting with "# TARGET: filename"
#   - target file has no updated lines
function _generateFiles(){

    # shellcheck source=/dev/null
    . "${_self}.cfg" || exit 1    

    params=$( _getreplaces | while read -r line; do echo -n "-e '$line' ";  done )

    local _tmpfile=/tmp/newfilecontent$$.tmp
    h2 "generate files from templates..."
    for mytpl in templates/*
    do
        # h3 $mytpl
        local _doReplace=1

        # fetch traget file from first line
        target=$( head -1 "$mytpl" | grep "^# TARGET:" | cut -f 2- -d ":" | awk '{ print $1 }' )

        if [ -z "$target" ]; then
            echo "SKIP: $mytpl - target was not found in 1st line"
            _doReplace=0
        fi

        # write generated files to target
        if [ $_doReplace -eq 1 ]; then

            # write file from line 2 to a tmp file
            sed -n '2,$p' "$mytpl" >"$_tmpfile"

            # add generator
            # sed -i "s#{{generator}}#generated by $0 - template: $mytpl - $( date )#g" $_tmpfile
            local _md5; _md5=$( md5sum $_tmpfile | awk '{ print $1 }' )
            sed -i "s#{{generator}}#GENERATED BY $_self - template: $mytpl - $_md5#g" $_tmpfile

            eval sed -i "$params" "$_tmpfile" || exit

            _fix_no-db $_tmpfile

            # echo "changes for $target:"
            if diff --color=always "../$target"  "$_tmpfile" | grep -v "$_md5" | grep -v "^---" | grep . || [ ! -f "../$target" ]; then
                echo -n "$mytpl - changes detected - writing [$target] ... "
                mkdir -p "$( dirname  ../"$target" )" || exit 2
                mv "$_tmpfile" "../$target" || exit 2
                echo OK
                echo
            else
                rm -f $_tmpfile
                echo "SKIP: $mytpl - Nothing to do."
            fi
        fi
    done

}

# loop over all files in templates subdir make replacements and generate
# a traget file.
function _removeGeneratedFiles(){
    h2 "remove generated files..."
    for mytpl in templates/*
    do
        h3 "$mytpl"

        # fetch traget file from first line
        target=$( head -1 "$mytpl" | grep "^# TARGET:" | cut -f 2- -d ":" | awk '{ print $1 }' )

        if [ -n "$target" ] && [ -f "../$target" ]; then
            echo -n "REMOVING "
            ls -l "../$target" || exit 2
            rm -f "../$target" || exit 2
            echo OK
        else
            echo "SKIP: $target"
        fi
        
    done
}

# show running containers
function _showContainers(){
    local bLong=$1
    local _out

    local sUp=".. UP"
    local sDown=".. down"

    local Status=
    local StatusWeb="$sDown"
    local StatusDb=""
    local colWeb=
    local colDb=

    colDb="$fgRed"
    colWeb="$fgRed"

    _out=$( if [ -z "$bLong" ]; then
        docker-compose -p "$APP_NAME" ps
    else
        # docker ps | grep "$APP_NAME"
        docker-compose -p "$APP_NAME" ps
    fi)

    h2 CONTAINERS
    if [ "$( wc -l <<< "$_out" )"  -eq 1 ]; then
        if [ "$DB_ADD" = "false" ]; then
            colDb="$fgGray"
            Status="The web container is not running. This app has no database container."
        else
            StatusDb="down"
            Status="No container is running for <$APP_NAME>."
        fi
    else
        grep -q "${APP_NAME}-server" <<< "$_out" && colWeb="$fgGreen"
        grep -q "${APP_NAME}-server" <<< "$_out" && StatusWeb="$sUp"

        grep -q "${APP_NAME}-db" <<< "$_out"  && colDb="$fgGreen"
        StatusDb="$sDown"
        grep -q "${APP_NAME}-db" <<< "$_out"  && StatusDb="$sUp"

        if [ "$DB_ADD" = "false" ]; then
            colDb="$fgGray"
            StatusDb=""
            Status="INFO: This app has no database container."
        fi
    fi

    printf "$colWeb     __________________________  $colDb   __________________________    $fgReset \n"
    printf "$colWeb    |  %-22s  |  $colDb |  %-22s  | $fgReset \n" ""                             ""
    printf "$colWeb    |  %-22s  |  $colDb |  %-22s  | $fgReset \n" "${APP_NAME}-web ${StatusWeb}" "${APP_NAME}-db ${StatusDb}"
    printf "$colWeb    |  %-22s  |  $colDb |  %-22s  | $fgReset \n" "  PHP ${APP_PHP_VERSION}"     "  ${MYSQL_IMAGE}"
    printf "$colWeb    |  %-22s  |  $colDb |  %-22s  | $fgReset \n" "  :${APP_PORT}"               "  :${DB_PORT}"
    printf "$colWeb    |__________________________| $colDb  |__________________________|   $fgReset \n"

    if [ -n "$Status" ]; then
        echo
        echo "$Status"
    fi
    echo

    if [ -n "$bLong" ]; then
        echo "$_out"

        h2 STATS
        docker stats --no-stream
        echo
    fi

}


# show urls for app container
function _showBrowserurl(){
    echo "In a web browser open:"
    echo "  $frontendurl"
    if grep "${APP_NAME}-server" /etc/hosts >/dev/null; then
        echo "  https://${APP_NAME}-server/"
    fi
}

# detect + show ports and urls for app container and db container
function _showInfos(){
    _showContainers long
    h2 INFO

    h3 "processes webserver"
    # docker-compose top
    docker top "${APP_NAME}-server"
    if [ ! "$DB_ADD" = "false" ]; then
        h3 "processes database"
        docker top "${APP_NAME}-db"
    fi

    h3 "Check app port"
    if echo >"/dev/tcp/localhost/${APP_PORT}"; then
        echo "OK, app port ${APP_PORT} is reachable"
        echo
        _showBrowserurl
    else
        echo "NO, app port ${APP_PORT} is not available"
    fi 2>/dev/null

    if [ "$DB_ADD" != "false" ]; then
        h3 "Check database port"
        if echo >"/dev/tcp/localhost/${DB_PORT}"; then
            echo "OK, db port ${DB_PORT} is reachable"
            echo
            echo "In a local DB admin tool you can connect it:"
            echo "  host    : localhost"
            echo "  port    : ${DB_PORT}"
            echo "  user    : root"
            echo "  password: ${MYSQL_ROOT_PASS}"
        else
            echo "NO, db port ${DB_PORT} is not available"
        fi 2>/dev/null

    fi
    echo
}

# helper for menu: print an inverted key
function  _key(){
    echo -en "\e[4;7m ${1} \e[0m"
}

# helper: wait for a return key
function _wait(){
    local _wait=15
    echo -n "... press RETURN ... or wait $_wait sec > "; read -r -t $_wait
}

# ----------------------------------------------------------------------
# MAIN
# ----------------------------------------------------------------------

action=$1; shift 1

while true; do

    if [ -z "$action" ]; then

        echo "_______________________________________________________________________________"
        echo
        echo
        echo "  ${APP_NAME^^} :: Initializer for docker"
        echo "                                                                         ______"
        echo "________________________________________________________________________/ $_version"
        echo

        _showContainers

        h2 MENU       
        showMenu
        echo
        echo -n "  select >"
        read -rn 1 action 
        echo
    fi

    case "$action" in
        "-h") showHelp; exit 0 ;;
        "-v") echo "$_self $_version"; exit 0 ;;
        g)
            _removeGitdata
            ;;
        i)
            # _gitinstall
            _setWritepermissions
            ;;
        t)
            _generateFiles
            ;;
        T)
            _removeGeneratedFiles
            rm -rf containers
            ;;
        m)
            _showInfos
            _wait
            ;;
        u|U)
            h2 "Bring up..."
            dockerUp="docker-compose -p $APP_NAME --verbose up -d --remove-orphans"
            if [ "$action" = "U" ]; then
                dockerUp+=" --build"
            fi
            echo "$dockerUp"
            if $dockerUp; then
                _showBrowserurl
            else
                echo "ERROR: docker-compose up failed :-/"
                docker-compose -p "$APP_NAME" logs | tail
            fi
            echo

            _wait
            ;;
        s)
            h2 "Stopping..."
            docker-compose -p "$APP_NAME" stop
            ;;
        r)
            h2 "Removing..."
            docker-compose -p "$APP_NAME" rm -f
            ;;
        c)
            h2 "Console"
            _containers=$( docker-compose -p "$APP_NAME" ps | sed -n "2,\$p" | awk '{ print $1}' )
            if [ "$DB_ADD" = "false" ]; then
                dockerid=$_containers
            else
                echo "Select a container:"
                sed "s#^#    #g" <<< "$_containers"
                echo -n "id or name >"
                read -r dockerid
            fi
            test -z "$dockerid" || (
                echo
                echo "> docker exec -it $dockerid /bin/bash     (type 'exit' + Return when finished)"
                docker exec -it "$dockerid" /bin/bash
            )
            ;;
        p)
            h2 "PHP $APP_PHP_VERSION linter"

            dockerid="${APP_NAME}-server"
            echo -n "Scanning ... "
            typeset -i _iFiles
            _iFiles=$( docker exec -it "$dockerid" /bin/bash -c "find . -name '*.php' " | wc -l )

            if [ $_iFiles -gt 0 ]; then
                echo "found $_iFiles [*.php] files ... errors from PHP $APP_PHP_VERSION linter:"
                time if echo "$APP_PHP_VERSION" | grep -E "([567]\.|8\.[012])" >/dev/null ; then
                    docker exec -it "$dockerid" /bin/bash -c "find . -name '*.php' -exec php -l {} \; | grep -v '^No syntax errors detected'"
                else
                    docker exec -it "$dockerid" /bin/bash -c "php -l \$( find . -name '*.php' ) | grep -v '^No syntax errors detected' "
                fi
                echo
                _wait
            else
                echo "Start your docker container first."
            fi
            ;;
        o) 
            h2 "Open app ..."
            xdg-open "$frontendurl"
            ;;
        q)
            h2 "Bye!"
            exit 0;
            ;;
        *) 
            test -n "$action" && ( echo "  ACTION FOR [$action] NOT IMPLEMENTED."; sleep 1 )
    esac
    action=$1; shift 1
done


# ----------------------------------------------------------------------
