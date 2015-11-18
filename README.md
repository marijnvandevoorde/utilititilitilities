# Sevenedge Utilities  Library#

## Installation instructions##

Since we want this repo to remain private, we can't add it to packagist for composer installation. Using git as a repository next to packagist is quite simple though. Just add this to the bottom of your composer.json file (or add the repository to existing repositories);
```!json
 "repositories": [
    {
        "type": "vcs",
        "url":  "git@bitbucket.org:sevenedge/sevenedge-utilities.git"
    }
]
```

Now, add the Library to the require section as you would with a regular packagist repository:
```
    "Sevenedge/Utilities": "dev-master"
```        

Run composer update and you're done!

## Using the libarary ##
The classes & their methods should speak for themselves. If not, they might have some in-code comments. 
If  extensive instructions are in place, you should find / put them in the docs folder.

## Remarks ##

If the website is not hosted on the client's server, or if you have to push the code to the client (like prophets), please remove any class you're not using. We don't want to give away all our code for free :-)


## Requirements / Prequisites ##

We've decided not to put the requirements in composer because they're only rarely needed, and not everything can be installed through composer anyway. But requirements you might bump into are:
* a lot of the classes require curl to be installed
* some require GD or Imagick for Image processing
* the twitterapi requires https://twitteroauth.com/