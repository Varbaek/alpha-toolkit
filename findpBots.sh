#!/bin/sh

# Use  this script to quickly identify pBots in your "decoded" directory.
# Note: This script assumes that all of the decoded scripts, are 100% 
# decoded and will therefore return accurate results in that case.

echo -n ">> pBot Finder <<\r\n"
cd decoded
echo "[*] Total number of pBots found: " 
grep -R pBot * --binary-files=text|echo "`wc -l`/2" | bc
echo "[*] Raw dump:"
grep -R pBot * --binary-files=text