The tiny api Project
Copyright 2013 Michael C. Montero (mcmontero@gmail.com)

GOALS
=====

    Primary
    -------
      * To provide a minimalist framework for developing REST based API's in
        PHP.
      * To automate as much functionality as possible for handling data
        interactions from the API end point to the data store.

    Secondary
    ---------
        * To completely abstract away the data store layer and make switching
          between data stores seamless.
        * To abstract SQL to an intermediate, minimal "language".
        * To automate the building of RDBMS objects.

APACHE CONFIGURATION
====================

    - Add the following rewrite rule into your Apache configuration:

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule . dispatcher.php [L]
        </IfModule>

      If a file exists in the file system, that file will be served as is
      through Apache.  If a file does not exist (or for everything else),
      requests will be routed to the /dispatcher.php file (found in the
      tiny api repository) and tiny api will take over.

    - Inside of the tiny api repository is a file called dispatcher.php.
      Either copy this file into your Apache document root or link to it.

    - Restart Apache.

PHP CONFIGURATION
=================

    - Add the base directory for tiny api to your PHP include path in php.ini.

      Examples:

        include_path = "/path/to/tiny-api"
        include_path = ".:/path1:/path/to/tiny-api"

    - /path/to/tiny-api is the directory that contains the following
      directories and files:

        0.0/
        base/
        bin/
        conf/
        dispatcher.php
        LICENSE
        README.md

    - The tiny-api-conf.php file (an example of which is provided in conf/)
      must be loaded in order for things to function properly.  This can
      occur either by adding the path to your version of the configuration file
      to the auto_prepend_file directive in php.ini or by including it in
      another file that is loaded prior to the tiny api functionality being
      executed.

REST API URL SCHEME
===================

    - https://[domain]/[version number]/[entity]{/accessor/../..}{/id}

      Examples:

        https://api.your-domain.com/1.0/user
        https://api.your-domain.com/1.1/user/public-profile
        https://api.your-domain.com/1.1/user/1234
        https://api.your-domain.com/2.0/store/manager/5678
        https://api.your-domain.com/3.0/client/products/widgets/list

    - The accessor part of the URL can now be infinite depth.  Regardless of
      depth, an ID value can always be provided.
