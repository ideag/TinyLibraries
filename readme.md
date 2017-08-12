# TinyLibraries

TinyLibraries is a small attempt to solve lack of library sharing and library dependency capabilities in WordPress. It allows plugin developers to define what libraries they require for their code to function in plugin headers, like this

```
/*
Plugin Name: TinyLibraries Sample A
Plugin URI: http://arunas.co
Description: Loads A library
Version: 0.1.0
Author: Arūnas Liuiza
Author URI: http://arunas.co
Text Domain: tinylibraries
Libraries: wp-background-processing
*/
```

In this example plugin declares that it requires WP Background Processing library. It will be downloaded and installed when this plugin is activated.

Later in the code, plugin developer can require_once the library via `TinyLibraries()->require_library( 'wp-background-processing' );` method. You can see this in work in sample plugins: `tinylibraries-sample-a.php`, `tinylibraries-sample-b.php` and `tinylibraries-sample-ab.php`.

Currently supported libraries:

* [WP Background Processing](https://github.com/A5hleyRich/wp-background-processing) by Delicious Brains Inc. - `wp-background-processing`
* [ButterBean](https://github.com/justintadlock/butterbean) by Justin Tadlock. - `butterbean`
* [Extended Taxonomies](https://github.com/johnbillion/extended-taxos) by John Blackbourn. - `extended-taxos`
* [Extended CPTs](https://github.com/johnbillion/extended-cpts) by John Blackbourn. - `extended-cpts`
