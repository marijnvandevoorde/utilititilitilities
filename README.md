Sevenedge Utilities

Installation instructions:

1. Add a submodule and use the git url of this repostory. Unpack it to app/Vendor/Sevenedge
2. Move the autoload.php to the app/Vendor
3. Wherever you need a class in this library, load that autoload.php file using require_once(APP . 'Vendor' . DS . 'autoload.php'). If you need it on every request, just put it on top of the AppController.php file or in bootstrap.


Remarks:

If the website is not hosted on the client's server, or if you have to push the code to the client (like prophets), please remove any class you're not using. We don't want to give away all our code for free :-)


Requirements / Prequisites:
* a lot of the classes require curl to be installed
* some require GD or Imagick for Image processing
* the twitterapi requires https://twitteroauth.com/
