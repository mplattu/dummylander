.PHONY: update-libs

update-libs:
	wget -O src/backend/ext/Parsedown.php https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php
	wget -O src/ui/ext/jquery.min.js https://code.jquery.com/jquery-3.4.1.min.js

lint:
	php -l src/backend/lib/AdminAPI.php
	php -l src/backend/lib/PageContent.php
	php -l src/backend/lib/PageStorage.php
	php -l src/backend/lib/ShowAdminUI.php
	php -l src/backend/lib/ShowPage.php
	php -l src/backend/index.php

build: lint
	if [ ! -d dist/data/ ]; then mkdir -p dist/data/; fi
	perl include.pl root.php >dist/index.php
	php -l dist/index.php
	cp -r src/data-sample/* dist/data/

serve:
	php -S 0.0.0.0:8080 -t dist/
