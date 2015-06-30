# phpPlateSender

## Overview

phpPlateSender is a free, open source set of scripts to use with openalpr-daemon (alprd). It sends notifications when plate numbers are detected. phpPlateSender runs in the background and wait for new entries in the 'alprd' beanstalkd queue.

## Requirements

phpPlateSender requires:
- A running instance of openalpr-daemon (and beanstalkd),
- A running instance of memcached server,
- PHP client binary (usually /usr/bin/php),
- CURL binary (usually /usr/bin/curl),
- A working environment to send emails with sendmail binary (usually /usr/sbin/sendmail) or direct access to a SMTP server (sendmail is the default method to send emails).

## Installation

### Get the files and enter phpPlateSender directory

```bash
git clone https://github.com/manupap1/phpPlateSender.git
cd phpPlateSender
```

### Install phpPlateSender with standard installation method

***This is not the preferred method.
You are encouraged to use the package installation method if your distribution is supported.***

Generate files required for installation:
```bash
./bootstrap.sh
```
Display the list of configuration options:
```bash
./configure --help
```
Please set these options as requested by your environment. Main usefull options are:
- `--prefix` - Prefix of directory where to install phpPlateSender. If not set, the default value is `/usr/local` (phpPlateSender will be installed under `/usr/local/share/phpplatesender`).
- `--with-phpmailerdir` - Path to existing phpmailer library. If set, phpPlateSender will load this library at runtime. If not set, a local version will be installed (internet access required).
- `--with-pheanstalkdir` - Path to existing pheanstalk library. If set, phpPlateSender will load this library at runtime. If not set, a local version will be installed (internet access required).
- `--with-rundir` - Path to directory where phpPlateSender will write the process file. If not set, the default value is `/var/run/phpplatesender`.
- `--with-runuser` - User name for script execution. This must be the name of a valid user on your system. If not set, the default value is `apache`.
- `--with-rungroup` - Group name for script execution. This must be the name of a valid group on your system. If not set, the default value is `apache`.
- `--with-logdir` - Path to directory where phpPlateSender will write the log files. If not set, the default value is `/var/log/phpplatesender`.
- `--with-confdir` - Path to directory where phpPlateSender will load the configuration file. If not set, the default value is `/etc/phpplatesender`.
- `--with-alprd-confdir` - Path to directory where phpPlateSender will look for the configuration file of openalpr-daemon (alprd). If not set, the default value is `/etc/openalpr`.

