#Fresque

Fresque is a command line tool to manage your php-resque workers.


##Prerequisites

If you don't know what is *resque* or *redis*, take a look at their official website :
- Redis : http://redis.io/
- Resque : https://github.com/defunkt/resque/
- Php-Resque : https://github.com/chrisboulton/php-resque/

This tool is intended to facilitate your life by making interfacing with php-resque more easier and more friendly.
You should already have some knowledge about php-resque, and have php-resque installed and running.
I'll assume in this tutorial that you have sufficient knowledge to start a worker normally with php-resque.


##What is Fresque

Fresque is a command line tool to manage your php-resque workers

	# Starting a worker
    $ QUEUE=file_serve php resque.php

    # Starting a worker with fresque
    $ fresque start -q file_serve

It's more friendly, and provides more options, like `restart`, `stop`, etc …
Php-resque, and resque, by default doesn't provide an out-of-the-box way to stop your workers. You just kill the worker process. With Fresque, you'll enjoy stopping and restarting your workers at gogo. No more system process handling!

##Installation

Clone the git repo

	$ git clone git://github.com/kamisama/Fresque.git

 `cd` to the Fresque folder you just cloned

	$ cd the/fresque/folder/you/just/cloned

Then download Composer

	$ curl -s https://getcomposer.org/installer | php

Finally, install dependencies

	$ php composer.phar install

##Configuration

A fresque.ini file is provided to set your workers default parameters, and other options used by fresque, such as redis connection options.
it's well documented, and you shouldn't have difficulties filling it.


##Usage

*Some examples are available at the end of this page.*

It's often wise to start by adding the fresque executable to your system path.
You then just call

	$ fresque <command>

Or if you didn't add it,

	$ cd /path/to/the/resque/executable
	$ ./fresque <command>



There's a bunch of interesting commands available :

* **start**

To start a new resque worker. By default, it will use the default configuration defined in you fresque.ini for the queue name, the pooling frequency, and other various options. You can override all of them with an option flag. Available options :

> `-u` or `--user` : User running the php process. Should be the user running your php application, usually **www-data** for apache. Using a different user could lead to permissions problems in some cases.
>
> `-q` or `--queue` : A list of queues name, separated with a comma.

> `-i` or `--interval` : Pooling frequency. Number of seconds between each pooling.

> `-n` or `--workers` : Number of workers working on the same queue. Uses pcntl to fork the process, ensure that you PHP is compiled with it.

> `-l` or `--log` : Absolute path to the log file. You can set a different log for each worker. The `--user` must have permission to read and write to that file (and the parent folder, since the file will be created if non-existent).


For creating multiple queues with different options, just run `start` again.


* **stop**

To stop workers. Will wait for all jobs to finish, then stop the worker. If more than one worker is running, you'll have to choose the worker to stop from a worker menu.

> `-f` or `--force` : Force shutdown, without waiting for the jobs to finish. All jobs will fail.
> `-w` or `--all` : Stop all workers, skipping the worker menu.

* **pause**

To pause workers. Like with `stop`, a you'll be prompted with a worker list if more than one worker is available.

> `-w` or `--all` : Stop all workers, skipping the worker menu.

* **resume**

To resume paused workers. Again, you'll be prompted with a worker list if there is more than one paused worker.

> `-w` or `--all` : Stop all workers, skipping the worker menu.

* **restart**

To restart all the workers, keeping their settings.

* **load**

To start a batch of pre-defined workers (set in your configuration file). See fresque.ini for more informations.

* **stats**

Display total number of failed/processed jobs, as well as various stats for each workers.

* **tail**

Tail the workers' logs. If you have more than one log file, you'll have to choose the log to tail from a log file menu.

* **enqueue**

Add a job to a queue. Takes 3 arguments :

> **queuename**  : Name of the queue you will enqueue this job to
> **jobclass** : Name of the class that will perform the job, and that your application autoloader will have to load.
> **arguments** : comma separated list of arguments, passed to the job.

Will print the **Job ID** if the job is successfully enqueued.

*Successfully enqueuing a job does not means it will perform it successfully*


* **test**

Test your configuration. If no options are provided, it will test you *fresque.ini*. It accepts all options, to let you test them.

Finally, there's some global options, that can be used for all commands. Default value in your config file will be used unless you use these.

> `-s` or `--host` : Redis hostname

