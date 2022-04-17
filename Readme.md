<p align="center">
    <img src="doc/img/api_platform.svg" width=300 />
</p>

<h1 align=center>API Platform - Boilerplate</h1>

[![Build Status](https://github.com/api-platform/api-platform/workflows/CI/badge.svg?branch=master)](https://github.com/capiloky/Api-Platform-Boilerplate/actions)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)

**Boilerplate preparado para trabajar en Symfony 6.**
## Tabla de contenidos

* [Instalación](#instalación)
* Seguridad
  * [Creación de entidades](doc/CreateEntities.md)
    * [Entidad User](doc/CreateEntities.md#user-entity)
    * [Entidad UserRoles](doc/CreateEntities.md#user-roles)
    * [Entidad UserImage](doc/CreateEntities.md#user-image)
  * [Hash Password](doc/HashPassword.md)
  * [User Email Verify](doc/UserEmailVerify.md)
  * [User Password Reset](doc/UserPasswordReset.md)
  * [JWT Login](doc/JWTLogin.md)
    * [Configurar JWT Token](doc/JWTLogin.md#configurar-jwt-token)
    * [Añadir "recuérdame"](doc/JWTLogin.md#añadir-remember-me)
  * [JWT Refresh](doc/JWTRefresh.md)
  * [User Logout](doc/UserLogout.md)
  * [Login with OAuth](doc/LoginwithOAuth.md)
    * [Google Client](doc/LoginwithOAuth.md#google-client)
    * More soon...
  * [Login Throttling (limitación de inicio de sesión)](doc/LoginThrottling.md)
* [OPEN API Decorators]()
* [Log]()
  * [Auth Event Log]()
  * [Write API Event Log]()
* [Fixtures](doc/Fixtures.md)
  * [Examples](doc/Fixtures.md#examples)
  * [Run Fixtures](doc/Fixtures.md#run-fixtures)
* [Test](doc/Test.md)
  * [Test HttpClient](doc/Test.md#test-httpclient)


## Instalación

Clonar repositorio:

```console
git clone https://github.com/capiloky/Api-Platform-Boilerplate
cd Api-Platform-Boilerplate
composer install
```

Hacer migración :

```console
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Start dev server

```console
php -S localhost:8000 -t public/
```
