## Global picture

This is an overview about needed installations.

``` mermaid
flowchart TD

    %% ----- STYLING
    style START fill:#eee,stroke:#888,stroke-width:2px
    style END fill:#eee,stroke:#888,stroke-width:2px

    style LOOPSTART fill:#eee,stroke:#888,stroke-width:2px
    style LOOPEND fill:#eee,stroke:#888,stroke-width:2px

    style SRV fill:#8e8,stroke:#4a3,stroke-width:2px
    style Client fill:#8e8,stroke:#4a3,stroke-width:2px
    
    style D fill:#8e8,stroke:#4a3,stroke-width:2px
    style G fill:#8e8,stroke:#4a3,stroke-width:2px
    style H fill:#8e8,stroke:#4a3,stroke-width:2px

    START((Start))
    END((End))
    LOOPSTART(( ))
    LOOPEND(( ))

    MOREAPPS{On more<br>webapp?}

    SRV[First:<br>Install Appmonitor server]

    START --> SRV --> LOOPSTART
    LOOPSTART --> B[I want to monitor<br>a webapp on<br>another server]
    B --> C{Is it a<br>PHP<br>webapp?}
    C -->|No| D[See description<br>of JSON syntax for<br> client response]
    
    C -->|Yes| Client[Install Appmonitor client]
    Client --> F{Is this PHP webapp<br>supported already?}
    F --> |Yes| G[Start with a<br>pre defined check]
    F --> |No| H[Create a custom check<br>using existing checks]

    D --> LOOPEND
    G --> LOOPEND
    H --> LOOPEND

    LOOPEND --> MOREAPPS

    MOREAPPS --> |Yes| LOOPSTART
    MOREAPPS --> |No| END

```

## Server

**You need a server installation** to have a web interface and to send notifications.
The server needs PHP and can be installed when a machine has PHP installed.

You also can install it on your local machine or a dedicated system

* using Apache httpd/ Nginx and php or
* using docker

You can check the application status in your network or on shared hosting.

👉 See [Server -> Installation](../40_Server/10_Installation.md)

## PHP webapp

You run a PHP application and want to monitor it. Your luck: A PHP client is delivered in the project. 

* Install the Appmonitor client
* Configure the Appmonitor client

### Install the Appmonitor client

On you system with the web application you need to install the Appmonitor client. 
On a shared hosting you can create a subdirectory eg. `[webroot]/appmonitor/`.
If you have more control you can install it outside webroot and add an alias `/appmonitor`. 

👉 See [Install the Appmonitor client](../60_PHP-client/20_Install_PHP-client.md)

### Configure the Appmonitor client

* If you run a PHP application, where a pre defined client check is delivered (see `./client/plugins/apps/`) then you are lucky again and can profit from it: you can start with a preset of application specific checks.<br>👉 See [Use Application checks](../70_PHP-client/60_Plugins/10_Apps/10_Introduction.md)<br><br>

* If not: the folder `./client/plugins/checks/` contains several check items to test http connections, database connections, files and more.<br>You can create your own application check with these check plugins.<br>👉 See [Write Checks](../70_PHP-client/30_Monitor_an_application.md)

## Non-PHP webapp

You need to implement the checks and a JSON response in the given format.

👉 See [Decription of response](../50_Client/20_Description_of_response.md)
