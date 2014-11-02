timezone:
	echo "America/Chicago" | sudo tee /etc/timezone && dpkg-reconfigure --frontend noninteractive tzdata

update-apt:
	apt-get update -y

base: update-apt
	DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" upgrade
	apt-get install -y vim curl wget git-core git-sh
	git config --global push.default simple
	git config --global user.name "Larry Garfield"
	git config --global user.email garfield@palantir.net

apache: base
	apt-get install -y apache2
	rm /etc/apache2/sites-available/000-default.conf
	ln -s /vagrant/config/apache2-default-site.conf /etc/apache2/sites-available/000-default.conf
	a2enmod rewrite
	service apache2 restart
	[ -f /var/www/html/index.html ] && rm /var/www/html/index.html
	[ -z "$(ls -A /var/www/html)" ] && rm -r /var/www/html

php: update-apt apache
	apt-get install -y php5 php5-dev php5-cli libapache2-mod-php5 php5-mcrypt php5-curl php5-xdebug
	pear install PHP_CodeSniffer
	wget https://phar.phpunit.de/phpunit.phar
	chmod +x phpunit.phar
	mv phpunit.phar /usr/local/bin/phpunit

composer:
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar /usr/local/bin/composer

mongodb-repo:

mongodb: mongodb-repo
	apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 7F0CEB10
	echo 'deb http://downloads-distro.mongodb.org/repo/ubuntu-upstart dist 10gen' | sudo tee /etc/apt/sources.list.d/mongodb.list
	# Figure out how to make this depend on update-apt, rather than needing to put
	# These all in one command so that apt-get update gets called twice.
	apt-get update -y
	apt-get install -y mongodb-org
	apt-get install -y php5-mongo
	sed -i 's/^bind_ip\(\s*=.*\)/#bind_ip\1/' /etc/mongod.conf
	sudo service mongod restart

gitconfig: base
	# ignore file mode in git
	git config core.fileMode false

redis:
	apt-get install -y redis-server

xdebug: base
	#Enable XDebug for remote debugging.
	echo "xdebug.remote_autostart = On" >> /etc/php5/mods-available/xdebug.ini
	echo "xdebug.remote_enable = On" >> /etc/php5/mods-available/xdebug.ini
	echo "xdebug.remote_connect_back = On" >> /etc/php5/mods-available/xdebug.ini
	service apache2 restart

mysql: base
	apt-get install -y mysql-server mysql-server-5.5 mysql-client mysql-client-core-5.5 mysql-client-5.5 php5-mysql
	mysql -u root -e "CREATE USER 'test'@'localhost' IDENTIFIED BY 'test';"
	mysql -u root -e "GRANT ALL PRIVILEGES ON * . * TO 'test'@'localhost';"

sqlite: base
	apt-get install -y php5-sqlite sqlite
