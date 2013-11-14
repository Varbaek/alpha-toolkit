Alpha Toolkit
==========

### Presentation 
* Botnets of the Web - How to Hijack One

### Slides
* http://www.slideshare.net/HansMichaelVarbaek/botnets-of-the-web-how-to-hijack-one

### Demo
* https://www.youtube.com/playlist?list=PLIjb28IYMQgqWSjVFsSTT5QY_gPYoynxh

Requirements
------------
* php5-cli (For bwall's decoder used with `scraper.py`)

Installation notes
------------------
* statis.py & rsser.py:

`pip install feedparser`

* scraper.py: 

`pip install requests`
`pip install beautifulsoup4`
`pip install python-magic`

### Tested on:
* Python 2.7.3
* Kali-Linux

Directories
------------
* botConfiguration: Output directory for `rsser.py`
* bwalldecoder: Used to decode scraped payloads locally with `scraper.py`
* raw: Raw payloads found by `scraper.py`
* output: Directory used by `logscraper.sh`
* statisDir: Directory used by `statis.py`
* sorted: Directory where gunzipped payloads are stored after mime-check by `scraper.py`
* decoded: Directory where decoded payloads are stored by `scraper.py`

Tools
-----
* `./logscraper.sh` - Shell script which parses Apache access.log's
* `./statis.py` - Python script that collects all the publish dates in the BallastSec bot RSS feeds.
* `./rsser.py` - Python scripts that collects and decodes all botnet configurations in BallastSec bot RSS feeds.
* `./scraper.py` - Python script that scrapes the website at BallastSec and saves the payloads locally (a check is done first). Gunzips the payloads, checks the mime-type of the payload and stores it in an appropriate directory (sorted/known-mime-type). Unzips known good mime-types (i.e. not archives). Cleans up after itself.
* `./bwalldecoder/Decoder/haxx.php` Accepts a file via CLI and returns the decoded contents only.
* `./findpBots.sh` Finds pBots in the decoded directory.
* `./findRA1NX.sh` Finds RA1NX bots in the decoded directory.

Developed by
------------
* Hans-Michael Varbaek

Code design
-----------
* Spaghetti
* Modify-on-the-fly
* As-long-as-it-works