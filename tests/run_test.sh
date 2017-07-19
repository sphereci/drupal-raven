#!/bin/bash 
CONTAINER=`if [ -f /proc/1/cgroup ]; then echo 'docker'; else echo 'native'; fi`

case $CONTAINER in
docker)
  COMMAND_PREFIX=""
  ;;
*)
  COMMAND_PREFIX="docker-compose exec drupal"
  ;;
esac

#$COMMAND_PREFIX drush si --root=/var/www/drupal standard --db-url=mysql://root:root@172.16.238.20/database_name -l http://localhost:8080/ -y
#$COMMAND_PREFIX drush config-set --root=/var/www/drupal system.performance css.preprocess 0 -y
#$COMMAND_PREFIX drush config-set --root=/var/www/drupal system.performance js.preprocess 0 -y
#$COMMAND_PREFIX drush config-set --root=/var/www/drupal system.file file_public_path "/tmp" -y
#$COMMAND_PREFIX drush en --root=/var/www/drupal raven -y
$COMMAND_PREFIX ./vendor/bin/behat --config=behat.yml $@
