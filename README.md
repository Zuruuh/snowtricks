# Snowtricks 

Snowtricks is a website that brings together all snowboard enjoyers.

## Development environement

### Requirements
* PHP 8 >
* Composer
* Symfony CLI
* Docker
* Docker-compose

You can check if you meet the requirements (except for Docker & Docker-compose) with the following command:

```bash
symfony check:requirements
```

### Start the developing environment

```bash
docker-compose up -d
symfony serve -d
```