YAMDB Application
========================
# This Readme is a WIP

The "Yet Another Movie Database" is an application made to showcase my ability
to learn new libraries/tools.

## Requirements


* PHP 8.2.0 or higher;
* Docker-desktop
* PDO-MySQL PHP extension enabled;
* Composer installed
* and the [usual Symfony application requirements][1].

## Installation


Assuming you have the usage of the [Symfony CLI][2], [Composer][3] and Git VCS:

**In your CLI: **  use the `git` command to clone the YAMDB repo:

```bash
git clone https://github.com/dajukz/YAMDB.git
````
This should have created a folder YAMDB, you can check this using <br> `ls -al` <br>
After this you can pull from origin:
```bash
cd YAMDB/
git pull origin main
````

Now you are ready to start the server and go to the webpage on your browser once the
server has been started!


## Usage


### First Start:

On your first start you will have to do 4 things:

* Let ddev create all images and Container
* Install all composer packages
* Install all npm modules
* Populate the database _(will take a while, [+-20min](DB-population-explanation))_

```bash
ddev start
ddev composer install
ddev npm i
```
Once those are successful you can start by filling in your .env file: <br>
`cp .env .env.local` <br>
Then you can fill in your .env.local file with your own personal data <br> 
These need to be filled in:
* `DATABASE_URL`
* `TMDB_TOKEN`

For me the `DATABASE_URL` constant looked like this:
`mysql://{user}:{password}@YAMDB-db:3306/app?serverVersion=8.0.33&charset=utf8mb4`<br>
and `{user}` and `{password}` need to be replaced by the database user and its respective password. <br>

The `TMDB_TOKEN` is a Bearer token you can get when you have an account from TMDB API.


After filling in these in your .env.local you can start populating the database:
```bash
ddev ssh
php bin/console doctrine:fixtures:load
```

## Extra

You can access the application in your browser at the URL 
given by ddev or by running in your CLI:

```bash
ddev launch
```


#### DB population Explanation

Because the population of the database happens by fetching a ton of data from an API
the command doing this has a built-in limiter to make sure it doesn't get blocked
by the rate-limiter of TMDB API. Because of this it might take a while to load the fixture.

While the fixture is running you will be able to see the logs at
`/var/log/dev.log` So if there is any issue it will appear there. 
You will also be able to see the progress of the command.

If the command takes too long for your liking, you can always go change 
the iterations in `/src/DataFixtures/AppFixtures.php`. 
You can do this by changing the iterator `$i` on line 38 from 5 to something lower.
Keep in mind though that the command only flushes (and inserts into db) 
every 100 iterations, so per 2000 movies roughly.

[1]: https://symfony.com/doc/current/setup.html#technical-requirements
[2]: https://symfony.com/download
[3]: https://getcomposer.org/