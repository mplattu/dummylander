# Dummylander

[![Build Status](https://travis-ci.org/mplattu/dummylander.svg?branch=master)](https://travis-ci.org/mplattu/dummylander)

Superdupersimple landing page administration tool.

## HOWTO

 1. Copy `dist/index.php` to your PHP-enabled web server. Dummylander has been tested with PHP versions 7.2, 7.3 and 7.4.
 1. Browse you homepage by opening your URL (e.g. `http(s)://yourdomain.com`) with a browser. On the first
    page load it creates a password for the administration page. Please save this random string as you
    need it to update the page content. If you did not get the password please open an
    [issue](https://github.com/mplattu/dummylander/issues).
 1. Reload the URL (`http(s)://yourdomain.com`). This time you should get a nice default page.
 1. Log in to admin UI: `http(s)://yourdomain.com/?admin`
 1. Enter the password you was given above and profit!

## This works

 * The most essential page properties (title, description, keywords, og:image, custom CSS) can be edited.
 * The most essential page part properties (MD-formatted text, images, fonts, font colors) can be edited.
 * You can add, remove and move page parts.
 * You can see page preview before publishing.
 * You can upload and remove files.

## This does not work yet

 * There must be a tons of page or part attributes missing.
 * Easy way to add images and links to files.
 * Changing administrator password.

## Authentication

Dummylander has very simple, robust and straightforward authentication. Each time the
admin UI makes call to backend it re-sends the cleartext password. Therefore,
it is essential for security reasons to *secure all your connections with SSL*.
In other words do not serve your site at all through `http` but preferably only `https`. Use
the `https` connection at least  whenever editing your site (`https://yourcomain.com/?admin`).

The encrypted admin password is stored in `settings.php`. Although the password is crypted
don't forget to double-check that the URL `http(s)://youdromain.com/settings.php`
returns an empty page.
 * If you get error 404 (Not found) make sure you entered the URL correctly and
   you have retrieved the page (`http(s)://yourdomain.com`) at least once.
 * If you see the file content the PHP settings of the server are not correctly set.

## Building

To build Dummylander you need to have:
 * PHP (7.2 or above, Debian packages `php-cli php-mbstring`)
 * phpunit (6 or above, Debian package `phpunit`)
 * Perl modules JSON::XS (Debian package `libjson-xs-perl`)
 * `make update-libs`
 * `make build` or `make test-integration`

## Installing by Ansible

The Ansible install scripts are provided in `ansible/`. The scripts work for Debian 9
and 10 with `nginx` and `PHP-FPM` installed. The scripts are useful if you'd like i.e. to
serve several Dummylander sites on one host without setting up each host manually.

The following examples expect that the DNS configuration is already carried out. For exampe you have configured `test.dummylander.net` to point to your existing server `test.yourdomain.com`.

The server `test.yourdomain.com` has to be in group `dummylander` in `/etc/ansible/hosts`:

```
[dummylander]
test.yourdomain.com
```

Also, your local commanding machine has to have `php-cli` installed as the password is encrypted locally.

### Install Everything From the Scratch

The script does following things for you:
 * Prepare shared SSL files (session ticket key and Diffie-Hellman parameter file)
 * Get a Let's Encrypt certificate for the site (if parameter `certbot_email` is set)
 * Set a cronjob to renew the certificates
 * Create a nginx configuration for the site
 * Enable the site (make it effective)
 * Install the latest master build of Dummylander
 * Set the site password (if parameter `dummypass` is set)
 * Does not create a sample page as it gets created when page is loaded for the first time

```
ansible-playbook -l test.yourdomain.com -K install.yml \
--extra-vars '{"domains": ["test.dummylander.net"], "certbot_email": "office@sivuduuni.biz", "dummypass": "yournewsecrectpass"}'
```

In case you want to create site with multiple domain names (e.g. `test1.dummylander.net` and `test2.dummylander.net`):

```
ansible-playbook -l test.yourdomain.com -K install.yml \
--extra-vars '{"domains": ["test1.dummylander.net", "test2.dummylander.net"], "certbot_email": "office@sivuduuni.biz", "dummypass": "yournewsecrectpass"}'
```

### Update Dummylander
Just to update the code (i.e. install the latest `index.php`) define only the `domains` variable:

```
ansible-playbook -l test.yourdomain.com -K install.yml \
--extra-vars '{"domains": ["test.dummylander.net"]}'
```

### Change Password

Just to set (change) a password (i.e. re-create `settings.php`) define variables `domains` and `dummypass`:

```
ansible-playbook -l test.yourdomain.com -K install.yml \
--extra-vars '{"domains": ["test.dummylander.net"], "dummypass": "yournewsecrectpass"}'
```

## License and Acknowledgement

 * License: MIT, see `LICENSE`
 * Please note that the `DuckDuckGo-DaxSolo.svg` used in the sample page is &copy; DuckDuckGo and not covered by Dummylander license.
