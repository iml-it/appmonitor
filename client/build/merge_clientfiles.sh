#!/usr/bin/bash

cd $( dirname $0 )/..
outfile=client_all_in_one.php

test -f ${outfile} && rm -f ${outfile}
test -f ${outfile}.tmp && rm -f ${outfile}.tmp


# ----- INIT output file
echo
echo "===== MERGER ======"
echo
echo "<?php
/*

    MERGED APPMONITOR CLIENT :: WORK IN PROGRESS

    generated $( date )

*/" >${outfile}.tmp


# ----- Loop over files to merge
for myfile in \classes/appmonitor-client.class.php \
        classes/appmonitor-checks.class.php \
        plugins/checks/*.php
do
    echo Adding $myfile ...
    php -wq $myfile | sed "s#<?php##g" >> ${outfile}.tmp

done

echo
echo PHP lint of generated file
php -l ${outfile}.tmp 
if [ $? -eq 0 ]; then
    echo moving .tmp file to ${outfile} ...
    mv ${outfile}.tmp ${outfile}
    ls -l ${outfile}
    echo "SUCCESS."
else
    echo Keeping ${outfile}.tmp to anylyze it.
    echo "FAILED :-/"
    exit 1
fi
