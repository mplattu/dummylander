# Dummylander

Superdupersimple landing page administration tool.

## HOWTO

 1. Copy `dist/*` to your PHP-enabled web server.
 1. Set `$ADMIN_PASSWORD` in `index.php`. The default is "secret".
 1. Log in to admin UI: `http(s):yourdomain.com/?admin`
 1. Enter the password and profit!

## This works

 * The most essential page properties (title, description, keywords, custom CSS) can be edited.
 * The most essential page part properties (MD-formatted text, images, fonts, font colors) can be edited.

## This does not work yet

 * You cannot add, remove or move page parts. For this you have to manually edit `data/content.json`.
 * You cannot upload or remove files.
 * There must be a tons of page or part attributes missing.

## License and Acknowledgement

 * License: MIT, see `LICENSE`
 * Please note that the `src/data-sample/DuckDuckGo-DaxSolo.svg` is &copy; DuckDuckGo and not covered by Dummylander license.
