Simple NuGet Server
===================

A very simple NuGet server for my personal use, similar to
[NuGet.Server](https://www.nuget.org/packages/NuGet.Server) but in PHP. Designed
for scenarios where a single user (ie. a person or a build server) pushes
packages.

Features
========
 - Basic searching and listing of packages
 - Pushing via NuGet command line (NuGet.exe)
 - Simple set up
 - Stores data in SQLite or MySQL database
 - Uses a single API key (so not suitable for scenarios where multiple users
   need to be able to push packages)
