#!/usr/bin/bash
# ======================================================================
#
# CLIENT MERGER :: WORK IN PROGRESS
#
# This bash script generates a single php file containing all classes
# and check plugins.
#
# ----------------------------------------------------------------------
# 2021-11-05  v0.1  www.axel-hahn.de    1st initial working version
# 2024-07-23  v0.2  axel.hahn@unibe.ch  update quoting + shellcheck fixes
# ======================================================================

# ----------------------------------------------------------------------
cd "$( dirname "$0" )/.." || exit
outfile=classes/client_all_in_one.php

packmethod=1

test -f "${outfile}" && rm -f "${outfile}"
test -f "${outfile}".tmp && rm -f "${outfile}.tmp"

# ----------------------------------------------------------------------
echo
echo "===== MERGER ======"
echo
echo "--- merge files"
echo "<?php
/*

    MERGED APPMONITOR CLIENT :: WORK IN PROGRESS

    generated $( date )

*/" >${outfile}.tmp


# ----- Loop over files to merge
for myfile in \
	plugins/checks/*.php \
	classes/appmonitor-checks.class.php \
	classes/appmonitor-client.class.php
do
    echo -n "Adding $myfile ... "
	typeset -i iCountPhp; 
	iCountPhp=$( grep -c "<?php" "$myfile"  )
	if [ $iCountPhp -lt 1 ]; then
		echo "ERROR: <?php was found $iCountPhp times in $myfile. ABORTING MERGE."
		exit 1
	fi

	# REMARK:
	# those replacements are NOT safe ... but I know that I have all functions
	# on a single line.
	phptmp="/tmp/$( basename "$myfile" )"

	case "$packmethod" in 
		1)
			sed "s#require[\ \_].*;##g" "$myfile" >"$phptmp"
			;;
		2)
			cat "$myfile" >"$phptmp"
			;;
	esac

	php -wq < "$phptmp" \
		| sed "s#<?php##g" \
		>> "${outfile}.tmp"  || exit

	# sed "s#\;#;\n#g" ${outfile}.tmp | grep require_once  && exit
	echo "OK"

done
echo

# ----------------------------------------------------------------------
echo "--- PHP lint of generated file"
if ! php -l "${outfile}.tmp" ; then
    echo "Keeping ${outfile}.tmp to anylyze it."
    echo "FAILED :-/"
    exit 1
fi

echo "Moving .tmp file to ${outfile} ..."
mv ${outfile}.tmp ${outfile}
ls -l ${outfile}
echo 

# ----------------------------------------------------------------------
echo "--- short test of merged client"
# php $( dirname $0 )/test_client_all_in_one.php || exit
php "build/test_client_all_in_one.php" || exit
echo

echo "SUCCESS."

# ----------------------------------------------------------------------
