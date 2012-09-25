========================
= PITC v1.0+ TODO LIST =
========================

- Key
* [_] - To Do.
* [X] - Done.
* [%] - In Progress / Researching
* [O] - Unable to implement for certain reasons, see comment.

General
-------
* [_] - Add Mutliserver.
* [%] - Allow scripts to be unloaded.
* [_] - IRC Color Parsing
* [_] - Add SCREEN support

Bugs
----
* [_] - Fix Screen bugs
* [%] - Fix nick bug where it changes to your alternate nick if the nick us in use WHILE connected.

PITC API - Handlers
-------------------
* [X] - Add COMMAND handler
* [X] - Add TEXT handler
* [X] - Add ACTION handler
* [X] - Add CTCP handler
* [X] - Add JOIN handler
* [X] - Add PART handler
* [_] - Add NOTICE handler
* [_] - Add MODE handler
* [_] - Add NICK handler
* [_] - Add KICK handler
* [_] - Add TOPIC Handler
* [X] - Add CONNECT handler
* [_] - Add LOAD handler (Script Loaded)
* [_] - Add START handler (Client Loaded)
* [_] - Add DISCONNECT handler
* [X] - Add RAW handler
* [_] - Add WHOIS handler
* [_] - Add AWAY handler
* [X] - Add TICK handler (When the client ticks over once)

PITC API - Commands
-------------------
* [X] - Add LOG command (Logs to Status window)
* [X] - Add ECHO command (Echo's to specified window else Status, named PECHO due to collision with PHP ECHO)
* [X] - Add MSG command
* [X] - Add NOTICE command
* [X] - Add ACTION command
* [X] - Add QUIT command
* [X] - Add NICK command
* [X] - Add JOIN command
* [X] - Add PART command
* [X] - Add RAW command
* [X] - Add ADDWINDOW command
* [X] - Add DELWINDOW command
* [X] - Add CHECKWINDOW command
* [X] - Add MODE command
* [X] - Add TOPIC command
* [X] - Add CTCP command
* [X] - Add CTCPREPLY command

#### You can use the RAW command to imitate unimplemented commands.

PITC API - Commands - Formatting
--------------------------------
* [X] - Add BOLD formatting
* [X] - Add COLOUR/COLOR formatting
* [X] - Add ITALIC formatting

#### If you have any extra ideas for the API or General Client let me know