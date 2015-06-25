# phpPlateSender

## Overview

phpPlateSender is a free, open source set of scripts to use with openalpr-daemon (alprd). It sends notifications when plate numbers are detected. phpPlateSender runs in the background and wait for new entries in the 'alprd' beanstalkd queue.

## Requirements

phpPlateSender requires:
- PHP client binary (usually /usr/bin/php)
- CURL binary (usually /usr/bin/curl)
- A running instance of memcached server
- A running instance of openalpr-daemon
- A running instance of beanstalkd
- Optionally, access to a SMTP server if sendmail is not the preferred method to send emails

## Installation

### File downloading
```bash
git clone https://github.com/manupap1/phpPlateSender.git
cd phpPlateSender
```
### Standard Installation Method
```bash
./bootstrap.sh
./configure --help
```
This will display the available options to customize installation, main usefull options are:
- `--prefix`  Prefix of directory where to install phpPlateSender. If not set, the default value is /usr/local (this means phpPlateSender will be installed under /usr/local/share/phpplatesender)
- `--with-phpmailerdir` - Path to existing phpmailer library. If set, phpPlateSender will load this library at runtime. If not set, a local version will be installed
- `--with-pheanstalkdir` - Path to existing pheanstalk library. If set, phpPlateSender will load this library at runtime. If not set, a local version will be installed
- `--with-rundir` - Path to directory where phpPlateSender will write the process file. If not set, the default value is /var/run/phpplatesender
- `--with-logdir` - Path to directory where phpPlateSender will write the log files. If not set, the default value is /var/log/phpplatesender
- `--with-confdir` - Path to directory where phpPlateSender will load the configuration file. If not set, the default value is /etc/phpplatesender
- `--with-imagedir` - Path to directory where phpPlateSender will look for images of detected plates. If not set, the default value is /var/lib/openalpr/plateimages
- `--with-webuser` - User name for script execution. This must be the name of a valid user on your system. If not set, the default value is apache.
- `--with-webgroup` - Group name for script execution. This must be the name of a valid group on your system. If not set, the default value is apache
Please set these options as requested by your environment.
```bash
./configure --prefix=/usr
make
```
This minimal example will configure phpPlateSender for installation in /usr/share/phpplatesender with local versions of phpmailer and pheanstalki. All other options are leaved to default value.
Please set the options as requested by your environment.

And with root privileges:
```bash
make install
```
This will finally install phpPlateSender on your system.

Further configuration steps are required to start phplateSender.
The configuration file is /etc/phpplatesender/config.php (this may vary depending on --with-confdir option).

### Package installation method

#### Debian / Ubuntu Distribution

To be completed

#### Other Distribution

Not yet supported

## Using

### Standard Installation

To be completed

### Package Installation

#### Debian / Ubuntu Distribution

To be completed

#### Other Distribution

Not yet supported

