http://www.idolbin.com/blog/server-management/vps-setup-guide/setup-nginx-web-server-not-apache-on-ubuntu-10-04/
############################################################
1.
sudo -s
apt-get install python-software-properties
add-apt-repository ppa:nginx/stable
apt-get update
apt-get install nginx

2.
vim /etc/nginx/nginx.conf
#=============== Sample configuration profile optimized for a server with Quad Core Processor and 512MB RAM:
user www-data;
worker_processes 4;

events
{
	worker_connections 1024;
	# multi_accept on;
}

http
{
	##
	# Basic Settings
	##
	sendfile on;
	tcp_nopush on;
	tcp_nodelay on;
	keepalive_timeout 65;
	types_hash_max_size 2048;
	# server_tokens off;
	# server_names_hash_bucket_size 64;
	# server_name_in_redirect off;

	include /etc/nginx/mime.types;
	default_type application/octet-stream;
	
	# Default webpage
	index index.php;

	##
	# Logging Settings
	##
	access_log /var/log/nginx/access.log;
	error_log /var/log/nginx/error.log;
	
	##
	# Gzip Settings
	##
	gzip on;
	gzip_disable "msie6";
	gzip_vary on;
	gzip_proxied any;
	gzip_comp_level 2;
	gzip_buffers 16 8k;
	gzip_http_version 1.1;
	gzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript;

	##
	# Virtual Host Configs
	##
	include /etc/nginx/conf.d/*.conf;
	include /etc/nginx/sites-enabled/*;
}
#=============== End Sample

3.
mkdir -p /var/www/example.com/logs
mkdir -p /var/www/example.com/public_html/adminpanel
mkdir -p /var/www/example.com/public_html/errorpages
chown -R www-data /var/www/example.com/logs

vim /etc/nginx/sites-available/default
#=============== configs start
server
{
	listen			80;
	server_name		www.example.com;
	
	access_log 		/var/www/example.com/logs/access.log;
	error_log		/var/www/example.com/logs/error.log;
	
	root			/var/www/example.com/public_html/;
	
	error_page 401 /errorpages/401.html;
	error_page 403 /errorpages/403.html;
	error_page 404 /errorpages/404.html;
	error_page 500 502 503 504 /errorpages/500.html;
	
	location /
	{
		try_files $uri $uri/ /index.php?$args;
	}
	
	# rewrite adminpanel to use https
	rewrite ^/adminpanel(.*)$ https://$host$uri permanent;
	
	# Add trailing slash to */wp-admin requests. Needed if wordpress is installed later
	rewrite /wp-admin$ $scheme://$host$uri/ permanent;
	
	# Directives to send expires headers
	location ~* \.(js|css|png|jpg|jpeg|gif|ico)$
	{
		expires 30d;
	}
	
	# Deny all attempts to access hidden files such as .htaccess, .htpasswd
	location ~ /\.
	{
		deny all;
		access_log off;
		log_not_found off;
	}
	
	locaton ~ \.php$
	{
		try_files $uri = 404;
		include /etc/nginx/fastcgi_params;
		fastcgi_pass 127.0.0.1:6000;
		fastcgi_index index.php;
		fastcgi_param SCRIPT_FILENAME /var/www/example.com/public_html$fastcgi_script_name;
	}
}
#=============== configs end

4.
ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

5.
service nginx restart



http://www.idolbin.com/blog/server-management/vps-setup-guide/enable-https-httpssl-in-nginx-web-server/
############################################################
1.
apt-get install openssl

2.
mkdir /usr/ssl/
cd /usr/ssl/

3. 
openssl genrsa -des3 -out server.key 1024
openssl req -new -key server.key -out server.csr
cp server.key server.key.protected
openssl rsa -in server.key.protected -out server.key
openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt

4.
append the block in the /etc/nginx/sites-available/default
#=============== Start
server
{
	listen 			443;
	ssl				on;
	ssl_certificate	/usr/ssl/server.crt;
	ssl_certificate_key	/usr/ssl/server.key;
	
	server_name		www.example.com;
	
	access_log		/var/www/example.com/logs/access.log;
	error_log		/var/www/example.com/logs/error.log;
	
	root			/var/www/example.com/public_html/;

	error_page  401  /errorpages/401.html;
    error_page  403  /errorpages/403.html;
    error_page  404  /errorpages/404.html;
    error_page  500 502 503 504  /errorpages/500.html;
	
	location /
    {
        try_files $uri $uri/ /index.php?$args;
    }
 
    # Add trailing slash to */wp-admin requests for wordpress
    rewrite /wp-admin$ $scheme://$host$uri/ permanent;
 
    # Directives to send expires headers
    location ~* \.(js|css|png|jpg|jpeg|gif|ico)$
    {
        expires 30d;
    }
 
    # Deny all attempts to access hidden files such as .htaccess, .htpasswd
    location ~ /\.
    {
        deny all;
        access_log off;
        log_not_found off;
    }
 
    location ~ \.php$
    {
        try_files $uri =404;
        include /etc/nginx/fastcgi_params;
        fastcgi_pass 127.0.0.1:6000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/example.com/public_html$fastcgi_script_name;
        fastcgi_param HTTPS on;
    }
}
#=============== End

5.
service nginx restart



http://www.idolbin.com/blog/server-management/vps-setup-guide/nginx-password-protect-web-directory/
############################################################
1.
apt-get install apache2-utils
htpasswd -b /usr/ssl/htpasswd NewUserName NewPassword

2.
add the configs in the https server block
#=============== Start
location ^~ /adminpanel/
{
    auth_basic            "Restricted";
    auth_basic_user_file  /usr/ssl/htpasswd;
 
    location ~ \.php$
    {
        try_files $uri =404;
        include /etc/nginx/fastcgi_params;
        fastcgi_pass 127.0.0.1:6000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/example.com/public_html$fastcgi_script_name;
        fastcgi_param HTTPS on;
    }
}
#=============== End

3.
service nginx restart

############################################################
############################################################
############################################################





http://www.idolbin.com/blog/server-management/vps-setup-guide/setup-php-fpm-with-apc-on-ubuntu-10-04-for-faster-performance/
############################################################
1.
sudo -s
add-apt-repository ppa:nginx/php5
apt-get update

2.
apt-get install php5 php5-dev php5-suhosin
apt-get install php-pear php5-cgi php5-cli php5-curl
apt-get install php5-gd php5-imagick php5-mcrypt
apt-get install php5-fpm php-apc php5-memcache php5-mysql

3.
vim /etc/php5/fpm/pool.d/www.conf
#=============== Sample configuration profile optimized for a server with Quad Core Processor and 512MB RAM:
[www]

listen = 127.0.0.1:6000
listen.allowed_clients = 127.0.0.1

user = www-data
group = www-data

pm = static
pm.max_children = 10
pm.max_requests = 100

request_terminate_timeout = 30
#=============== End Sample

4.
vim /etc/php5/fpm/php.ini
# nothing, but maybe put the "memory_limit" value to a higher value?

5.
extension=apc.so in the php.ini file, if you compiling from source

6.
vim /var/www/example.com/adminpanel/phpinfo.php

7.
download apc.php file: http://www.idolbin.com/downloads/apc.zip
upload to the adminpanel folder then can check apc status.
