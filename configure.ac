AC_PREREQ([2.60])
AC_INIT([phpplatesender],[1.0.0],,)
AC_CONFIG_AUX_DIR([.])
AM_INIT_AUTOMAKE([foreign])
#AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.

AC_PROG_INSTALL

AC_PATH_PROG([HOSTNAME_BINARY_PATH],[hostname],[no])
if test "x$HOSTNAME_BINARY_PATH" == "xno"; then
    AC_MSG_ERROR([hostname binary is missing, please install it])
else
    FQDN=$($HOSTNAME_BINARY_PATH --fqdn)
    if test $? != 0; then
        AC_MSG_WARN([Failed to retrieve the fully qualified name of the machine.
                    Using mydomain.com as fallback method.])
        FQDN="mydomain.com"
    fi
    AC_SUBST(FQDN)
fi

AC_PATH_PROG([CURL_BINARY_PATH],[curl],[no])
if test "x$CURL_BINARY_PATH" == "xno"; then
    AC_MSG_ERROR([curl binary is missing, please install it])
fi
AC_SUBST(CURL_BINARY_PATH)

AC_PATH_PROG([PHPCLI_BINARY_PATH],[php],[no])
if test "x$HAVE_PHPCLI_BINARY" == "xno"; then
    AC_MSG_ERROR([php client binary is missing, please install it])
fi
AC_SUBST(PHPCLI_BINARY_PATH)

if test "x$($PHPCLI_BINARY_PATH -m | grep memcached)" == "x"; then
    AC_MSG_ERROR([memcached extension is missing on php, please install it])
fi

AC_CHECK_PROG([ALPRD],[alprd],[yes],[no])
if test "x$ALPRD" == "xno"; then
    AC_MSG_ERROR([openalpr-daemon (alprd) is missing on php, please install it])
fi

# Options

PHPMAILER_EXT_DIR=
PHPMAILER_FILE=
AC_ARG_WITH([phpmailerdir],
    [  --with-phpmailerdir=<path>    Prefix of directory for the phpmailer
                                     library],
    [PHPMAILER_EXT_DIR=$with_phpmailerdir],
    AC_MSG_WARN([You can call configure with the --with-phpmailerdir option.
    This tells configure to not install phpmailer but to use the given directory
    at runtime to load this library.
    e.g. --with-phpmailerdir=/usr/share/php/libphp-phpmailer])
)
if test "x$PHPMAILER_EXT_DIR" = "x"; then
    PHPMAILER_FILE=vendor/phpmailer/phpmailer/class.phpmailer.php
fi
AC_SUBST(PHPMAILER_EXT_DIR)
AC_SUBST(PHPMAILER_FILE)

PHEANSTALK_EXT_DIR=
PHEANSTALK_FILE=
AC_ARG_WITH([pheanstalkdir],
    [  --with-pheanstalkdir=<path>   Prefix of directory for the pheanstalk
                                     library],
    [PHEANSTALK_EXT_DIR=$with_pheanstalkdir],
    AC_MSG_WARN([You can call configure with the --with-pheanstalkdir option.
    This tells configure to not install pheanstalk but use the given directory
    at runtime to load this library.
    e.g. --with-pheanstalkdir=/usr/share/php/php-pheanstalk])
)
if test "x$PHEANSTALK_EXT_DIR" = "x"; then
    PHEANSTALK_FILE=vendor/pda/pheanstalk/pheanstalk_init.php
fi
AC_SUBST(PHEANSTALK_EXT_DIR)
AC_SUBST(PHEANSTALK_FILE)

RUN_DIR=/var/run/phpplatesender
AC_ARG_WITH([rundir],
    [  --with-rundir=<path>          Prefix of directory for the process file,
                                     default /var/run/phpplatesender],
    [RUN_DIR=$with_rundir],
    AC_MSG_WARN([You can call configure with the --with-rundir option.
    This tells phpplatesender where to write the process file.
    e.g. --with-rundir=/var/run/phpplatesender])
)
AC_SUBST(RUN_DIR)

