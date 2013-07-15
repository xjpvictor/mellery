mellery
=======

A PHP photo gallery
-------

This is a simple photo gallery written with PHP. All information and image files are stored on [box.com][b] and retrieved using the API provided.

## Features ##

### Cache ###

Cache is enabled for faster access and lower CPU/memory consumption

* Thumbnails of the images

* Pages that have been visited

* Image lists and folder lists downloaded from box.com

### Security ###

* Access privileges can be specified on per album basis

    * Make albums public
    * Limit access of albums to visitors with access code
    * Mark albums as private and no one will be able to access _(Default for new albums)_

* Specify an expiration time for thumbnails to prevent hot-linking

* Enable Google 2-step authentication for login

* During login, password will be hashed with a time-based-one-time-passcode to enhance security

* Restrict access if failed to login too many times

* Expire sessions if IP address is changing too frequently

**HTTPS is highly recommended and is required during setup**

### Others ###

* Disqus integrated

* Built-in statistics of page views with option of _Do Not Track_ for the visitors

* Disable search engine indexing

* Easily customize the page

* Keyboard shortcut enabled for navigation

* Display exif information and geolocation when available

* CDN support for static contents and thumbnails

## Setup and backup ##

PHP-GD required.

Access _admin/setup.php_ to setup the site.

All personal data is in the directory _data_. Backup is only needed for this directory.

When restoring, access _admin/setup.php_ to reauthenticate with box.com.

## Notes ##

Box.com will expire the authentication if the token is not refreshed in 14 days. You may need to set up a cron job to access _utils/update.php_ at least once every 14 days. Update.php will do nothing but update the token.

## License ##

This work uses MIT license. Feel free to use, modify or distribute. I'll NOT be responsible for any loss caused by this work.

[b]: https://www.box.com "box.com"
