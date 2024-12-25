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
    style InstallClient fill:#8e8,stroke:#4a3,stroke-width:2px
    
    style UseAppCheck fill:#8e8,stroke:#4a3,stroke-width:2px
    style SeeDescription fill:#fb4,stroke:#da3,stroke-width:2px
    style CreateChecks fill:#fb4,stroke:#da3,stroke-width:2px

    %% ----- NODES

    START((Start))
    END((End))
    LOOPSTART(( ))
    
    SRV[First:<br>Install Appmonitor server]
    WannaMonitor[I want to monitor<br>a webapp<br>on a server]
    IsPHP{Is it a<br>PHP<br>webapp?}

    SeeDescription[See description<br>of JSON syntax for<br> client response]
    InstallClient[Install Appmonitor client]
    SupportedApp{Is this PHP webapp<br>supported already?}
    UseAppCheck[Start with a<br>pre defined check]
    CreateChecks[Create a custom check<br>using existing checks]

    LOOPEND(( ))
    MOREAPPS{One more<br>webapp?}


    %% ----- NODES

    START --> SRV --> LOOPSTART
    LOOPSTART --> WannaMonitor
    WannaMonitor --> IsPHP
    IsPHP -->|No| SeeDescription
    
    IsPHP -->|Yes| InstallClient
    InstallClient --> SupportedApp
    SupportedApp --> |Yes| UseAppCheck
    SupportedApp --> |No| CreateChecks

    SeeDescription --> LOOPEND
    UseAppCheck --> LOOPEND
    CreateChecks --> LOOPEND

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
