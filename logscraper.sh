#!/bin/sh
#
# Version: 1.0
# Developed by: Hans-Michael Varbaek
# Note:
# This script only looks for calls to files like this: =http://somedomain.txt?
# including other extensions. It's quite common that attackers append a question
# mark to their request, as this is widely taught in various RFI guides.
# 
# Suggestions and code improvements are always welcome.

clear
echo -n "\r\n\
 /------------------------------------\\
 |  Simple Apache access.log scraper  |\r\n\
 | Developed by: Hans-Michael Varbaek |\r\n\
 \\------------------------------------/\r\n"

if [ ! -d "output" ]; then
echo "[*] Creating 'output' directory.."
mkdir output
fi

echo "[?] This file parses files in the following format: access.log.*"

echo "[+] Creating a list of raw payloads in: output"
cat access.log.* |grep -P '=((http)|(https))[a-zA-Z0-9:/\.-].*((.txt\?)|(.jpg\?)|(.png\?)|(.gif\?))' -o|sed 's/=http/http/g' > output/RawPayloads_`date +%d-%M-%Y`
echo "[+] Generating a list of attacking IPs in: output"
cat access.log.* |grep -P '=((http)|(https))[a-zA-Z0-9:/\.-].*((.txt\?)|(.jpg\?)|(.png\?)|(.gif\?))' |grep -P '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3} -' -o|sed 's/ -//g' > output/AttackingIPs_`date +%d-%M-%Y`

echo "[!] Please note that the output has not been sorted."