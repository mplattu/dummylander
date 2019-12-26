.PHONY: update-libs

PHPUNIT_PARAMS = --include-path src/backend/lib --verbose -d display_errors=On -d error_reporting=E_ALL

update-libs:
	mkdir -p src/backend/ext/
	wget -O src/backend/ext/Parsedown.php https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php

	mkdir -p src/ui/ext/
	wget -O src/ui/ext/jquery.min.js https://code.jquery.com/jquery-3.4.1.min.js

	mkdir temp
	wget -O temp/bootstrap-colorpicker.zip https://github.com/itsjavi/bootstrap-colorpicker/releases/download/3.1.2/bootstrap-colorpicker-v3.1.2-dist.zip
	cd temp; unzip bootstrap-colorpicker.zip
	cp temp/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js src/ui/ext/
	cp temp/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css src/ui/ext/
	rm -fR temp/

	mkdir temp
	wget -O temp/bootstrap-icons.zip https://github.com/twbs/icons/archive/v1.0.0-alpha2.zip
	cd temp; unzip bootstrap-icons.zip
	mv temp/icons-1.0.0-alpha2/icons/ src/ui/ext/
	rm -fR temp/

lint:
	php -l src/backend/lib/AdminAPI.php
	php -l src/backend/lib/PageContent.php
	php -l src/backend/lib/PageStorage.php
	php -l src/backend/lib/ShowAdminUI.php
	php -l src/backend/lib/ShowPage.php
	php -l src/backend/index.php

test:
	php -l src/backend/test/global_functions_test.php
	phpunit $(PHPUNIT_PARAMS) src/backend/test/global_functions_test.php
	php -l src/backend/test/PageContent_test.php
	phpunit $(PHPUNIT_PARAMS) src/backend/test/PageContent_test.php
	php -l src/backend/test/AdminAuth_test.php
	phpunit $(PHPUNIT_PARAMS) src/backend/test/AdminAuth_test.php
	php -l src/backend/test/AdminAPI_test.php
	cd dist; TEST_MY_URL=http://localhost:8080/ phpunit $(PHPUNIT_PARAMS) ../src/backend/test/AdminAPI_test.php

settings:
	if [ ! -f dist/settings.php ]; then cp src/backend/settings.php dist/; fi
	php -l dist/settings.php

build: lint test settings
	if [ ! -d dist/data/ ]; then mkdir -p dist/data/; fi
	perl include.pl root.php >dist/index.php
	php -l dist/index.php
	cp -r src/data-sample/* dist/data/

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
