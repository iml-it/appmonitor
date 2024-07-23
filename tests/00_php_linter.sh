#!/bin/bash

# --------------------------------------------------------------------------------
# INIT
# --------------------------------------------------------------------------------

cd "$( dirname "$0" )/.." || exit 1
. ./docker/init.sh.cfg || exit 1

dockerid="${APP_NAME}-server"


# --------------------------------------------------------------------------------
# FUNCTIONS
# --------------------------------------------------------------------------------

function _section(){
    echo
    echo
    echo ">>>>>>>>> $*"
}

# Execute a command in the docker container
# it shows the output.The last line is the returncode
#
# param  string  command
function _exec(){
    local _cmd=$1
    local _out

    # _out=$( docker exec -it "$dockerid" /bin/bash -c "$_cmd; echo \$?" ) || exit $?
    _out=$( docker exec -it "$dockerid" /bin/bash -c "$_cmd" ) || exit $?

    echo "$_out"
}

# --------------------------------------------------------------------------------
# MAIN
# --------------------------------------------------------------------------------

cat <<EOH
-------------------------------------------------------------------------------

Client tester

-------------------------------------------------------------------------------
EOH

_section "PHP linter"
    _exec "php -l \$( find . -name '*.php' )"


_section "Appmonitor Client - Check Appmonitor server"
    data=$( _exec "php client/check-appmonitor-server.php" ) || exit $?
    echo "Response: $data"
    echo

    echo "Get section <meta> from it"
    echo "$data" | jq ".meta" || exit $?


# --------------------------------------------------------------------------------
