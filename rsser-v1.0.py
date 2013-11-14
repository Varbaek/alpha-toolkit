#!/usr/bin/python
#
# BallastSec RSS Bot Scraper
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
global pbotdir
global rainxdir
output = "botConfiguration"
pbotdir = output+"/pBot"
rainxdir = output+"/RA1NX"

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
 / BallastSec RSS Bot Scraper  \\
 |-----------------------------|
 | Developed by: Capt. Obvious |
 | %s | aka. Varbaek |
 \\-----------------------------/
""" % v
   print var

def getpBot():
  i = 0
  z = 0
  x = 0
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
  
  # Check how many items are in the list first
  for item in list:
    z = z+1

  print "[*] Total %d pBots returned." % z

  for item in list:
    realtitle = item[ "title" ].replace('pBot ','')
    fullpath = pbotdir+"/"+realtitle
    try:
      with open(fullpath): 
        x = x+1
        pass
    except:
      #print "[+] Adding bot (%s/%s) to library: %s" % (i,z,realtitle) # Noisy
      fp = open(fullpath, 'wb')
      fp.write(base64.b64decode(item[ "summary" ]))
      fp.close()
      i = i+1
      
  print "[-] Found %d pBot configuration entries." % x
  print "[+] Added %d new pBot configuration entries." % i
  
def getRAINX():
  i = 0
  z = 0
  x = 0
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
    
  # Check how many items are in the list first
  for item in list:
    z = z+1

  print "[*] Total %d RA1NX bots returned." % z

  for item in list:
    realtitle = item[ "title" ].replace('RA1NX ','')
    fullpath = rainxdir+"/"+realtitle
    try:
      with open(fullpath): 
        x = x+1
        pass
    except:
      #print "[+] Adding bot (%s/%s) to library: %s" % (i,z,realtitle) # Noisy
      fp = open(fullpath, 'wb')
      fp.write(base64.b64decode(item[ "summary" ]))
      fp.close()
      i = i+1
  
  print "[-] Found %d RA1NX configuration entries." % x
  print "[+] Added %d new RA1NX configuration entries." % i
  
# /************************\
# |  MAIN FUNCTION MODULE  |
# \************************/
def main():
  try:
    checkDir(output)
    checkDir(pbotdir)
    checkDir(rainxdir)
    checkOS()
    printBanner()
    getpBot()
    getRAINX()
  except KeyboardInterrupt:
    print '\n[*] CTRL+C detected, shutting down.'
    sys.exit(1)

if __name__ == "__main__":
  main()