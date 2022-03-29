# Requirements #

- PHP7 or 8 (up to 8.1)
- php-curl

# Installation #

## Install the server ##

Below a document root of a website create a directory [webroot]/appmonitor/ (sure it works to install the server into webroot too).

- download zip file from Github: https://github.com/iml-it/appmonitor
- create a subdir "appmonitor" below your webroot 
- extract all files of the public_html subdir from this archive into [webroot]/appmonitor/
- verify the installation with your browser
  <http://localhost/appmonitor/server/>
  You will see a welcome message.
- Go to the setup.
- Add the url <http://localhost/appmonitor/client/check-appmonitor-server.php> to integrate a first check.

## Install server with git ##

To use git you need access to the webserver configuration to be able to define a webroot that must point to the public_html folder.

As root:

```shell
cd /var/www
git clone https://github.com/iml-it/appmonitor.git
chown -R www-data. appmonitor
```

Create a vhost with a webroot on `/var/www/appmonitor/public_html`

Open the Webinterface on webroot, eg. <http://localhost/>

## Use docker ##

The repository includes my development environment.

You need 

- a running rootless docker service
- docker-compose 
- bash
- acl (to use command setfacl)

Clone the git repository and start a bash script.

With your desktop user:

```shell
cd somewhere
git clone https://github.com/iml-it/appmonitor.git
cd appmonitor
./docker/init.sh u
```

It starts up a Apache 2.4 + PHP 8.1 instance and uses the public_html subdir as webroot.
After bringing up the container it starts the monitoring service and shows its log

In the webbrowser open http://localhost:8001/

If you see the welcome message then go back to the terminal. Stop output with `Ctrl` + `C`. You see a menu. Press `i` + `Return` to set permissions for your current user and the apache httpd inside the container.

Optional: If you are back in the menu press `u` + `Return` to start the service script in the container again.

## Production use ##

If you are happy with the first clicks around then continue the next chaprters.

# Used 3rd party tools #

I use several libraries to save time and to use stable components. I just wanna say thank you to all of them.

- [ahCache] (<https://www.axel-hahn.de/docs/ahcache/index.htm>) class to store
   serialized data as file (included)
- [cdnorlocal] (<https://www.axel-hahn.de/docs/cdnorlocal/index.htm>)
- [Icons small-n-flat] (<http://paomedia.github.io/small-n-flat/>)

The web ui loads from CDNJS (but could be stored locally too):

- [AdminLTE] (<https://adminlte.io/>)
- [chartJs] (<https://www.chartjs.org/>)
- [jQuery] (<https://jquery.com/>)
- [Datatables plugin] (<https://datatables.net/>)
- [Font Awesome] (<https://fontawesome.com/>)
- [Vis.js] (<https://visjs.org/>)
