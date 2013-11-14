#!/bin/sh

# Use  this script to quickly identify ra1nx in your "decoded" directory.
# Note: This script assumes that all of the decoded scripts, are 100% 
# decoded and will therefore return accurate results in that case.

echo -n ">> RA1NX Finder <<\r\n"
cd decoded
echo "[*] Total number of RA1NX Bots found: " 
grep -R ra1nx * --binary-files=text|echo "`wc -l`/2" | bc
echo "[*] Raw dump:"
grep -R ra1nx * --binary-files=text