clear
echo 'Enter new site domain (Must be unique):'
read domainName
echo 'Enter WordPress MySQL DB name:'
read dbName
echo 'Enter WordPress MySQL Username:'
read dbUser

apacheHome="/home/www/"
logDir="/var/log/apache/"
newPath="$apacheHome$domainName"
newLogPath="$logDir$domainName"
configPath="$newPath/public_html/wp-config.php"

if [ -z "$domainName" ] || [ -z "$dbName" ] || [ -z "$dbUser" ]; then
        echo "Domain, DB name and username are all mandatory.";
        exit;
fi


if [ -d "$newPath" ] && [ -d "$newLogPath" ]; then
        echo "Domain $domainName is already setup.";
else
        sudo mkdir /home/www/$domainName
        sudo mkdir /var/log/apache/$domainName
        sudo cp -R /home/ashwin/clean/ /home/www/$domainName/
        sudo cp -R /home/ashwin/currentwordpress/ /home/www/$domainName/public_html/
        sudo chown -R www:www /home/www/$domainName/
        sudo chmod -R 555 /home/www/$domainName/
        sudo chmod -R 755 /home/www/$domainName/public_html/wp-content/
        mysql --host=### --user="ashwin" --password="###" --execute="CREATE DATABASE $dbName;"
        mysql --host=### --user="ashwin" --password="###" --execute="grant usage on *.* to $dbUser@app3 identified by 'key1Gfs!';"
        mysql --host=###--user="ashwin" --password="###" --execute="grant all privileges on $dbName.* to $dbUser@app3;"
        mysql --host=### --user="ashwin" --password="###" --execute="grant usage on *.* to $dbUser@app4 identified by 'key1Gfs!';"
        mysql --host=### --user="ashwin" --password="###" --execute="grant all privileges on $dbName.* to $dbUser@app4;"
        sudo perl -pi -e "s/database_name_here/$dbName/g" "$configPath"
        sudo perl -pi -e "s/username_here/$dbUser/g" "$configPath"
        sudo perl -pi -e 's/password_here/###/g' "$configPath"
        sudo perl -pi -e 's/localhost/wpdb/g' "$configPath"
fi


vhostContents=$(</home/www/VHosts/httpd-vhosts.conf)
vhostsFullPath="/home/www/VHosts/httpd-vhosts.conf"


if [[ $vhostContents != *"$domainName"* ]]; then
        sudo cat <<EOT >> $vhostsFullPath

<VirtualHost 127.0.0.1:8080>
                DocumentRoot "/home/www/$domainName/public_html"
                ServerName $domainName
                ServerAlias www.$domainName
                <Directory "/home/www/$domainName/public_html">
                                AllowOverride All
                                Order allow,deny
                                Allow from all
                                Options FollowSymLinks
                </Directory>
                TransferLog "/var/log/apache/$domainName/httpd-access.log"
                ErrorLog "/var/log/apache/$domainName/httpd-error.log"
</VirtualHost>
EOT
fi
