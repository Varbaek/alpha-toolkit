#!/usr/bin/python
#
# PHP Payload/Decoder Scraper
v = "Version: 1.3"
# Changelog:
# 1.0 - First stable version
# 1.1 - Added a check for mime-types. New directory structure.
#       Exception handling for downloadBot().
# 1.2 - Added multithreading to downloadBot(). (Not finished but it works.)
# 1.3 - Added additional user-controls when a large amount of bots/payloads
#       are returned from the website, where the user wants to download 'X' amount.
#
# Python 2.7.3
# Tested on Kali Linux
# Developer: Hans-Michael Varbaek
# URL: https://defense.ballastsecurity.net/decoding/index.php
# 
# pip install requests
# pip install beautifulsoup4
# pip install python-magic
#
# Requires: php5-cli (For bwall's decoder)
#
# Known bugs:
# - After threads have been spawned, CTRL+C will not stop the tool.
#   Setting exitFlag to 1 when a KeyboardInterrupt happens will probably solve it.
# - Sometimes the program just hangs when downloading a bot. 
#   Note: The website is storing payloads of +10MB)
# - Lots more bugs I bet. (PS: It's the first time I ever wrote a scraper)
#
# License:  Attribution-ShareAlike 3.0 Unported
# http://creativecommons.org/licenses/by-sa/3.0/deed.en_US

##################################
  ########## IMPORTS ###########
##################################

# Generic imports
import os
import sys
import time

# Used for handling scraper data
import requests
from bs4 import BeautifulSoup

# Used in mime-check and gunzip modules
import magic
import shutil
import subprocess

# Used for threading
import threading
import Queue

##################################
 ########## VARIABLES ###########
##################################

# Global variables
global output_directory
global sorted_directory
global decode_directory
global tmpdir
output_directory = "raw"
sorted_directory = "sorted"
decode_directory = "decoded"
tmpdir = '/tmp/scraper'

# Flag used for multithreading
global exitFlag # Reason why this is a global now is because everything was moved functions.
exitFlag = 0

# Thread Tuning Variable(s)
global items_per_thread
items_per_thread = 4

# Associate known mime-types with directory names.
# Do NOT change the order of the dictionary below.
# Adding more mime-types may change the subdirs variable.
global types
types =  {'application/octet-stream':'unknown',
	  'application/pdf':'pdf',
	  'image/gif':'embedded',
	  'text/x-php':'source',
	  'text/x-c++':'source',
	  'application/x-rar':'compressed',
	  'application/zip':'compressed',
	  'text/x-perl':'scripts',
	  'text/x-shellscript':'scripts',
	  'text/plain':'possible_source',
	  'text/html':'possible_source',
	  'application/xml':"possible_source"
	  }

#################################
 ########## CLASSES ############
#################################

class myThread (threading.Thread):
    def __init__(self, threadID, name, q):
        threading.Thread.__init__(self)
        self.threadID = threadID
        self.name = name
        self.q = q
    def run(self):
        #print "[*] Starting " + self.name
        process_data(self.name, self.q)
        #print "[*] Exiting " + self.name

##################################
 ########## FUNCTIONS ###########
##################################

# Processes data, provides debug data
def process_data(threadName, q):
  global exitFlag
  while not exitFlag:
    queueLock.acquire()
    if not workQueue.empty():
      data = q.get()
      queueLock.release()
      #print "%s processing %s" % (threadName, data)
    else:
      queueLock.release()
    #time.sleep(1)

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

def printBanner():
   var = """  _____________________________
 / PHP Payload Decoder Scraper \\
 |-----------------------------|
 | Developed by: Capt. Obvious |
 | %s | aka. Varbaek |
 \\-----------------------------/
""" % v
   print var

# Credits for uniq() => unwind
def uniq(input):
  output = []
  for x in input:
    if x not in output:
      output.append(x)
  return output

def checkDir(directory):
  if not os.path.exists(directory):
    os.makedirs(directory)

def copy(src, dest):
  try:
    shutil.copy2(src, dest) # Copies metadata as well.
  except shutil.Error:
    print "[!] An error occurred copying the file to: %s" % dest

def move(src, dest):
  try:
    shutil.move(src, dest)
  except shutil.Error:
    print "[!] An error occurred moving the file to: %s" % dest

def downloadBot(md5):
  url = "https://defense.ballastsecurity.net/decoding/index.php?raw=%s&download=true" % md5
  fullpath = output_directory+"/"+md5+".gz"
  try:
    r = requests.get(url, stream = True) # here we need to set stream = True parameter
  except IOError:
    print "[!] Could not connect to: https://defense.ballastsecurity.net/decoding/index.php"
    print "[!] Quitting..."
    sys.exit(1)
  with open(fullpath, 'wb') as f:
    for chunk in r.iter_content(chunk_size=1024): 
      if chunk: # filter out keep-alive new chunks
        f.write(chunk)
        f.flush()

