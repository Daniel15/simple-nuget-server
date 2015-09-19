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
 - Uses a single API key (so not suitable for scenarios where multiple users need to be able to push packages)
 - Delete a package version (this feature comes handy while testing package deployment)


Setup
=====

For a Debian-based distro (including Ubuntu), installation is something along the lines of what I've written below. Installation using other Linux distributions will vary but it should be pretty similar.

1 - Copy app to your server, you could do a Git clone on the server or add it as a Git submodule if adding to a site that's already managed via Git:
```bash
cd /var/www
git clone https://github.com/Daniel15/simple-nuget-server.git
# Make db and packages directories writable
chown www-data:www-data db packagefiles
chmod 0770 db packagefiles
```
3 - This solution requires PHP to have support for sqlite. Proceed to install as follows if needed
```bash
# check if pp5-sqlite is installed
dpkg --get-selections | grep php5-sqlite
# install package if not found
apt-get install php5-sqlite
```

2 - Copy nginx.conf.example to /etc/nginx/sites-available/nuget and modify it for your environment:
 - Change `example.com` to your domain name.  In my environment I am using `homenuget.com`. Remember to add this entry to the client host file if needed
 - Root must point to `/var/www/simple-nuget-server/public/`. Do not change this entry
 - Change "hhvm" to the name of a PHP upstream in your Nginx config. This can be regular PHP 5.4+ or HHVM.  This entry needs to be changed for the \.php$ and /index.php locations
 - If hosting as part of another site, prefix everything with a subdirectory and combine the config with your existing site's config (see ReactJS.NET config at the end of this comment)

3 - Edit `inc/config.php` and change `ChangeThisKey` to a randomly-generated string

4 - Enable the site (if creating a new site)
```bash
# create link to folder to enable site
ln -s /etc/nginx/sites-available/nuget /etc/nginx/sites-enabled/nuget
# reload nginx
/etc/init.d/nginx reload
```

5 - Set the API key and test it out. I test using nuget.exe (which is required for pushing)
```
nuget.exe setApiKey -Source http://example.com/ ChangeThisKey
nuget.exe push Foo.nupkg -Source http://example.com/
```
(if using Mono, run `mono nuget.exe` instead)

Examples
========
[ReactJS.NET](http://reactjs.net/) repository at at http://reactjs.net/packages/. You can see its Nginx configuration at https://github.com/reactjs/React.NET/blob/master/site/nginx.conf

Links
========
[Nuget Command Line (windows)](https://docs.nuget.org/consume/command-line-reference) 

Versions
========
- 1.0.1
Includes ability to delete versions of a package and update the package to the next available version. Package record and root directory is deleted when no more version are available
- 1.0.0
Push, Search, List and download capabilities

Licence
=======
(The MIT licence)

Copyright (C) 2014 Daniel Lo Nigro (Daniel15)

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
