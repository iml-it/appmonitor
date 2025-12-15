# TARGET: docker/containers/php-fpm/dockerfile-entrypoint.sh
#!/bin/bash
set -e
function _h1(){
    echo
    echo
    echo "---===###|  {{APP_NAME}} PHP-FPM  |  $1"
    echo
}

function _run(){
    $* &
}
# ----------------------------------------------------------------------
# MAIN
# ----------------------------------------------------------------------

startup="{{APP_ONSTARTUP}}"
if [ -z "$startup" ]; then
    echo "SKIP: No service script was set as APP_ONSTARTUP"
else
    _h1 "Start service script"
    echo $startup
    _run "$startup"
fi

# ----------------------------------------------------------------------

_h1 "Starting '$*'"
# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php "$@"
fi

exec "$@"

# ----------------------------------------------------------------------
