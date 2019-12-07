.PHONY: update-libs

PHPUNIT_PARAMS = --include-path src/backend/lib --verbose -d display_errors=On -d error_reporting=E_ALL

update-libs:
	wget -O src/backend/ext/Parsedown.php https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php
	mkdir -p src/ui/ext/
	wget -O src/ui/ext/jquery.min.js https://code.jquery.com/jquery-3.4.1.min.js
	mkdir temp
	wget -O temp/bootstrap-colorpicker.zip https://github.com/itsjavi/bootstrap-colorpicker/releases/download/3.1.2/bootstrap-colorpicker-v3.1.2-dist.zip
	cd temp; unzip bootstrap-colorpicker.zip
	cp temp/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js src/ui/ext/
	cp temp/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css src/ui/ext/
	rm -fR temp/

lint:
	php -l src/backend/lib/AdminAPI.php
	php -l src/backend/lib/PageContent.php
	php -l src/backend/lib/PageStorage.php
	php -l src/backend/lib/ShowAdminUI.php
	php -l src/backend/lib/ShowPage.php
	php -l src/backend/index.php

test:
	phpunit $(PHPUNIT_PARAMS) src/backend/test/global_functions_test.php
	phpunit $(PHPUNIT_PARAMS) src/backend/test/PageContent_test.php

build: lint test
	if [ ! -d dist/data/ ]; then mkdir -p dist/data/; fi
	perl include.pl root.php >dist/index.php
	php -l dist/index.php
	cp -r src/data-sample/* dist/data/

serve:
	php -S 0.0.0.0:8080 -t dist/
