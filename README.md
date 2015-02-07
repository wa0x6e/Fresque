#Fresque [![Build Status](https://travis-ci.org/kamisama/Fresque.png?branch=master)](https://travis-ci.org/kamisama/Fresque) [![Coverage Status](https://coveralls.io/repos/kamisama/Fresque/badge.png?branch=fix-travis)](https://coveralls.io/r/kamisama/Fresque?branch=fix-travis) [![Dependency Status](https://www.versioneye.com/php/fresque:fresque/badge.png)](https://www.versioneye.com/php/fresque:fresque) [![Latest Stable Version](https://poser.pugx.org/fresque/fresque/v/stable.png)](https://packagist.org/packages/fresque/fresque)

> Fresque is a command line tool to manage your php-resque workers.

##Prerequisites

If you don't know what is *resque* or *redis*, take a look at their official website :

- Redis : http://redis.io/
- Resque : https://github.com/resque/resque/
- Php-Resque : https://github.com/chrisboulton/php-resque/

This tool is intended to facilitate your life by making interfacing with php-resque more easier and more friendly.
You should already have some knowledge about php-resque, and have php-resque installed and running.
I'll assume in this tutorial that you have sufficient knowledge to start a worker normally with php-resque.

##Requirements

* Redis
* `sudo` package installed on your system

php-resque will be installed automatically as a composer dependency.

##What is Fresque

Fresque is a command line tool to manage your php-resque workers

	# Start a worker without fresque
    $ QUEUE=file_serve php resque.php

    # Starting a worker with fresque
    $ fresque start -q file_serve

It's more friendly, and provides more options, like `restart`, `stop`, etc …
Php-resque, and resque, by default doesn't provide an out-of-the-box way to stop your workers. You have to directly kill the worker process. With Fresque, you'll enjoy stopping and restarting your workers at gogo. No more system process handling!

##Installation

### By cloning the git repo

	$ git clone git://github.com/kamisama/Fresque.git

 `cd` to the Fresque folder you just cloned

	$ cd Fresque

Then download Composer

	$ curl -s https://getcomposer.org/installer | php

Finally, install dependencies

	$ php composer.phar install

### Using Composer

If your application is already using Composer, just add Fresque in your composer dependencies

    "require": {
        "fresque/fresque": "~1.2.0"
    }

and update the dependencies with `composer update`

##Configuration

A fresque.ini file is provided to set the workers default parameters, and other options used by fresque, such as redis connection options.
It's well documented, and you shouldn't have difficulties editing it.

##Usage

For convenience, you should add the fresque executable to your system path.
You can just then call fresque with

	$ fresque <command>

Or if you didn't add it,

	$ cd /path/to/fresque
	$ ./fresque <command>

If installed as a composer dependency, it's also available in composer bin folder

    $ vendor/bin/fresque <command>

There's a bunch of interesting commands available :

*Examples are available at the end of this section.*

* **start**

To start a new resque worker. By default, it will use the default configuration defined in you fresque.ini for the queue name, the pooling frequency, and other options. You can override all of them with an option flag. Available options :

> `-u` or `--user` : User running the php process. Should be the user running your php application, usually **www-data** for apache. Using a different user could lead to permissions problems.
>
> `-q` or `--queue` : A list of queues name polled by the worker, separated with a comma.

> `-i` or `--interval` : Polling frequency. Number of seconds between each polling.

> `-n` or `--workers` : Number of workers working on the same queues.

> `-l` or `--log` : Absolute or relative path to the log file. You can set a different log for each worker.
> The `--user` must have permission to read and write to that file (and the parent folder, since the file will be created if non-existent).
> Relative path is relative to the fresque folder.


* **startScheduler**

To start the scheduler worker.

> `-i` or `--interval` : Polling frequency. Number of seconds between each polling.

Scheduler worker is disabled by default, enable it in the configuration file, in the [Scheduler] section. When enabled, it'll be automatically started when using `load()`.

*Only one scheduler worker can run at the same time*


* **stop**

To stop workers. Will wait for all jobs to finish, then stop the worker. If more than one worker is running, a list of workers will be displayed, to choose the worker to stop.

> `-f` or `--force` : Stop worker immediately, without waiting for the current job to finish processing. This will fail the current job.

> `-w` or `--all` : Stop all workers at once, skipping the worker menu.

> `-q` or `--queue` : Stop all workers attributed to the specified queue name.

> `-o` or `--count` : An additional optional which can be specified along with the `--queue` option to stop a fixed number of workers from the queue.

* **pause**

To pause workers. Similary to `stop`, you'll be prompted with a worker list if more than one worker is available.

