#!/bin/bash

MYconfig=$( dirname $0 )/couscous.yml
typeset -i iPreSpaces=10
typeset -i MYiSpaces=4
DEBUG=false

# ----------------------------------------------------------------------
# FUNCTIONS
# ----------------------------------------------------------------------

function _wd(){
    test $DEBUG = true && echo "; $*"
}

function _addMenuitem(){
    local _mymdfile=$1
    local _mybase=$( echo ${_mymdfile} | sed "s#\.md\$##" | sed "s#^./##")
    local _myhtml="${_mybase}.html"

    typeset -i local _iLevel=$( tr -dc '/' <<<"${_mybase}" | wc -c )
    typeset -i local _iSpaces=$iPreSpaces+$_iLevel*2*$MYiSpaces


    grep "${_mybase}:" $MYconfig >/dev/null
    if [ $? -ne 0 ]; then
        _wd "_addMenuitem ${_mybase} - need to add"
        printf %${_iSpaces}s${_mybase}:\\n

        printf %${_iSpaces}s
        printf %${MYiSpaces}s
        echo "text: \"${_mybase}\""

        printf %${_iSpaces}s
        printf %${MYiSpaces}s
        echo "relativeUrl: \"${_myhtml}\""
    else
        _wd "SKIP _addMenuitem ${_mybase}"
    fi

    grep "^currentMenu: ${_mybase}$" $MYconfig >/dev/null
    if [ $? -ne 0 ]; then
        _wd "_addMenuitem ${_mybase} - need to add metainfo in md file: currentMenu: ${_mybase}"
    fi
    ls -1 ${_mybase} >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        printf %${_iSpaces}s
        printf %${MYiSpaces}s
        echo "items:"
        _scanDir "${_mybase}/"
    fi
}

function _scanDir(){
    local _mydir=$1
    _wd "_scanDir $_mydir"
    ls -1 ${_mydir}*.md | grep -v "/_" | sort | while read mymdfile
    do
        _addMenuitem "$mymdfile"
    done    
}

# ----------------------------------------------------------------------
# MAIN
# ----------------------------------------------------------------------

ls -l $MYconfig
_addMenuitem "readme.md"
_scanDir ""

