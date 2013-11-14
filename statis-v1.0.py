#!/usr/bin/python
#
# RSS pubDate Scraper
v = "Version: 1.0"
# Changelog:
# 1.0 - First stable version
#
# Python 2.7.3
# Tested on Kali Linux
# Developer: Hans-Michael Varbaek
# URLs: 
# 	https://defense.ballastsecurity.net/decoding/rss/pbot.rss
# 	https://defense.ballastsecurity.net/decoding/rss/ra1nx.rss
# 
# pip install feedparser
#
# Known bugs:
# - Some of the RSS feed items does not have all the necessary parameters.
#
# License:  Attribution-ShareAlike 3.0 Unported
# http://creativecommons.org/licenses/by-sa/3.0/deed.en_US

# Generic Imports
import os
import sys
import time


# Used to read and parse the RSS feed
import feedparser
import base64

# Global RSS Variables
global pbotrss
global rainxrss
pbotrss = "https://defense.ballastsecurity.net/decoding/rss/pbot.rss"
rainxrss = "https://defense.ballastsecurity.net/decoding/rss/ra1nx.rss"

# Global Directory Variables
global output
output = "statisDir"

# Functions
def checkOS():
  if(os.name) == "posix":
    os.system("clear")
  elif(os.name) == "nt":
    os.system("cls")
    print "\n[!] This tool was created to run under Linux."
    sys.exit(1)
  else:
    print "\n[!] This tool was created to run under Linux."
    sys.exit(1)

def checkDir(directory):
  if not os.path.exists(directory):
    os.makedirs(directory)

def printBanner():
   var = """  _____________________________
 /     RSS pubDate Scraper     \\
 |-----------------------------|
 | Developed by: Capt. Obvious |
 | %s | aka. Varbaek |
 \\-----------------------------/
""" % v
   print var

def getpBot():
  i = 0
  print "[*] Connecting to pBot RSS.."
  try:
    feed = feedparser.parse( pbotrss )
  except:
    print "[!] An error occurred connecting to: %s" % pbotrss
    return
  print "[*] Title: "+feed[ "channel" ][ "title" ] # Bot RSS Feed
  print "[*] Description: "+feed[ "channel" ][ "description" ] # Links to new decoded pBot payloads.
  print "[*] Link: "+feed[ "channel" ][ "link" ] # https://defense.ballastsecurity.net/decoding/rss/pbot.rss
  
  # Assign a new variable
  list = feed[ "items" ]
  
  for item in list:
    writeme = item["published"]+"\r\n"
    filename = output+"/statistics_pbot_"+time.strftime("%H-%M-%S-%Y")
    fp = open(filename, 'a+')
    fp.write(writeme)
    fp.close()
    i = i+1
  
def getRAINX():
  i = 0
  print "[*] Connecting to RA1NX RSS.."
  try:
    feed = feedparser.parse( rainxrss )
  except:
    print "[!] An error occurred connecting to: %s" % rainxrss
    return
  print "[*] Title: "+feed[ "channel" ][ "title" ] # Bot RSS Feed
  print "[*] Description: "+feed[ "channel" ][ "description" ] # Links to new decoded RA1NX payloads.
  print "[*] Link: "+feed[ "channel" ][ "link" ] # https://defense.ballastsecurity.net/decoding/rss/ra1nx.rss
  
  # Assign a new variable
  list = feed[ "items" ]
    
  for item in list:
    writeme = item["published"]+"\r\n"
    filename = output+"/statistics_ra1nx_"+time.strftime("%H-%M-%S-%Y")
    fp = open(filename, 'a+')
    fp.write(writeme)
    fp.close()
    i = i+1
  
 
# /************************\
# |  MAIN FUNCTION MODULE  |
# \************************/
def main():
  try:
    checkDir(output)
    checkOS()
    printBanner()
    getpBot()
    getRAINX()
  except KeyboardInterrupt:
    print '\n[*] CTRL+C detected, shutting down.'
    sys.exit(1)

if __name__ == "__main__":
  main()