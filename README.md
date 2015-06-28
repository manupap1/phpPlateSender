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
- `--with-imagedir` - Path to directory where phpPlateSender will look for images of detected plates. If not set, the default value is `/var/lib/openalpr/plateimages`.
- `--disable-sendmail` - Do not use sendmail binary to send emails. If set, configure will not look for sendmail binary. This option is usefull if you plan to the use direct SMTP access method to send emails.

Example of configuration for installation in `/usr/share/phpplatesender` with local versions of phpmailer and pheanstalk (all other options leaved to default value):
```bash
./configure --prefix=/usr
make
```
Installation of phpPlateSender files (root privileges required):
```bash
make install
```
phpPlateSender is now installed on your system, however further configuration steps are required before starting it (please see [configuration section](https://github.com/manupap1/phpPlateSender#configuration).

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
Don't forget to configure openalpr-daemmon and restart it (the configuration file is `/etc/openalpr/alprd.conf`). The following options must be changed or verified:
- `country` set according your location,
- `stream` set to the url of the MJPEG stream on your camera,
- `store_plates` set to 1.

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
phpPlateSender is now installed on your system, however further configuration steps are required before starting it (please see [configuration section](https://github.com/manupap1/phpPlateSender#configuration).
When the configuration is finished, phpPlateSender must be restarted.

Restart command for SysVinit based distributions:
```bash
sudo service phpplatesender force-reload
```
Restart command for Systemd based distributions:
```bash
sudo systemctl force-reload phpplatesender
```

#### Other distributions

Not yet supported.
Your contribution is welcomed!

### Configuration

The configuration file of phpPlateSender is `/etc/phpplatesender/config.php`.

Most of the default values can be kept to start a new instance of phpPlateSender but email notification requires to configure at least the following options:
- `$enable_notifications` must be switched to `true`,
- `$recipient_email` must be set to a valid email address.

Optionally, you can configure the options relatives to SMTP in order to send email throught a SMTP server instead of the default sendmail method:
- `$use_sendmail` switched to false,
- `$smtp_hosts` set to the address of the SMTP server,
- `$smtp_port` set to the port of the SMTP server (required option if the port of the SMTP server is not standard),
- `$smtp_auth`, `$smtp_username`, `$smtp_password`, `$smtp_sec_proto` set if required by the SMTP server

For the meaning of other options, please read the comments in the configuration file.

After each modification of options in the configuration file, phpPlateSender must be restarted for the changes to take effect.

#### Tips about email sending

If emails are not received or are classified among the spam they are several possible reasons (which can be cumulative):
- The configuration of your sendmail system or your local SMTP server is broken (please read the documentation of used application for instructions about how to test email sending).
- You are using a local SMTP server which is not considered legitimate by the mailing system of the recipient. In this case you have two options:
 - Configure a directive on your local SMTP server to send emails to a legitimate relay host. Many ISPs provide an address to a SMTP server which can be used for this purpose (if you are using postfix, please follow the documentation for instructions about how to implement the `relayhost` directive).
 - Configure phpplatesender to send emails directly to a legitimate SMTP server. Please see [configuration section](https://github.com/manupap1/phpPlateSender#configuration) for instructions about how to configure a SMTP server in phpPlateSender.
- The email address of the sender is considered suspicious by the mailing system of the recipient. This address does not have to be a valid address associated to a mailbox, however the domain name may be verified somewhere in the transmission chain. To check this possibility, you can try to set a valid address. Please see [configuration section](https://github.com/manupap1/phpPlateSender#configuration) for instructions about how to change the `$sender_email` option.
- The email address of the recipient does not exists (but I think you have checked it first!).

## Using

To be completed.
