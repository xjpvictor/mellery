mellery
=======

A PHP photo gallery
-------

This is a simple photo gallery written with PHP. All information and image files are stored on [Box.com][b] and retrieved using the API provided.

## Features ##

### Cache ###

Cache is enabled for the following items for faster access and lower CPU/memory consumption

* Thumbnail of the images

* Pages that have been visited

* Image lists and folder lists downloaded from [Box.com][b].

### Security ###

* Access privileges can be specified on per album basis

    * You can set albums as public
    * Allow access of multiple albums by using a general access code
    * Specify an access code for a particular album
    * Use a temporary access code with certain period of validity
    * Mark as private and no one will be able to access
    * New albums are set as private by default

* Specify an expiration time for thumbnails to prevent hot-linking

* Enable Google 2-Step authentication for login

* During login, password will be hashed with a time-based-one-time-passcode to enhance security

* Restrict access if failed to login too many times

* Expire sessions if IP address is changing too frequently

**HTTPS is highly recommended and is required during setup**

### Others ###

* Disqus integrated

* Built-in statistics of page views with option of _Do Not Track_ for the visitors

* Easily customize the page

* Keyboard shortcut enabled for navigation

[b]: https://www.box.com "Box.com"
