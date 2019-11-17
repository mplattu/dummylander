# Dummylander

Superdupersimple landing page generation &amp; administration tool. Currently only the rendering part is implemented.

## HOWTO

 1. Copy `dist/*` to your PHP-enabled web server.
 1. See `data/content.json`. The landing page is defined in sections. Each section can have its own background and font settings. The `text` contains [MarkDown](https://www.markdownguide.org/)-formatted text.
 1. That's it!

## Roadmap

The Grand Plan is to add an administration functionality using [Auth0](https://auth0.com/) authentication backend.

## License and Acknowledgement

 * License: MIT, see `LICENSE`
 * Please note that the `src/data-sample/DuckDuckGo-DaxSolo.svg` is &copy; DuckDuckGo and not covered by Dummylander license.