LOG_DIR=/var/log/phpplatesender
AC_ARG_WITH([logdir],
    [  --with-logdir=<path>          Prefix of directory for the log file,
                                     default /var/log/phpplatesender],
    [LOG_DIR=$with_logdir],
    AC_MSG_WARN([You can call configure with the --with-logdir option.
    This tells phpplatesender where to write the log file.
    e.g. --with-logdir=/var/log/phpplatesender])
)
AC_SUBST(LOG_DIR)

CONFIG_DIR=/etc/phpplatesender
AC_ARG_WITH([confdir],
    [  --with-confdir=<path>         Prefix of directory for the config file,
                                     default /etc/phpplatesender],
    [CONFIG_DIR=$with_confdir],
    AC_MSG_WARN([You can call configure with the --with-confdir option.
    This tells phpplatesender where to look for the configuation file.
    e.g. --with-confdir=/etc/phpplatesender])
)
AC_SUBST(CONFIG_DIR)

ALPRD_CONFIG_DIR=/etc/openalpr
AC_ARG_WITH([alprd-confdir],
    [  --with-alprd-confdir=<path>   Prefix of directory for the config file of
                                     alprd, default /etc/openalpr],
    [ALPRD_CONFIG_DIR=$with_alprd_confdir],
    AC_MSG_WARN([You can call configure with the --with-alprd-confdir option.
    This tells phpplatesender where to look for the configuration file of alprd.
    e.g. --with-alprd-confdir=/etc/openalpr])
)
ALPRD_CONFIG_FILE=$(ls $ALPRD_CONFIG_DIR/alprd.conf 2> /dev/null)
if test $? != 0; then
    AC_MSG_ERROR([alprd configuration file not found in directory])
fi
AC_SUBST(ALPRD_CONFIG_FILE)

RUN_USER=apache
AC_ARG_WITH([runuser],
    [  --with-runuser=<user>         Name of user that executes php scripts,
                                     default apache],
    [RUN_USER=$with_runuser],
    AC_MSG_WARN([You can call configure with the --with-runuser option.
    This tells configure the user name to set for php script execution.
    Default value is 'apache'.
    e.g. --with-runuser=apache])
)
ID_RUN_USER=$(id -u $RUN_USER > /dev/null 2>&1)
if test $? != 0; then
    AC_MSG_ERROR([User not found, please adjust option '--with-runuser'
                  or create user])
fi
AC_SUBST(RUN_USER)

RUN_GROUP=apache
AC_ARG_WITH([rungroup],
    [  --with-rungroup=<group>       Name of the group to which the user
                                     that executes php scripts belongs,
                                     default apache],
    [RUN_GROUP=$with_rungroup],
    AC_MSG_WARN([You can call configure with the --with-rungroup option.
    This tells configure the group name to set for script execution.
    Default value is 'apache'.
    e.g. --with-rungroup=apache])
)
ID_RUN_GROUP=$(id -u $RUN_GROUP > /dev/null 2>&1)
if test $? != 0; then
    AC_MSG_ERROR([Group not found, please adjust option '--with-rungroup'
                  or create group])
fi
AC_SUBST(RUN_GROUP)

USE_SENDMAIL=yes
AC_ARG_ENABLE([sendmail],
    [  --enable-sendmail=<yes|no>    Enable or disable the use of sendmail to
                                     send notification emails, default enabled],
    [USE_SENDMAIL=$enable_sendmail],
    [AC_MSG_WARN([You can call configure with the --enable-sendmail=<yes|no>
    or --disable-sendmail option.
    This tells configure whether to look for sendmail binary.
    e.g. --enable-sendmail=yes or --disable-sendmail])]
)
if test "x$USE_SENDMAIL" == "xyes"; then
    # Can not use AC_CHECK_PROG because /usr/sbin path may not be available for
    # current user
    SENDMAIL_BINARY_PATH=$(ls /usr/sbin/sendmail > /dev/null 2>&1)
    if test $? != 0; then
        AC_MSG_ERROR([sendmail is missing, please install it])
    fi
fi

DATA_DIR="${prefix}/share/phpplatesender"
AC_SUBST(DATA_DIR)

AC_CONFIG_FILES([Makefile config.php default.php lang/Makefile misc/phpplatesender.init misc/phpplatesender.service misc/phpplatesender.tmpfile])

AC_OUTPUT
