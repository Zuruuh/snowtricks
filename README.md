# Snowtricks

Snowtricks is a website that brings together all snowboard enjoyers.

## Development environement

### Requirements

- PHP 8 >
- Composer
- Symfony CLI
- Docker
- Docker-compose
- NodeJS 14 >
- NPM
- Yarn

You can check if you meet the requirements with the following command:

```bash
symfony check:requirements
docker -v
docker-compose -v
node -v
npm -v
yarn -v
```

### Setup the developing environment

If you are on a Linux or MacOS system, you can use the Makefile to setup the project, just run the commands:  
`$ sudo apt install make` =====> To make sure you have the _make_ package installed  
`$ make install` ============> To actually setup the project
<br>
<br>
If your installation went correctly, you can directly skip to the next part.  
Else, you'll have to setup manually the project.

First you will need to install the project dependencies. Run the following commands to get started:

```bash
$ composer install
$ yarn install
```

Now, you'll have to setup the database. Run the following commands (make sure to run the docker-compose command first !) to get it running:

```bash
$ docker-compose up -d
$ php bin/console doctrine:database:create
$ php bin/console d:m:m
$ php bin/console d:f:l
```

### Starting the server

When this is done, you can start the local server using this command:

```bash
$ symfony server:start -d
```