> `-p` or `--port` : Redis port

> `-b` or `--lib` : Absolute path to the php-resque library. Used when you already have your own, and don't want to use the one shipped with fresque.

> `-c` or `--config` : Absolute path to your config file. You can use different config for different workers. Default one is *fresque.ini*, in the same directory as the executable.

> `-a` or `--autoloader` : Absolute path to your application entry point. Usually, it should be the file that will autoload all your job classes.


##Examples

Let's start a worker with the default settings defined in the config file (fresque.ini)

	$ fresque start

Let's start another worker, named *activity*, with a pooling frequency of 1 second. Also. I want two workers working on this queue.

	$ fresque start -q activity -i 1 -n 2

Then, I want another worker, that will work on the queues *default* and *activity* at the same time.

	$ fresque start -q default,activity

Oh wait, I have another resque on another redis server. I want to log its activities in an other log file : remote.log

	$ fresque start -s 192.168.1.26 -p 6390 -q myspecialqueue -l /path/to/remote.log

If you have your config file elsewhere, and your php-resque lib elsewhere also

	$ fresque start -c /path/to/my-config.ini -b /path/to/my/custom/php-resque

To view stats of your workers (to know how many you have, processed/failed jobs count, uptime)

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


Remember that you can use the global options (-s, -p etc …) with any command

	$ fresque stop -c /path/to/my-config.ini -s 192.168.1.26
Let's enqueue a job to the *activity* queue

	$ fresque enqueue activity PageVisit 5 /index.php 158745693

php-resque will then run this job, by instantiating the class `PageVisit`, then calling the `perform()` method with the arguments `5`, `/index.php` and `158745693`.
In order to instantiate the PageVisit class, php-resque should know where to find it. That should be done with you application autoloader (`--include`)


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

See notes if it says **There were no active workers to kill …** but you're sure there are some.

###Starting all the workers at once

We've just created 6 workers, calling `start` 5 times (remember, the second `start` create 2 workers with `-n 2`). But there's a way to start all of them with only one command, useful when you have a lot of workers that you have to start each time.

Just set all your workers settings in the config file in the [Queues] section (walkthrough available in the config file), then start all of them with

	$ fresque load


##Notes

###You can test your config with `test`

A testing tool for testing your configuration file is provided, just call `test`.
This will test the minimum requirements to run fresque :

* Your redis hostname and port are not null
* You can connect to the redis server with the specified hostname:port
* The log file exist and is writeable, or if it doesn't exists, the parent directory is writeable
* The path to the php-resque libraries is valid
* The path to your application autoloader is valid

This will not test the content of your application autoloader, so if there's something inside triggering a fatal error, fresque will not know, and return a *success*, but the worker will fail to start, like we just said above.

You can test more than the settings inside your config file, by passing options. An option will override the setting defined in the config

	$ fresque test -s 195.168.1.26 -p 6890

This will test your config file, but with the specified redis hostname and port.

You can also test an other config file

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

###`stop` command doesn't stop workers

This happens when you try to stop your workers, but it says **There were no active workers to kill …**. And a look with `stats` will tell you that there are. This occurs when the `--user` doesn't have sufficient permissions to kill the worker process, or the worker PID was 'changed'. In some weird case, the worker fork a child to execute the job, but doesn't switch back to the parent job after. The child become the parent worker, but doesn't notify Resque, and the worker list is outdated.

The only way to kill these 'stray' worker are to find their pid and kill them manually.

		ps aux | grep resque.php

It will print a list a process. Find the PID of the stray workers and kill them

		sudo kill YOUR_PID

##Notes

###Consult your logs

Logs tell you all you need to know about the issue of a job, and the current status of your php-resque workers. It tells you when a job is enqueued, when a job is about to being performed, and its issue (success/fail). It also display all php related errors that may occurs.
Check them frequently, as fresque doesn't capture those errors.


##Background

Fresque is a derivated works from my other plugin, [cake-resque](https://github.com/kamisama/Cake-Resque), a command line tool to manage php-resque, but inside cakephp console.
Very convenient, but limited to only cakephp framework. I wanted to release a tool that can work in any php environment.

##Credits

* [PHP-Resque](https://github.com/chrisboulton/php-resque) is written by Chris Boulton
* Based on [Resque](https://github.com/defunkt/resque) by defunkt
* Fresque by Wan Qi Chen (kamisama)
