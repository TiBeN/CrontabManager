# CrontabManager

A PHP library for managing GNU/Linux cron jobs.

It enable you to : 

- Deal with your cron jobs in PHP.
- Create new Cron jobs.
- Update existing cron jobs.
- Manage cron jobs of others users than runtime user using some sudo tricks (see below). 

## Requirments:
- PHP 5.3+
- `crontab` command-line utility (should be already installed in your distribution).
- `sudo`, if you want to manage crontab of another user than runtime user without running into right issues (see below)

## Installation:

The library can be installed using Composer. 
```   
composer require tiben/crontab-manager
```

## Usage:
The library is composed of three classes: 

- `CrontabJob` is an entity class which represent a cron Job.
- `CrontabRepository` is used to persist/retrieve your jobs.
- `CrontabAdapter` abstract raw read/write against the crontab.  

### Instanciate the repository:
In order to work, the CrontabRepository need an instance of CrontabAdapter which handle raw read/write against the crontab.

```php
$crontabRepository = new $crontabRepository(new CrontabAdapter());
```

### Create new Job and persist it into the crontab:
Suppose you want to create an new job which consist of launching the command "df >> /tmp/df.log" every day at 23:30. You can do it in two ways.

- In Pure oo way :
```php
$crontabJob = new CrontabJob();
$crontabJob->minutes = '30';
$crontabJob->hours = '23';
$crontabJob->dayOfMonth = '*';
$crontabJob->months = '*';
$crontabJob->dayOfWeek = '*';
$crontabJob->taskCommandLine = 'df >> /tmp/df.log';
$crontabJob->comments = 'Logging disk usage'; // Comments are persisted in the crontab
```

- From raw cron syntax string using a factory method :  
```php
$crontabJob = CrontabJob::createFromCrontabLine('30 23 * * * df >> /tmp/df.log');
```

You can now add your new cronjob in the crontab repository and persist all changes to the crontab.
```php
$crontabRepository->addJob($crontabJob);
$crontabRepository->persist();
```

### Find a specific cron job from the crontab repository and update it:
Suppose we want to modify the hour of an already existing cronjob. Finding existings jobs is made using some regular expressions. Search in made against the entire crontab line. 
```php
$results = $crontabRepository->findJobByRegex('/Logging\ disk\ usage/');
$crontabJob = $results[0];
$crontabJob->hours = '21';
$crontabRepository->persist();
```

### Remove a cron job from the crontab:
You can removing a job like this :
```php
$results = $crontabRepository->findJobByRegex('/Logging\ disk\ usage/');
$crontabJob = $results[0];
$crontabRepository->removeJob($crontabJob);
$crontabRepository->persist();
```
Note: Since cron jobs are internally matched by reference, they must be previously obtained from the repository or previously added.

### Work with the crontab of another user than runtime user:
This feature allow you to manage the crontab of another user than the user who launched the runtime. This can be useful when the runtime user is `www-data` but the owner of the crontab you want to edit is your own linux user account. 

To do this, simply pass the username of the crontab owner as parameter of the CrontabAdapter constructor. Suppose you are `www-data` and you want to edit the crontab of user `bobby`:
```php
$crontabAdapter = new CrontabAdapter('bobby');
$crontabRepository = new CrontabRepository();
```

Using this way you will propably run into user rights issue. 
This can be resolved by editing your sudoers file using 'visudo'.     
If you want to allow user `www-data` to edit the crontab of user `bobby`, add this line:
```
www-data        ALL=(bobby) NOPASSWD: /usr/bin/crontab
```
which tell sudo to not ask for password when call `crontab` of user `bobby` 

Now, you can access to the crontab of user `bobby` like this :
```php
$crontabAdapter = new CrontabAdapter('bobby', true);
$crontabRepository = new CrontabRepository();
```
Note the second parameter `true` of the CrontabAdapter constructor call. This boolean tell the CrontabAdapter to use 'sudo' internally to read/write the crontab.   



