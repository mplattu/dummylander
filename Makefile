.PHONY: update-libs

update-libs:
	wget -O src/backend/ext/Parsedown.php https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php

build: src/
	if [ ! -d dist/data/ ]; then mkdir -p dist/data/; fi
	cat src/backend/index.php >dist/index.php
	cat src/backend/ext/Parsedown.php >>dist/index.php
	echo "?>" >>dist/index.php
	cat src/backend/lib/*.php >>dist/index.php
	cp -r src/data-sample/* dist/data/

serve:
	php -S 0.0.0.0:8080 -t dist/
