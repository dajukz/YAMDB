YAMDB Application
========================
# This Readme is WIP

The "Yet Another Movie Database" is an application made to showcase my ability
to learn new libraries/tools.

Requirements
------------

* PHP 8.2.0 or higher;
* Docker-desktop
* PDO-MySQL PHP extension enabled;
* Composer installed
* and the [usual Symfony application requirements][1].

Installation
------------

Assuming you have the usage of the [Symfony CLI][2], [Composer][3] and Git VCS:

**In your CLI: **  use the `symfony` binary installed
on your computer to run the following commands:

```bash
git clone https://github.com/dajukz/YAMDB.git
git pull remote origin main
````
After this you can install all packages:
```bash
composer install
npm i
```


Usage
-----

Once all is installed is done you can set up the project to start up



```bash
cd my_project/
ddev start
```

Then access the application in your browser at the URL given by ddev or alternatively:
```bash
ddev launch
```

[1]: https://symfony.com/doc/current/setup.html#technical-requirements
[2]: https://symfony.com/download
[3]: https://getcomposer.org/