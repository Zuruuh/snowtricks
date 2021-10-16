# Snowtricks

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/6e9d7551cdfa43d5ae30808cc5978d4a)](https://www.codacy.com/gl/Zuruh/snowtricks/dashboard)

Snowtricks is a website that brings together all snowboard enjoyers.

## Environement

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

Once this is done, update the DSN env variables in the .env file.  
Then, rename the .env file to be .env.local

### Setup the developing environment

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

## Setup the production environment

Pull the repo on your production server, and make sure it meets all the requirements.  
Then, run the following commands to install the dependencies.

```bash
$ composer install --no-dev
$ yarn install
```

You'll now need to setup the app's secret. Simply update the .env file to match your settings.

Then, simply build all the assets by running this command:

```bash
$ yarn build
```

Once this is done, setup the database by running the following commands:

```bash
$ php bin/console doctrine:database:create
$ php bin/console d:m:m
$ php bin/console d:f:l
```

Now your server should be ready ! You'll just have to start it.

## Starting the server

When this is done, you can start the local server using this command:

```bash
$ symfony server:start -d
```
