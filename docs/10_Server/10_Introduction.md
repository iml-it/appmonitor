<style>
	.mark{background:#fc0; color:#f22;}
	.optional{color:#888;}
</style>

[TOC]
# Requirements #

- PHP7 or 8 (up to 8.1)
- php-curl

The server uses

- [ahCache] (<https://www.axel-hahn.de/docs/ahcache/index.htm>) class to store
   serialized data as file (included)
- [cdnorlocal] (<https://www.axel-hahn.de/docs/cdnorlocal/index.htm>)
- [Icons small-n-flat] (<http://paomedia.github.io/small-n-flat/>)

... and loads from CDNJS (but could be stored locally too):

- [AdminLTE] (<https://adminlte.io/>)
- [chartJs] (<https://www.chartjs.org/>)
- [jQuery] (<https://jquery.com/>)
- [Datatables plugin] (<https://datatables.net/>)
- [Font Awesome] (<https://fontawesome.com/>)
- [Vis.js] (<https://visjs.org/>)

# Installation #

## Install the server ##

Below a document root of a website create a directory [webroot]/appmonitor/.

Remark: sure it works to install the server into webroot.

- copy all files of the public_html subdir from this archive into [webroot]/appmonitor/
- verify the installation with your browser
  <http://localhost/appmonitor/server/>
  You will see a welcome message.
- Go to the setup.
- Add the url <http://localhost/appmonitor/client/check-appmonitor-server.php> to integrate a first check.

## Production use ##

If you are happy with the first tests then continue the next chaprters.