# This is used to parse a subset of a larger list
def parselist(id):
  #z = 0
  for item in divlist[id]:
    print '[+] Adding payload to database: %s' % item
    downloadBot(item)
    #z = z+1
    #if z == items_per_thread-1:
      #workQueue.task_done()
    

# Used for dividing the linklist into separate chunks
def chunks(l, n):
  return [l[i:i+n] for i in range(0, len(l), n)]

##################################
 ######### PREPARE ENV ##########
##################################

# Clear screen and print banner
checkOS()
printBanner()

# Create upper-level directories
checkDir(output_directory)
checkDir(sorted_directory)
checkDir(decode_directory)

# Note: The function below relies on uniq() defined above.
# The new order is: scripts, source, unknown, compressed, possible_source, embedded, pdf
global subdirs
subdirs = uniq([x for x in types.values()])

# Create lower-level directories
# Do NOT change the list index numbers.
for entry in subdirs:
  sortname = '%s/%s' % (sorted_directory,entry)
  checkDir(sortname)
  if entry != subdirs[0] and\
     entry != subdirs[2] and\
     entry != subdirs[3] and\
     entry != subdirs[6]:
    decname = '%s/%s' % (decode_directory,entry)
    checkDir(decname)

##################################
 ######## GET RAW CODE ##########
##################################
# Begin transforming serial code into ugly functions
def getrawcode():
 # Define global variables, otherwise we'll get a million exceptions
 global queueLock
 global workQueue
 global threads
 global threadID
 global items_per_thread
 global exitFlag # Threads wouldn't finish without having exitFlag set correctly, gawd.

 # Fetch and parse data
 try:
   r  = requests.get("https://defense.ballastsecurity.net/decoding/index.php")
   data = r.text
   soup = BeautifulSoup(data)
 except KeyboardInterrupt:
   print "[*] Keyboard Interrupt, quiting.."
   sys.exit(1)
 except IOError:
   print "[!] Could not connect to: https://defense.ballastsecurity.net/decoding/index.php"
   print "[!] Quitting..."
   sys.exit(1)

 # Create a list of URLs (Store only relevant URLs)
 linklist = []
 botlist = []
 i = 0

 for link in soup.find_all('a'):
   if 'http' not in link.get('href'):
     linklist.insert(i, link.get('href'))
     i = i+1

 # Remove duplicates. (Yes, that website we're scraping sometimes has duplicates.)
 linklist = uniq(linklist)

 print "[*] Total payloads found: %d " % len(linklist)

 # Remove "?raw=" from filenames, check if they exist locally; if not: create a new list.
 for link in linklist:
   filename = link.replace('?raw=','')
   fullpath = output_directory+"/"+filename+'.gz'
   try:
     with open(fullpath): pass
   except:
     botlist.insert(i, filename)

 # Because it takes ages to download a lot of bots from the website, let the user control how many (new) bots from top to bottom should be downloaded.
 # Every time I look at my code and think about it, there's another logical bug that needs to be fixed :-/ When will it end.
 if len(botlist)>=200:
   question = "[*] More than 200 new payloads were found (%d). Download may take ages and cause the program to malfunction.\n\
[!] If you want to skip this step, press CTRL+C.\n\
[?] If you wish to continue, how many payloads do you want to download?\n\
[?] Type an integer or 'all': " % len(botlist)
   try:
     allorint = raw_input(question)
   except KeyboardInterrupt:
     print "\n[!] Keyboard interrupt, skipping download.."
     return
   if allorint == 'all':
     if len(botlist)>200:
       items_per_thread = 15 # We can't spawn a billion threads, fix this to be dynamically computed later on.
     pass
   else:
     try:
       leinteger = int(allorint) # Yeah I know it could've been put below, feel free to optimize it.
     except ValueError:
       leinteger = 50
       print "[!] Not a valid integer. Stop messing around..\n[*] Downloading %d payloads instead." % leinteger
     botlist = botlist[:leinteger]
     if leinteger>200:
       items_per_thread = 15 # Can't spawn a billion threads, fix later.

 # Create a new global list that has been divided into chunks of: items_per_thread (e.g. 4)
 threadlist = chunks(botlist, items_per_thread) 

 print "[*] Splitting into %d threads" % len(threadlist)

 # Other thread variables
 queueLock = threading.Lock()
 workQueue = Queue.Queue(0) # Standard value was 10 but caused a deadlock
 threads = []
 threadID = 1

 # Start the threads!
 for id in xrange(len(threadlist)):
   tName = "Thread-%d" % id
   thread = myThread(threadID, tName, workQueue)
   thread.start()
   threads.append(thread)
   threadID += 1

 global divlist # Is used in: parselist()
 divlist = chunks(botlist, items_per_thread)

 # Fill the queue
 queueLock.acquire()
 for id in xrange(len(divlist)):
   try:
     workQueue.put(parselist(id))
   except KeyboardInterrupt:
     print "[*] Keyboard Interrupt, quiting.."
     sys.exit(1)
 queueLock.release()

 # Wait for queue to empty
 while not workQueue.empty():
   pass

 # Notify threads it's time to exit
 exitFlag = 1

 # Wait for all threads to complete
 for t in threads:
     t.join()
 print "[*] Payloads finished downloading."

