# Dummylander

Superdupersimple landing page administration tool.

## HOWTO

 1. Copy `dist/*` to your PHP-enabled web server. Make sure you copy also the `admin/` subdirectory and its contents.
 1. Set admin password by editing `settings.php` (see *Authentication* for details).
 1. Make sure the URL `http(s)://yourdomain.com/settings.php` returns an empty page.
 1. Log in to admin UI: `http(s)://yourdomain.com/?admin`
 1. Enter the password and profit!

## This works

 * The most essential page properties (title, description, keywords, og:image, custom CSS) can be edited.
 * The most essential page part properties (MD-formatted text, images, fonts, font colors) can be edited.
 * You can add, remove and move page parts.

## This does not work yet

 * You cannot upload or remove files.
 * There must be a tons of page or part attributes missing.
 * The authentication requires too much IT skills to set up.

## Authentication

Dummylander has very simple, robust and straightforward authentication. Each time the
admin UI makes call to backend it re-sends the cleartext password. Therefore,
it is essential for security reasons to *secure all your connections with SSL*.
In other words do not serve your site at all through `http` but preferably only `https`. Use
the `https` connection at least  whenever editing your site (`https://yourcomain.com/?admin`).

The cleartext admin password is stored in `settings.php`. Here is a sample content for the file:
```
<?php

$ADMIN_PASSWORD="verysecret";

?>
```

Don't forget to double-check that the URL `http(s)://youdromain.com/settings.php`
returns an empty page.
 * If you get error 404 (Not found) make sure you entered the URL correctly and
   you really have uploaded the `settings.php` to your server.
 * If you see the file content the PHP settings of the server are not correctly set.

## Installing by Ansible

The Ansible install scripts are provided in `ansible/`. The scripts work for Debian 9
with nginx and PHP-FPM 7.2 installed. The scripts are useful if you'd like i.e. to
serve several Dummylander sites on one host without setting each host manually.

The script does following things for you:
 * Prepare shared SSL files (session ticket key and Diffie-Hellman parameter file)
 * Get a Let's Encrypt certificate for the site (if parameter `certbot_email` is set)
 * Set a cronjob to renew the certificates
 * Create a nginx configuration for the site
 * Enable the site (make it effective)
 * Install the latest master build of Dummylander
 * Set the site password (if parameter `dummypass` is set)
 * Create a empty configuration

To make the setup at `test.yourdomain.com` to host a new Dummylander site
`test.dummylander.net`:
 1. Create a DNS record `test.dummylander.net` to point to `test.yourdomain.com`
 1. To make everything:
 ```
 ansible-playbook -l test.yourdomain.com -K install.yml \
 --extra-vars '{"domains": ["test1.dummylander.net", "test2.dummylander.net"], "certbot_email": "office@sivuduuni.biz", "dummypass": "yournewsecrectpass"}'
 ```

Just to set (change) a password (i.e. re-create `settings.php`):

```
ansible-playbook -l test.yourdomain.com -K install.yml \
--extra-vars '{"domains": ["test1.dummylander.net", "test2.dummylander.net"], "dummypass": "yournewsecrectpass"}'
```

The server `test.yourdomain.com` has to be in group `dummylander` in `/etc/ansible/hosts`:

```
[dummylander]
test.cupdom.com
```

## License and Acknowledgement

 * License: MIT, see `LICENSE`
 * Please note that the `src/data-sample/DuckDuckGo-DaxSolo.svg` is &copy; DuckDuckGo and not covered by Dummylander license.
