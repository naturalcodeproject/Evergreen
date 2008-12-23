#<?php die();?>

main-settings:
   uri_map:
         controller: main
         view: index
         action:
         id:
   errors:
       path: public/errors
       403: 403.php
       404: 404.php

database-settings:
   host: localhost
   username: root
   password: root
   database: hooktest
   database-type: MySQL

routes:
    - uri: /test/*
      uri_map:
          controller: testing
          view: look_here
    - uri: /oranges/*
      uri_map:
          branch: developer
          view: oranges
    - uri: /pickles/*
      uri_map:
          branch: developer
          view: pickles