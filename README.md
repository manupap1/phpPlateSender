# phpPlateSender
## Overview
phpPlateSender is a free, open source set of scripts to use with openalpr-daemon (alprd). It sends notifications when plate numbers are detected. phpPlateSender runs in the background and wait for new entries in the 'alprd' beanstalkd queue.
## Requirements
phpPlateSender requires:
- A running instance of openalpr-daemon (and beanstalkd)
- A running instance of memcached server
- PHP client binary (usually /usr/bin/php)
- CURL binary (usually /usr/bin/curl)
- sendmail binary (usually /usr/sbin/sendmail) or access to a SMTP server (sendmail is the default method to send emails)
## Installation
### Get the files and enter phpPlateSender directory
```bash
git clone https://github.com/manupap1/phpPlateSender.git
cd phpPlateSender
```
### Install phpPlateSender with standard installation method
This is not the preferred method.
You are encouraged to use the package installation method if your distribution is supported.
```bash
./bootstrap.sh
./configure --help
```
This will display the available options to customize installation, main usefull options are:
- `--prefix` - Prefix of directory where to install phpPlateSender. If not set, the default value is `/usr/local` (phpPlateSender will be installed under `/usr/local/share/phpplatesender`)
- `--with-phpmailerdir` - Path to existing phpmailer library. If set, phpPlateSender will load this library at runtime. If not set, a local version will be installed (internet access required)
- `--with-pheanstalkdir` - Path to existing pheanstalk library. If set, phpPlateSender will load this library at runtime. If not set, a local version will be installed (internet access required)
- `--with-rundir` - Path to directory where phpPlateSender will write the process file. If not set, the default value is `/var/run/phpplatesender`
- `--with-logdir` - Path to directory where phpPlateSender will write the log files. If not set, the default value is `/var/log/phpplatesender`
- `--with-confdir` - Path to directory where phpPlateSender will load the configuration file. If not set, the default value is `/etc/phpplatesender`
- `--with-imagedir` - Path to directory where phpPlateSender will look for images of detected plates. If not set, the default value is `/var/lib/openalpr/plateimages`
- `--with-webuser` - User name for script execution. This must be the name of a valid user on your system. If not set, the default value is `apache`
- `--with-webgroup` - Group name for script execution. This must be the name of a valid group on your system. If not set, the default value is `apache`
Please set these options as requested by your environment.
```bash
./configure --prefix=/usr
make
```
This minimal command will configure phpPlateSender for installation in `/usr/share/phpplatesender` with local versions of phpmailer and pheanstalk. All other options are leaved to default value.
Please set the options as requested by your environment.

And with root privileges:
```bash
make install
```
This will finally install phpPlateSender on your system.

Further configuration steps are required before starting phplateSender.
The configuration file with default `--with-confdir` option is `/etc/phpplatesender/config.php`.
Most of default values can be kept to default but email notification requires to configure at least the following options:
- `$enable_notifications` must be switched to `true`
- `$recipient_email` must be set to a valid email address

Optionally, you can configure the options relatives to SMTP to send email throught a SMTP server instead of the default sendmail method.
It is strongly recommended to use SMTP if the recipients have public email addresses.

In order to manually start phpPlateSender the effective shell user must be the username given for `--with-webuser`.
You can also use the `sudo` command if your distribution supports it (please read the sudo man page for information about how to use this command).

phpPlateSender can be started with:
```bash
/usr/bin/php /usr/share/phpplatesender/worker.php
```
If you want to run phpPlateSender on the background, you can use the following command:
```bash
/usr/bin/php /usr/share/phpplatesender/daemon.php start
```
If you want that phpPlatePSender automatically starts on the background after reboots, take a look at the misc folder, there is some helper files for SysVinit and Systemd init systems (please read the corresponding man pages for information about how to implement a new entry for phpPlateSender).
### Install phpPlateSender with package installation method
#### Debian / Ubuntu distribution
Prerequisite: The package openalpr-daemon must be installed. Please read openalpr documentation on https://github.com/openalpr/openalpr.
Construction of phpPlateSender package:
```bash
cp -R distros/debian ./
dpkg-buildpackage -rfakeroot -us -uc
```
Installation of phpPlateSender package:
```bash
cd ..
sudo apt-get install memcached php5-cli curl
sudo dpkg -i phpplatesender_*_all.deb
```
#### Other distribution

Not yet supported.
Your contribution is welcomed!

## Using

To be completed.
