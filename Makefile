.PHONY: clean update-libs test

PHPUNIT_PARAMS = --include-path src/backend/lib --verbose -d display_errors=On -d error_reporting=E_ALL
HTTP_PORT_USED = `lsof -P -i -n | grep "(LISTEN)" | grep -i 8080`
HTTP_SERVER_PID = `wget -q -O - http://localhost:8080/getpid.php`

clean-settings:
	if [ -f dist/settings.php ]; then rm dist/settings.php; fi

clean-config:
	if [ -d dist/data/ ]; then rm -fR dist/data/; fi

clean: clean-settings clean-config
	if [ -d temp/ ]; then rm -fR temp/; fi
	if [ -d dist/ ]; then rm -fR dist/*; fi
	if [ -d src/ui/ext/ ]; then rm -fR src/ui/ext/; fi
	mkdir -p src/ui/ext/
	if [ -d src/backend/ext/ ]; then rm -fR src/backend/ext/; fi
	mkdir -p src/backend/ext/

src/backend/ext/Parsedown.php:
	if [ ! -d src/backend/ext/ ]; then mkdir -p src/backend/ext/; fi
	wget -O src/backend/ext/Parsedown.php https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php

src/backend/ext/: src/backend/ext/Parsedown.php

src/ui/ext/jquery.min.js:
	if [ ! -d src/ui/ext/ ]; then mkdir -p src/ui/ext/; fi
	wget -O src/ui/ext/jquery.min.js https://code.jquery.com/jquery-3.4.1.min.js

src/ui/ext/bootstrap-colorpicker.min.js src/ui/ext/bootstrap-colorpicker.min.css:
	if [ -d temp/ ]; then rm -fR temp/; fi
	mkdir temp/
	wget -O temp/bootstrap-colorpicker.zip https://github.com/itsjavi/bootstrap-colorpicker/releases/download/3.1.2/bootstrap-colorpicker-v3.1.2-dist.zip
	cd temp; unzip bootstrap-colorpicker.zip
	cp temp/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js src/ui/ext/
	cp temp/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css src/ui/ext/
	rm -fR temp/

src/ui/ext/icons/:
	if [ ! -d src/ui/ext/ ]; then mkdir -p src/ui/ext/; fi
	if [ -d temp/ ]; then rm -fR temp/; fi
	mkdir temp/
	wget -O temp/bootstrap-icons.zip https://github.com/twbs/icons/archive/v1.0.0-alpha2.zip
	cd temp; unzip bootstrap-icons.zip
	mv temp/icons-1.0.0-alpha2/icons/ src/ui/ext/
	rm -fR temp/

src/ui/ext/: src/ui/ext/jquery.min.js src/ui/ext/bootstrap-colorpicker.min.js src/ui/ext/bootstrap-colorpicker.min.css src/ui/ext/icons/

lint:
	php -l src/backend/lib/AdminAPI.php
	php -l src/backend/lib/AdminAuth.php
	php -l src/backend/lib/FileStorage.php
	php -l src/backend/lib/global_consts.php
	php -l src/backend/lib/global_functions.php
	php -l src/backend/lib/PageContent.php
	php -l src/backend/lib/PageStorage.php
	php -l src/backend/lib/Settings.php
	php -l src/backend/lib/ShowAdminUI.php
	php -l src/backend/lib/ShowPage.php
	php -l src/backend/index.php

test-unit:
	php -l test/backend/unit/global_functions_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/unit/global_functions_test.php
	php -l test/backend/unit/PageContent_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/unit/PageContent_test.php
	php -l test/backend/unit/AdminAuth_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/unit/AdminAuth_test.php
	php -l test/backend/unit/AdminAPI_test.php
	cd dist; TEST_MY_URL=http://localhost:8080/ phpunit $(PHPUNIT_PARAMS) ../test/backend/unit/AdminAPI_test.php
	php -l test/backend/unit/Settings_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/unit/Settings_test.php

test-integration: build
	php -l test/backend/int/createfiles_test.php
	php -l test/backend/int/file_list_test.php
	php -l test/backend/int/file_upload_test.php
	php -l test/backend/int/getpage_test.php
	php -l test/backend/int/login_test.php
	php -l test/backend/int/setpage_test.php

	cp test/backend/int/env/getpid.php dist/

	# Make sure the port 8080 is free
	if [ "$(HTTP_PORT_USED)" != "" ]; then echo "HTTP port used before starting tests (consider killing the following process):"; echo $(HTTP_PORT_USED); exit 1; fi
	php -S localhost:8080 -t dist/ &
	sleep 3

	# Make sure we have something in port 8080
	if [ "$(HTTP_PORT_USED)" = "" ]; then echo "PHP server did not start"; exit 1; fi

	# Run the tests
	phpunit $(PHPUNIT_PARAMS) test/backend/int/createfiles_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/int/file_list_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/int/file_upload_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/int/getpage_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/int/login_test.php
	phpunit $(PHPUNIT_PARAMS) test/backend/int/setpage_test.php

	# Cleanup
	kill -TERM $(HTTP_SERVER_PID)
	rm dist/getpid.php

settings:
	if [ ! -f dist/settings.php ]; then cp src/backend/settings.php dist/; fi
	php -l dist/settings.php

build: src/backend/ext/ src/ui/ext/ lint clean-config test-unit settings
	if [ -f dist/getpid.php ]; then rm dist/getpid.php; fi
	perl include.pl root.php >dist/index.php
	php -l dist/index.php

serve:
	php -S 0.0.0.0:8080 -t dist/

update-docs:
	mkdir temp
	cd temp; wget --mirror --convert-links http://localhost:8080/
	rm docs/index.html
	rm -fR docs/data/
	mv temp/localhost:8080/* docs/
	sed -i -- 's/http:\/\/localhost:8080\//http:\/\/dummylander.net\//g' docs/index.html
	rm -fR temp/
	cp dist/data/content.json docs/data/
