# Dev environment 

This docker setup is supposed to get a running system for local development.

## Run locally

* Compose Docker containers:
  ~~~
  cd docker_app 
  docker-compose up -d
  ~~~
  (Linux users may need `sudo` or `docker login`)

* Open [localhost:8080](http://localhost:8080) in your browser \
  Logins are defined at first use. (There may be example data in the future)

## Additonal information

### Inspect database
Open [localhost:8888](http://localhost:8888) in your browser for phpmyadmin \
Login: `mrbs:mrbs`

Alternatively, you can connect to the database using the command like tool `mysql`:
~~~
docker-compose db mysql -u mrbs -pmrbs mrbs
~~~

### View logs

View apache webserver logs:
~~~
docker-compose logs www
~~~
View database logs:
~~~
docker-compose logs db
~~~


### Live reloading
The repository's source code is mounted into the docker containers. That means, changes of code take effect immediately after refreshing the browser.

However, when configuration of php and database is change, you have to reset the containers.

### Reset containers

* Stop Docker containers:
  ~~~
  docker-compose down
  ~~~
* Delete persited volumes:
  ~~~
  docker volume prune
  ~~~
* Rebuild container images:
  ~~~
  docker-compose build
  ~~~

