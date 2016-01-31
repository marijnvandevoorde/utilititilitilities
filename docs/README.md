# Marijnworks Utilities  Library#

## Geography ##

- CoordinateSystems: converting between several coordinate systems.
- ShapeFile: Reading shape files (Busplan)
- GoogleGeocodeApi: get geolocation info through Google's (reverse) geocoding API.
- Utilities: Most usefull one probably: polyline decoding, distance calculation

## Mail ##

- CitobiApi: Adding subscriptions to the citobi db (carrefour).
- Mailjet, Mandril: Sending mails
- Mailchimp: subscribing to mailinglist.
- Utilities: strip html from a mail & convert it for a txt version.

## Notification ##

- Boxcar: iOS app for notifications
- Ifttt: probably the most interesting one here: pushing to the ifttt platform and connect your stuff as you prefer.
- Notifier: Wrapper notifier with fallbacks. Send a message to the best available option.
- Pushbullet: notification app on android, chrome, iOS,...

## Social ##

- InstagramAPI: work in progress. Full featured API implementation for instagram. Will include logging in etc.
- SimpleGramAPI: very simple instagram API implementation.
- TwitterAPI: wrapper around the Abraham\TwitterOauth library. scraping tweets & stuff. Tweeting & replying too.
- Pushbullet: notification app on android, chrome, iOS,...>

## StreamingServices ##

- SpotifAPI: spotify api implementation (for samsung multiroom). Creating lists etc.

## Travel ##

- Opentripplanner: All interaction with the open trip planner API.
- Pitobi: scraping data from pitobi, both summer & winter edition.
- Sunweb: scraping data from the Sunweb API.

## Utilities ##

- Basecamp: get accounts, todos, ... Work in progress
- Bitly: generate bit.ly urls
- Browser: Get browser info based on UserAgent string.
- Conversion: Conversion of units. Just inch, ft, m & cm for the time being, but to be expanded.
- CurlRequest: Fetch files over the web, parallel if you like.
- RSAEncryption: Wrapper around openssl RSA encryption, fallback to phpseclib automatically.
- ICal: ICal file parsing.
- ImageTools: Resize, crop,... All things images!
- NormalizeAndValidate: normalization of strings, Validation of e-mail addresses
- Security: Some security utilities: CSRF tokens, number obfuscation etc.
- Varnish: simple class for clearing the Varnish cache.

## Versioning ##

- Git: Basic git operations