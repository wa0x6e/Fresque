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

Fresque is a command line tool.
But instead of starting a worker with
	
	QUEUE=file_serve php resque.php
	
you do

	fresque start -q file_serve
	
It's more friendly, and provides more options, like `restart`, `stop`, etc …  
Php-resque, and resque, by default doesn't provide an out-of-the-box way to stop your workers. You just kill the worker process. With Fresque, you'll enjoy stopping and restarting your workers at gogo.

##Installation

Clone the git repo

	git clone --recursive git://github.com/kamisama/Fresque.git
	
###Install dependencies	

####php-resque

`--recursive` flag when cloning the repository is needed to download the *php-resque* vendor library. If you already have it installed elsewhere, ignore it. In that case, you'll have to configure the tool to point to its location.

php-resque is not included in the package, if you opt to download the compressed file instead of cloning the repo. You can then find it [there](https://github.com/chrisboulton/php-resque/).

####ZetaComponents

[Composer](http://getcomposer.org/) will install it for you. `cd` to the Fresque folder you just cloned

	cd the/fresque/folder/you/just/cloned
	
Then download Composer

	curl -s https://getcomposer.org/installer | php
	
Finally, install dependencies

	php composer.phar install

##Configuration

A fresque.ini file is provided to set your workers default parameters, and other options used by fresque, such as redis connection options.  
it's well documented, and you shouldn't have difficulties filling it.


##Usage

*Some examples are available at the end of this page.*

It's often wise to start by adding the fresque executable to your system path.
You then just call 

	fresque <command>
	
Or if you didn't add it,

	cd /path/to/the/resque/executable
	./fresque <command>
	

	
There's a bunch of interesting commands available :

* **start**

To start a new resque worker. Be default, it will use the default configuration defined in you fresque.ini for the queue name, the pooling frequency, and other various options. You can override all of them with an option flag. Available options :

> `-u` or `--user` : User running the php process. Should be the user running your php application, usually **www-data** for apache. Using a different user could lead to permissions problems in some cases.
> 
> `-q` or `--queue` : A list of queues name, separated with a comma.

> `-i` or `--interval` : Pooling frequency. Number of seconds between each pooling.

> `-n` or `--workers` : Number of workers working on the same queue. Uses pcntl to fork the process, ensure that you PHP is compiled with it.

> `-l` or `--log` : Absolute path to the log file. You can set a different log for each worker. The `--user` must have permission to read and write to that file (and the parent folder, since the file will be created if non-existent).


For creating multiple queues with different options, just run `start` again.


* **stop**

To shutdown all resque workers. Will wait for all jobs to finish, then shutdown all workers.

> `-f` or `--force` : Force shutdown, without waiting for the jobs to finish. All jobs will fail.

* **restart**

To restart all the workers, with their previous settings.

* **load**

To start a batch of pre-defined queues (set in your configuration file). See fresque.ini for more informations.

* **stats**

Display total number of failed/processed jobs, as well as various stats for each workers.

* **tail**

Tail the workers' logs.

* **enqueue**

Enqueue a job. Should be used for testing purpose only. Takes 3 arguments :
 
> **queuename**  : Name of the queue you will enqueue this job to  
> **jobclass** : Name of the class that will perform the job, and that your application autoloader will have to load.  
> **arguments** : Other arguments you want to pass to your jobs.

*Successfully enqueuing a job does not means it will perform it successfully. See notes*


* **test**

Test your configuration. If no options provided, it will test you fresque.ini. It accepts all options, to let you test them.

Finally, there's some global options, that can be used for all commands. Default value in your config file will be used unless you use these.

> `-s` or `--host` : Redis hostname

> `-p` or `--port` : Redis port

> `-b` or `--lib` : Absolute path to the php-resque library. Used when you already have your own, and don't want to use the one shipped with fresque.

> `-c` or `--config` : Absolute path to your config file. You can use different config for different workers. Default one is *fresque.ini*, in the same directory as the executable.

> `-a` or `--autoloader` : Absolute path to your application entry point. Usually, it should be the file that will autoload all your job classes.


##Examples

Let's start a worker with the default settings defined in the config file (fresque.ini)

	fresque start

Let's start another worker, named *activity*, with a pooling frequency of 1 second. Also. I want two workers working on this queue.

	fresque start -q activity -i 1 -n 2

Then, I want another worker, that will work on the queues *default* and *activity* at the same time.

	fresque start -q default,activity
	
Oh wait, I have another resque on another redis server. I want to log its activities in an other log file : remote.log

	fresque start -s 192.168.1.26 -p 6390 -q myspecialqueue -l /path/to/remote.log
	
If you have your config file elsewhere, and your php-resque lib elsewhere also

	fresque start -c /path/to/my-config.ini -b /path/to/my/custom/php-resque

To view stats of your workers (to know how many you have, processed/failed jobs count, uptime)

	fresque stats
	
It should output something like that 

	PHPResque Statistics

	Jobs Stats
	   Processed Jobs : 18617
	   Failed Jobs    : 319
	
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
		 
It's wise to do a `stats` after doing a `start`, to ensure that your workers has been successfully created.  [See Notes]

Remember that you can use the global options (-s, -p etc …) with any command

	fresque stop -c /path/to/my-config.ini -s 192.168.1.26		
Let's enqueue a job to the *activity* queue

	fresque enqueue activity PageVisit 5 /index.php 158745693
	
php-resque will then run this job, by instanciating the class PageVisit, then calling the `perform()` method with the arguments 5, /index.php and 158745693.
In order to instanciate the PageVisit class, php-resque should know where to find it. That should be done with you application autoloader (`--include`)


Oh, and if you want to restart all your workers for whatever reasons

	fresque restart
	
If you're finished, just

	fresque stop
	
It'll spout something like that

	Shutting down Resque Worker complete
	Killing 6 workers ...
	Killing 33197
	Killing 33207
	Killing 33215
	Killing 33232
	Killing 33233
	Killing 33223
	
See notes if it says **There were no active workers to kill …** but you're sure there are some.
	
###Starting all the workers at once
	
We've just created 6 workers, calling `start` 5 times (remember, the second `start` create 2 workers with `-n 2`). But there's a way to start all of them with only one command, useful when you have a lot of workers that you have to start each time.

Just set all your workers settings in the config file in the [Queues] section (walkthrough available in the config file), then start all of them with

	fresque load
	

##Notes

###Always confirm your `start` command with `stats`

Starting a worker with fresque just send a start command to php-resque, which will be running in another process. Thus, any error occuring with php-resque will not be escalated to fresque.

This happens when :

* The user (`--user`) does not exists
* The user doesn't have sufficient permissions
* There's something wrong (fatal error) in your application autoloader

These errors occured within php-resque in another process and can't be detected with fresque, which will still returned a *success* command. A way to confirm that your workers were successfully created is to call `stats`, which will display a list of workers.

You can also read your logs, they give you more details as to where the script hanged.

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

	fresque test -s 195.168.1.26 -p 6890
	
This will test your config file, but with the specified redis hostname and port.

You can also test an other config file

	fresque test -c /my/other/config/file.ini

A test result will looks like

	Testing configuration
	Redis configuration .....OK
	Redis server ............OK
	Log File ................OK
	PHPResque library .......OK
	Application autoloader ..OK
	
	Your settings seems ok
	
##Know problems

###`stop` command doesn't stop workers

This happens when you try to stop your workers, but it says **There were no active workers to kill …**. And a look with `stats` will tell you that there are. This occurs when the `--user` doesn't have sufficient permissions to kill the workers processes.

###Consult your logs

Logs tell you all you need to know about the issue of a job, and the current status of your php-resque workers. It tells you when a job is enqueued, when a job is about to being performed, and its issue (success/fail). It also display all php related errors that may occurs.  
Check them frequently, as fresque doesn't capture those errors.


##Background

Fresque is a derivated works from my other plugin, [cake-resque](https://github.com/kamisama/Cake-Resque), a command line tool to manage php-resque, but inside cakephp console.  
Very convenient, but limited to only cakephp framework. I wanted to release a tool that can work in any php environment. Fresque is more powerfull, since no more binded to a framework.


##Credits

* [PHP-Resque](https://github.com/chrisboulton/php-resque) is written by Chris Boulton 
* Based on [Resque](https://github.com/defunkt/resque) by defunkt
* Fresque by Wan Chen (kamisama)