> `-w` or `--all` : Stop all workers, skipping the worker menu.

* **resume**

To resume paused workers. Again, you'll be prompted with a worker list if there is more than one paused worker.

> `-w` or `--all` : Stop all workers, skipping the worker menu.

* **restart**

To restart all the workers, keeping their settings.

* **load**

To start a batch of pre-defined workers (set in your configuration file). See fresque.ini for more informations.

* **stats**

Display total number of failed/processed jobs, as well as various stats for each workers and queues.

* **tail**

Tail a worker's log. If you have more than one log file, you'll be prompted with list of log.

* **enqueue**

Add a job to a queue. Takes 3 arguments :

> **queuename**  : Name of the queue you will enqueue this job to
> **jobclass** : Name of the class that will perform the job, and that your application autoloader will have to load.
> **arguments** : comma separated list of arguments, passed to the job.

Will print the **Job ID** if the job is successfully enqueued.

*Successfully enqueuing a job does not mean it will perform it successfully*

* **test**

Test your configuration. If no options are provided, it will test your *fresque.ini*. It accepts all type of options.

Finally, there's some global options, that can be used for all commands. Default value in your config file will be used unless you use these.

> `-s` or `--host` : Redis hostname

> `-p` or `--port` : Redis port

> `-b` or `--lib` : Absolute path to the php-resque library. Used when you already have your own, and don't want to use the one shipped with fresque.

> `-c` or `--config` : Absolute path to your config file. You can use different config for different workers. Default one is *fresque.ini*, in the same directory as the executable.

> `-a` or `--autoloader` : Absolute path to your application entry point. Usually, it should be the file that will autoload all your job classes.


##Examples

Let's start a worker with the default settings defined in the config file (fresque.ini):

	$ fresque start

Let's start another worker, polling the *activity* queue, with a polling frequency of 1 second. Also, we'll want to have two workers working on this queue:

	$ fresque start -q activity -i 1 -n 2

If we want another worker, working on the queues *default* and *activity* at the same time:

	$ fresque start -q default,activity

Oh wait, we have another resque on another redis server. we'll want to log its activities in another log file: remote.log

	$ fresque start -s 192.168.1.26 -p 6390 -q myspecialqueue -l /path/to/remote.log

- -s 192.168.1.26 is the address of the redis server
- -p 6390 is the redis server port
- -q is the queuename
- -l is the path to the log file

If you have your config file elsewhere, and your php-resque lib elsewhere also

	$ fresque start -c /path/to/my-config.ini -b /path/to/my/custom/php-resque

To view stats of your workers (to know how many you have, processed/failed jobs count, uptime, etc ...)

	$ fresque stats

It should output something like that

	-----------------
    Resque statistics
    -----------------

	Jobs Stats
	   Processed Jobs : 18,617
	   Failed Jobs    :    319

    Queues Stats
       Queues count : 3
            - default           : 0 pending jobs
            - myspecialqueue    : 0 pending jobs
            - activity          : 0 pending jobs

	Workers Stats
	   Active Workers : 6
		Worker : KAMISAMA-MAC.local:33197:default
		 - Started on     : Wed May 16 00:33:04 EDT 2012
		 - Uptime         : less than a minute
		 - Processed Jobs : 0
		 - Failed Jobs    : 0
		Worker : KAMISAMA-MAC.local:33207:default
		 - Started on     : Wed May 16 00:33:08 EDT 2012
		 - Uptime         : less than a minute
		 - Processed Jobs : 0
		 - Failed Jobs    : 0
		Worker : KAMISAMA-MAC.local:33215:myspecialqueue
		 - Started on     : Wed May 16 00:33:10 EDT 2012
		 - Uptime         : less than a minute
		 - Processed Jobs : 0
		 - Failed Jobs    : 0
		Worker : KAMISAMA-MAC.local:33232:activity
		 - Started on     : Wed May 16 00:33:16 EDT 2012
		 - Uptime         : less than a minute
		 - Processed Jobs : 0
		 - Failed Jobs    : 0
		Worker : KAMISAMA-MAC.local:33233:activity
		 - Started on     : Wed May 16 00:33:16 EDT 2012
		 - Uptime         : less than a minute
		 - Processed Jobs : 0
		 - Failed Jobs    : 0
		Worker : KAMISAMA-MAC.local:33223:default,activity
		 - Started on     : Wed May 16 00:33:13 EDT 2012
		 - Uptime         : less than a minute
		 - Processed Jobs : 0
		 - Failed Jobs    : 0

