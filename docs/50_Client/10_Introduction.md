## How does it work?

The main idea is to make the checks with permissions of the application and with its credentials. Check if directories or files are writable, a connection to services like databases, email, external http APIs/ ressources, ... whatever.

An application check finally creates a JSON in a predefined structure.

The Appmintorserver handles this result to render the application status and sends notifications.

![Client](images/appmonitor-overview-client.png "Client")

## Requirements

The Appmonitor repository contains the server and the client - written in PHP.

You can implement a client in other languages. Your script must be able to generate
the monitorng data in the given JSON structure. You can respond the data directly
or create static json files.

Another option is using the compiled binary that contains all PHP checks. Instead of writing php code to perform the checks all checks are taken from an INI file.
📗 <https://os-docs.iml.unibe.ch/appmonitor-cli-client/>
