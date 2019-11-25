.PHONY: update-libs

update-libs:
	wget -O src/backend/ext/Parsedown.php https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php
	wget -O src/backend/html/ext/jquery.min.js https://code.jquery.com/jquery-3.4.1.min.js

lint:
	php -l src/backend/lib/AdminAPI.php
	php -l src/backend/lib/PageContent.php
	php -l src/backend/lib/PageStorage.php
	php -l src/backend/lib/ShowAdminUI.php
	php -l src/backend/lib/ShowPage.php
	php -l src/backend/index.php

build: lint
	if [ ! -d dist/data/ ]; then mkdir -p dist/data/; fi
	cat src/backend/index.php >dist/index.php
	cat src/backend/ext/Parsedown.php >>dist/index.php
	echo "?>" >>dist/index.php
	cat src/backend/lib/AdminAPI.php >>dist/index.php
	cat src/backend/lib/PageContent.php >>dist/index.php
	cat src/backend/lib/PageStorage.php >>dist/index.php
	perl include.pl src/backend/lib/ShowAdminUI.php >>dist/index.php
	cat src/backend/lib/ShowPage.php >>dist/index.php
	cp -r src/data-sample/* dist/data/
	php -l dist/index.php

serve:
	php -S 0.0.0.0:8080 -t dist/
