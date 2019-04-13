#!/bin/bash

HCRC="93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8"

C_BLACK='\033[0;30m'
C_DARK_GRAY='\033[1;30m'
C_RED='\033[0;31m'
C_LIGHT_RED='\033[1;31m'
C_GREEN='\033[0;32m'
C_LIGHT_GREEN='\033[1;32m'
C_ORANGE='\033[0;33m'
C_YELLOW='\033[1;33m'
C_BLUE='\033[0;34m'
C_LIGHT_BLUE='\033[1;34m'
C_PURPLE='\033[0;35m'
C_LIGHT_PURPLE='\033[1;35m'
C_CYAN='\033[0;36m'
C_LIGHT_CYAN='\033[1;36m'
C_LIGHT_GRAY='\033[0;37m'
C_WHITE='\033[1;37m'
C_NC='\033[0m'

if [[ -f './composer.phar' ]]; 
then
	printf "${C_ORANGE}Delete vendors directory...${C_NC}\n"
	rm -rf "./vendor" &> /dev/null
	printf "${C_ORANGE}Delete composer logs file...${C_NC}\n"
	unlink "composer.lock" &> /dev/null
	printf "${C_ORANGE}Clear composers cache...${C_NC}\n"
	composer.phar clear-cache &> /dev/null
	printf "${C_ORANGE}Delete composer...${C_NC}\n"
	rm -rf ~/.composer &> /dev/null
	unlink "composer.phar" &> /dev/null
fi

printf "${C_GREEN}Get composer installer...${C_NC}\n"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

printf "${C_GREEN}Check composer hash...${C_NC}\n"
unlink 'install.lock' &> /dev/null
php -r "if (hash_file('SHA384', 'composer-setup.php') === '${HCRC}') { echo \"${C_GREEN}Installer verified...${C_NC}\"; } else { echo \"${C_RED}Installer corrupt...${C_NC}\"; file_put_contents('install.lock', ''); unlink('composer-setup.php'); } echo PHP_EOL;"

if [[ -f './install.lock' ]];
then
	printf "${C_GREEN}Update composer-setup.php (https://getcomposer.org/download/) hash checksum in this file or try to install manually...${C_NC}\n"
else 
	printf "${C_GREEN}Install composer...${C_NC}\n"
	php composer-setup.php

	printf "${C_GREEN}Delete composer installer...${C_NC}\n"
	php -r "unlink('composer-setup.php');"

	printf "${C_GREEN}Let's ${C_ORANGE}[php composer.phar]${C_GREEN}...${C_NC}\n"

	printf "${C_GREEN}Fetch require libraries...${C_NC}\n"
	php composer.phar install

	printf "${C_GREEN}All ok...${C_NC}\n"	
fi