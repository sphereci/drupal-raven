#FROM drupal:8.2-apache
FROM spheresh/drupal-raven-behat:latest
RUN apt-get update -y
RUN apt-get install wget -y
RUN apt-get install mysql-client -y
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/local/bin  --filename=composer && \
    php -r "unlink('composer-setup.php');"
ENV PATH="/root/.composer/vendor/bin:${PATH}"
RUN composer global require hirak/prestissimo
RUN composer global require drush/drush:^8.0
RUN sed -i -e "s?/var/www/html?/var/www/drupal?g" /etc/apache2/sites-available/000-default.conf
RUN drush --root="/var/www/drupal" dl drupal --drupal-project-rename="drupal" --destination="/var/www" -y
