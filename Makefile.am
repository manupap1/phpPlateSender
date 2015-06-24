AUTOMAKE_OPTIONS = foreign

webuser = @WEB_USER@
webgroup = @WEB_GROUP@
webdir = @datarootdir@/phpplatesender
configdir = @CONFIG_DIR@

dist_web_DATA = \
	daemon.php \
	default.php \
	logger.php \
	sender.php \
	utils.php \
	worker.php \
	composer.json

SUBDIRS = \
	lang

config_DATA = \
	config.php

EXTRA_DIST = \
	config.php.in \
	default.php.in

# Yes, you are correct. This is a HACK!

composer.phar:
	( $(CURL_BINARY_PATH) -sS https://getcomposer.org/installer | $(PHPCLI_BINARY_PATH) )

vendor/phpmailer/phpmailer/class.phpmailer.php: composer.phar
	( $(PHPCLI_BINARY_PATH) composer.phar require phpmailer/phpmailer:~5.2 )
	( rm -f vendor/phpmailer/phpmailer/.gitignore vendor/phpmailer/phpmailer/language/phpmailer.lang-am.php )

vendor/pda/pheanstalk/pheanstalk_init.php: composer.phar
	( $(PHPCLI_BINARY_PATH) composer.phar require pda/pheanstalk:~2.1 )
	( rm -f vendor/pda/pheanstalk/.gitignore vendor/pda/pheanstalk/.gitattributes vendor/pda/pheanstalk/scripts/build_phar.php )

all-local: $(PHEANSTALK_FILE) $(PHPMAILER_FILE)

clean-local:
	( if test -e vendor/phpmailer/phpmailer; then $(PHPCLI_BINARY_PATH) composer.phar remove phpmailer/phpmailer; fi )
	( if test -e vendor/pda/pheanstalk; then $(PHPCLI_BINARY_PATH) composer.phar remove pda/pheanstalk; fi )
	( rm -rf config.php default.php composer.lock composer.phar vendor )

install-data-hook:
	( cd $(DESTDIR)$(configdir); chown $(webuser):$(webgroup) $(config_DATA); chmod 600 $(config_DATA); ln -sf $(configdir)/$(config_DATA) $(DESTDIR)$(pkgdatadir)/$(config_DATA) )
	( if ! test -e $(DESTDIR)$(RUN_DIR); then mkdir -p $(DESTDIR)$(RUN_DIR); fi; if test "$(DESTDIR)$(RUN_DIR)" != "/var/run"; then chown $(webuser):$(webgroup) $(DESTDIR)$(RUN_DIR); chmod u+w $(DESTDIR)$(RUN_DIR); fi )
	( if ! test -e $(DESTDIR)$(LOG_DIR); then mkdir -p $(DESTDIR)$(LOG_DIR); fi; if test "$(DESTDIR)$(LOG_DIR)" != "/var/log"; then chown $(webuser):$(webgroup) $(DESTDIR)$(LOG_DIR); chmod u+w $(DESTDIR)$(LOG_DIR); fi )
	( if test "x$(PHEANSTALK_FILE)" != "x"; then if ! test -e $(DESTDIR)$(pkgdatadir)/lib; then mkdir -p $(DESTDIR)$(pkgdatadir)/lib; fi; cp -R vendor/pda/pheanstalk $(DESTDIR)$(pkgdatadir)/lib; fi )
	( if test "x$(PHPMAILER_FILE)" != "x"; then if ! test -e $(DESTDIR)$(pkgdatadir)/lib; then mkdir -p $(DESTDIR)$(pkgdatadir)/lib; fi; cp -R vendor/phpmailer/phpmailer $(DESTDIR)$(pkgdatadir)/lib; fi )

uninstall-hook:
	@-( if test "$(DESTDIR)$(pkgdatadir)" != "/usr/share"; then rm -rf $(DESTDIR)$(pkgdatadir); fi )
	@-( if test "$(DESTDIR)$(RUN_DIR)" != "/var/run"; then rm -rf $(DESTDIR)$(RUN_DIR); fi )