################################
 ###### GUNZIP DAT BOT ########
################################
def gunzipme():
 checkDir(tmpdir)
 i = 0 # Reset counter
 unpacklist = []
 # Find out how many files need to be gunzipped.
 for file in os.listdir(output_directory):
   if '.gz' in file:
     i = i+1
     unpacklist.insert(i, file)
 print "[*] Found %d compressed payloads. Copying to %s.." % (i, tmpdir)
 
 # Copy the files to a temporary directory first, this script just keeps getting more stupid.
 for file in unpacklist:
   if '.gz' in file:
     srcpath = "%s/%s" % (output_directory,file)
     dstpath = "%s/%s" % (tmpdir,file)
     try:
       with open(dstpath): pass # If the decoded file exists, go to next file in the list.
     except:       
       copy(srcpath, dstpath)       
 
 i = 0 # Reset counter
 # Gunzip the amount of files that requires so.
 for file in os.listdir(tmpdir):
   if '.gz' in file:
     packed = "%s/%s" % (tmpdir,file)
     unpacked = "%s/%s" % (tmpdir,file.replace('.gz',''))
     try:
       with open(unpacked): pass # If the decoded file exists, go to next file in the list.
     except:       
       pipe = subprocess.Popen(["gunzip","-q", packed], stdout=None, stderr=None) # Gunzip the downloaded file
       i = i+1 
 
 print "[*] Unpacked %d payloads." % i


##################################
 ####### MIME-TYPE CHECK ########
##################################

# Reads files and puts them into their relevant folders.
# I know, it's an ugly way of checking whether a file exists before copying it.
# Hint: It's meant to save CPU time when updating your "database".
def mimetypecheck():
 i = 0 # Reset counter
 x = 0 # Reset counter
 for file in os.listdir(tmpdir):
   if '.gz' not in file:
     filename = '%s/%s' % (tmpdir,file)
     mime = magic.Magic(mime=True)
     mimeresult = mime.from_file(filename)
     try:
       dirname = '%s/%s' % (sorted_directory,types[mimeresult])
       fullpath = dirname+'/'+file
       try:
         with open(fullpath): 
           x = x+1
           pass
       except:
         copy(filename, dirname)
         i = i+1 # Count each time we do this
     except KeyError: # Unknown mime-type
       dirname = '%s/unknown' % (sorted_directory)
       fullpath = dirname+'/'+file
       try:
         with open(fullpath): pass
	 x = x+1
       except:
         copy(filename, dirname)
         i = i+1 
 # End of ugly function
 
 # For unknown reasons (I blame cosmic rays), the 'x' variable differs from 'i' on a range of 1-2.
 print "[*] Copied: %d new files into the sorted directories." % i
 print "[*] Found: %d files in the sorted directories already." % x
 
 print "[*] Removing: %s" % tmpdir
 time.sleep(2)
 shutil.rmtree(tmpdir)


##################################
 ######### DECODE BOTS ##########
##################################
def decodebots():
 print '[*] Checking for new bots to decode.';
 i = 0 # Reset counter
 z = 0
 buffer = "" # Empty
 dirlist = [subdirs[1],subdirs[4],subdirs[5]]
 total_files = []

 # Needed a quick total of all the files in the directory.
 for dir in dirlist:
   oldpath = "%s/%s" % (sorted_directory,dir)
   for file in os.listdir(oldpath):
     newfullpath = "%s/%s/%s" % (decode_directory,dir,file)
     try:
       with open(newfullpath): pass # Do not count files that have already been decoded
     except:
       z = z+1

 for dir in dirlist:
   oldpath = "%s/%s" % (sorted_directory,dir)
   for file in os.listdir(oldpath):
     oldfullpath = "%s/%s/%s" % (sorted_directory,dir,file)
     newfullpath = "%s/%s/%s" % (decode_directory,dir,file)
     try:
       with open(newfullpath): pass # If the decoded file exists, go to the next file in the list.
     except:
       with open(newfullpath, 'wb') as filepointer:
         pipe = subprocess.Popen(["php", "bwalldecoder/Decoder/haxx.php", oldfullpath], stdout=filepointer)
         pipe.wait()
         i = i+1
         print "[*] New payload decoded! (%d/%d) [%s]" % (i, z, file)

 if i == 0:
   print '[*] No new payloads to decode.'
 else:
   print '[*] Finished decoding all the payloads.'
 

# /************************\
# |  MAIN FUNCTION MODULE  |
# \************************/
def main():
  try:
    getrawcode()
    gunzipme()
    mimetypecheck()
    decodebots()
  except KeyboardInterrupt:
    print '\n[*] CTRL+C detected, shutting down.'
    sys.exit(1)

if __name__ == "__main__":
  main()

# Written by Hans-Michael Varbaek - Sense of Security 2013