# Requirements #

- PHP7 or 8 (up to 8.3)
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

You will see a welcome message. Go to the settings page and add this url:
`http://localhost/client/check-appmonitor-server.php`
This is the self check of the appmonitor server.

## Using a Docker container ##

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
./docker/init.sh
```

There you have a menu. 

```txt
>>>>> MENU
   g  - remove git data of starterkit

   i  - init application: set permissions
   t  - generate files from templates
   T  - remove generated files

   u  - startup containers    docker-compose ... up -d
   U  - startup containers    docker-compose ... up -d --build
   s  - shutdown containers   docker-compose stop
   r  - remove containers     docker-compose rm -f

   m  - more infos
   o  - open app [appmonitor] http://localhost:8001/
   c  - console (bash)
   p  - console check with php linter

   q  - quit
```

From top down press ...

- `i` - it sets permissions for your current user and the apache httpd user of the container onto the public_html directory.
- `t` - generate configuration files for the docker container and docker compose.
- `u` - start container

Remark: `i` and `t` are needed only once.

It starts up a Apache 2.4 + PHP 8.3 instance and uses the public_html subdir as webroot.
After bringing up the container it starts the monitoring service and shows its log.

In the webbrowser open <http://localhost:8001/>. You will see a welcome message. Go to the settings page and add this url:
`http://localhost/client/check-appmonitor-server.php`
This is the self check of the appmonitor server.

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
