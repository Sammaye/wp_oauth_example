wp_oauth_example
================

Example of OAuth using the OAuth Provider 2.0 plugin: http://wordpress.org/plugins/oauth2-provider/

To use it:

~~~
require 'WordPressOauth.php';
$oauth = new WordPressOauth(array(
		'consumerKey' => 'key',
		'consumerSecret' => 'secret'
));
$oauth->authorise();
~~~

And that's it. After you call `authorise()` you should be logged in using OAuth.

Just like all the other OAuth plugins this one is incomplete/has no guarantee of working.

It cannot do refresh tokens for one because the plugin has no ability to do them.

It also has a problem redirecting to the login form since the plugin cannot correctly detect some root urls for WordPress installations.

But it is an example of using OAuth in WordPoress so have fun.
