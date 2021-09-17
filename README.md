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

You can check if you meet the requirements (except for Docker & Docker-compose) with the following command:

```bash
symfony check:requirements
docker -v
docker-compose -v
node -v
npm -v
yarn -v
```

### Start the developing environment

First you will need to install the project dependencies. Run the following commands to get started:

```bash
composer install
yarn install
```

Now, you'll have to setup the database. Run the following commands (make sure to run the docker-compose command first !) to get it running:

```bash
docker-compose up -d
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Once this is done, you can start the local server using this command:

```bash
symfony server:start -d
```