Let's stop all workers from the *default* queue and one from the *activity* queue

	$ fresque stop -c /path/to/my-config.ini -q default
	$ fresque stop -c /path/to/my-config.ini -q activity -o 1

Remember that you can use the global options (-s, -p etc …) with any command

	$ fresque stop -c /path/to/my-config.ini -s 192.168.1.26
	
Let's enqueue a job to the *activity* queue

	$ fresque enqueue activity PageVisit "5,/index.php,158745693"

php-resque will then run this job, by instantiating the class `PageVisit`, then calling the `perform()` method with the arguments `5`, `/index.php` and `158745693`.

In order to instantiate the PageVisit class, php-resque should know where to find it. That should be done inside your application autoloader (either by using an autoloader, of an include/require).

Oh, and if you want to restart all your workers for whatever reasons

	$ fresque restart

If you're finished, and want to stop all workers, just

	$ fresque stop --all

It'll spout something like that

	----------------
    Stopping workers
    ----------------
	Killing 6 workers ...
	Killing 33197 … Done
	Killing 33207 … Done
	Killing 33215 … Done
	Killing 33232 … Done
	Killing 33233 … Done
	Killing 33223 … Done

###Starting all your favorites workers at once

We've just created 6 workers, calling `start` 5 times (remember, the second `start` create 2 workers with `-n 2`). But there's a way to start all of them with only one command, useful when you have a lot of workers that you have to start each time.

Just set all your workers settings in the config file in the [Queues] section (walkthrough available in the config file), then start all of them with

	$ fresque load


##Notes

###You can test your config with `test`

A testing tool for testing your configuration file is provided, by calling `test`.
It will test the minimum requirements to run fresque :

* Your redis hostname and port are not null
* You can connect to the redis server with the specified hostname:port
* The log file exist and is writeable, or if it doesn't exists, the parent directory is writeable
* The path to the php-resque libraries is valid
* The path to your application autoloader is valid

You can test more than the settings inside your config file, by passing options. An option will override the setting defined in the config

	$ fresque test -s 195.168.1.26 -p 6890

This will test your config file, but with the specified redis hostname and port.

You can also test another config file

	$ fresque test -c /my/other/config/file.ini

A test result will looks like

    ---------------------
    Testing configuration
    ---------------------
    Testing configuration
	Redis configuration .....OK
	Redis server ............OK
	Log File ................OK
	PHPResque library .......OK
	Application autoloader ..OK

	Your settings seems ok

##Known issues

###`stop` command doesn't behave as expected

#### Case 1: it says 'no such process' when stopping a worker

This happens when the saved list of workers is not synchronized with the real state of the workers on the system.
A worker was stopped, and fresque wasn't notified.

The main and usual cause is that you didn't stop the workers with the `stop` command, like when you reboot the machine, without stopping the workers beforehand.

**Solution:** Stop all your workers using `stop --all`, then use `reset` to clear fresque cache, and start your workers again.

#### Case 2: it says 'no permission'

You don't have permission to manipulate the worker process.

**Solution:** You have to start and stop the worker with the same user. If you used `start --user=www-data` to start your worker, use `stop --user=www-data` to stop it. To avoid that type of problem, always use the same user for all your workers (usually the apache user), and set it by default in the fresque.ini.

#### Case 3: the stopped worker sometime still appear in the worker list for a few seconds

You just used `stop`, and it stopped a worker. You immediately use `stop` again, and the worker is still here.

**Solution:** Do nothing. It's perfectly normal, stopping a worker can take some time (it'll wait for the job to finish, or wait for the next polling to stop).

##Notes

###Consult your logs

Logs tell you all you need to know about the issue of a job, and the current status of your php-resque workers. It tells you when a job is enqueued, when a job is about to being performed, and its final state (success/fail). It also display all php related errors that may occurs.
Check them frequently, as fresque doesn't capture those errors.

##Sudo

> sudo is a program for Unix-like computer operating systems that allows users to run programs with the security privileges of another user

Since you're usually not logged in on the shell under the same user as the one your webserver us running under, `sudo` is required to start and manipulate the workers on behalf of the php user.

Starting your workers under another user could lead to permission problems.

##Background

Fresque is a derivated works from my other plugin, [cake-resque](https://github.com/kamisama/Cake-Resque), a command line tool to manage php-resque, but inside cakephp console.
Very convenient, but limited to only cakephp framework. I wanted to release a tool that can work anywhere, as long as you have a terminal.

##Credits

* [PHP-Resque](https://github.com/chrisboulton/php-resque) is written by Chris Boulton
* Based on [Resque](https://github.com/defunkt/resque) by defunkt
* Fresque by Wan Qi Chen (kamisama)
