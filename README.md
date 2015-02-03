# Sevenedge Utilities #

## Installation instructions##

1. Add a submodule and use the git url of this repostory. Unpack it to app/Vendor/Sevenedge
2. copy the autoload.php file from the root to the app/Vendor folder.
3. Wherever you need a class in this library, load that autoload.php file using require_once(APP . 'Vendor' . DS . 'autoload.php'). If you need it on every request, just put it on top of the AppController.php file or in bootstrap.
4. You can now use the classes if you use the right Namespace prefix. For example, to use the generate method in the Bitly class, just do: 
```
#!php

Sevenedge\Utilities\Bitly::generate('http://www.google.com', ...);
```

The namespacing and the autoload file take care of loading the classes for you. No need to include all files you need separately. This also lazy loads, so the overhead stays limited.


## Remarks ##

If the website is not hosted on the client's server, or if you have to push the code to the client (like prophets), please remove any class you're not using. We don't want to give away all our code for free :-)


## Requirements / Prequisites ##
* a lot of the classes require curl to be installed
* some require GD or Imagick for Image processing
* the twitterapi requires https://twitteroauth.com/