Example of configuration for installation in `/usr/share/phpplatesender` with local versions of phpmailer and pheanstalk (all other options leaved to default value):
```bash
./configure --prefix=/usr
make
```
Installation of phpPlateSender files (root privileges required):
```bash
make install
```
phpPlateSender is now installed on your system, however further configuration steps are required before starting it (please read [configuration section](https://github.com/manupap1/phpPlateSender#basic-configuration)).

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
If you want phpPlateSender to automatically starts on the background after reboots, take a look at the `misc` folder.
There is some helper files for SysVinit and Systemd init systems (please read the corresponding man pages for information about how to implement these files).

### Install phpPlateSender with package installation method

#### Debian / Ubuntu distribution

Prerequisite:

The package openalpr-daemon must be installed. Please read the documentation about how to install the packages on https://github.com/openalpr/openalpr.
Don't forget to configure openalpr-daemon and restart it (the configuration file is `/etc/openalpr/alprd.conf`). The following options must be changed or verified:
- `country` set according your location,
- `stream` set to the url of the MJPEG stream on your camera,
- `store_plates` set to 1 if you want to attach images of detected plates to emails.

The following instructions assume that your current shell directory is phpPlateSender.

Construction of phpPlateSender package:
```bash
cp -R distros/debian ./
sudo apt-get install pkg-php-tools
dpkg-buildpackage -rfakeroot -us -uc
```
Installation of phpPlateSender package:
```bash
cd ..
sudo apt-get install memcached php5-cli curl
sudo dpkg -i phpplatesender_*_all.deb
```
phpPlateSender is now installed on your system, however further configuration steps are required before starting it (please read [configuration section](https://github.com/manupap1/phpPlateSender#basic-configuration)).
When the configuration is finished, phpPlateSender must be restarted.

Restart command for SysVinit based distributions:
```bash
sudo service phpplatesender restart
```
Restart command for Systemd based distributions:
```bash
sudo systemctl restart phpplatesender
```

#### Other distributions

Not yet supported.
Your contribution is welcomed!

## Basic configuration

The configuration file of phpPlateSender is `/etc/phpplatesender/config.php`.

Most of the default values can be kept to start a new instance of phpPlateSender but email notification requires to configure at least the following options:
- `$enable_notifications` must be switched to `true`,
- `$sender_email` must be set to an address with a valid domain name,
- `$recipient_email` must be set to a valid email address.

Optionally, you can configure the options relatives to SMTP in order to send email throught a SMTP server instead of the default sendmail method:
- `$use_sendmail` switched to false,
- `$smtp_hosts` set to the address of the SMTP server,
- `$smtp_port` set to the port of the SMTP server (required option if the port of the SMTP server is not standard),
- `$smtp_auth`, `$smtp_username`, `$smtp_password`, `$smtp_sec_proto` set if required by the SMTP server.

If you choose to keep the default method to send emails, the file `/usr/sbin/sendmail` must be present on your machine. It is usually provided by Mail Transport Agent (MTA) like `postfix`, `sendmail` or `exim`.

For the meaning of other options, please read the comments in the configuration file.

After each modification of options in the configuration file, phpPlateSender must be restarted for the changes to take effect.

## Using

### Tuning

The configuration file of phpPlateSender is `/etc/phpplatesender/config.php`.

Several configuration options are available to adjust detection result:
- `$max_plates` - openalpr-daemon (alprd) returns a list of plates with a confidence level for each image where a plate is detected. This option set the maximum number of plates to notify.
- `$max_det_duration` - When plates are detected in sucessive images, this options set the duration after which the sending of a notification is forced.
- `$no_det_duration` - To avoid sending of a notification for each image with a detected plate, this option set a waiting period. If a new detection occurs within this period, the result is aggregated with previous result and the period is reset. The notification is sent after the period expiration.

After each modification of options in the configuration file, phpPlateSender must be restarted for the changes to take effect.

### Tips about email sending

If emails are not received or are classified among the spam they are several possible reasons (which can be cumulative):
- The configuration of your sendmail system or your local SMTP server is broken (please read the documentation of used application for instructions about how to test email sending).
- You are using a local SMTP server which is not considered legitimate by the mailing system of the recipient. In this case you have two options:
 - Configure a directive on your local SMTP server to redirect emails through a legitimate relay host. Many ISPs provide an address to a SMTP server which can be used for this purpose. If you are using postfix, please read the postfix documentation for instructions about how to implement the `relayhost` directive.
 - Configure phpplatesender to send emails directly to a legitimate SMTP server. Please read [configuration section](https://github.com/manupap1/phpPlateSender#basic-configuration) for instructions about how to configure a SMTP server in phpPlateSender.
- The email address of the sender is considered suspicious. This address does not have to be a valid address associated to a mailbox, however this address may be rejected for different reasons:
 - The SMTP server may verify that the domain name is valid. The default email address is set from the domain name of the machine where phpPlateSender is installed. Please read [configuration section](https://github.com/manupap1/phpPlateSender#basic-configuration) for instructions about how to change the `$sender_email` option.
 - The mailing system of the recipient has blacklisted the email address of the sender or the domaine name of this address. Please read [configuration section](https://github.com/manupap1/phpPlateSender#basic-configuration) for instructions about how to change the `$sender_email` option.
- The email address of the recipient does not exists (but I think you have checked it first!).
