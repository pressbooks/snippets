First, Install dependencies. See Part 3 of https://docs.pressbooks.org/installation/

Next, get [WP-CLI][1].

Once WP-CLI is installed on your server, the following shell commands executed in the site root will download and install a fresh version of Pressbooks. 

> TIP: You copy/pasted and it didn't work as is? Of course not! You need to put in the correct information for your server..

    wp core download
    wp core config --dbname="dbname" --dbuser="dbuser" --dbpass="dbpass" --extra-php &lt;&lt;PHP
    /* Pressbooks */
    define( 'WP_DEFAULT_THEME', 'pressbooks-book' );
    define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
    define( 'PB_KINDLEGEN_COMMAND', '/opt/kindlegen/kindlegen' );
    define( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /opt/epubcheck/epubcheck.jar' );
    define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );
    define( 'PB_SAXON_COMMAND', '/usr/bin/java -jar /opt/saxon-he/saxon-he.jar' );
    define( 'PB_MATHJAX_URL', 'http://localhost:3000/' );
    PHP
    wp core multisite-install --url='http://domain.com' --title='Pressbooks' --admin_user='username' --admin_password='password' --admin_email='user@domain.com'
    wp plugin delete hello
    wp plugin update-all
    wp plugin install https://pressbooks.org/download/pressbooks/
    mkdir wp-content/mu-plugins &amp;&amp; cp wp-content/plugins/pressbooks/hm-autoloader.php wp-content/mu-plugins
    wp plugin activate pressbooks --network
    wp theme install https://pressbooks.org/download/mcluhan/
    wp theme install https://pressbooks.org/download/aldine/
    wp theme install https://github.com/pressbooks/pressbooks-jacobs/archive/master.zip
    wp theme install https://github.com/pressbooks/pressbooks-austenclassic/archive/master.zip
    wp theme install https://github.com/pressbooks/pressbooks-clarke/archive/master.zip
    wp theme install https://github.com/pressbooks/pressbooks-donham/archive/master.zip
    wp theme install https://github.com/pressbooks/pressbooks-fitzgerald/archive/master.zip
    wp theme enable pressbooks-book --network
    wp theme enable pressbooks-jacobs --network
    wp theme enable pressbooks-clarke --network
    wp theme enable pressbooks-donham --network
    wp theme enable pressbooks-fitzgerald --network
    wp theme enable pressbooks-austenclassic --network


 [1]: https://wp-cli.org/
