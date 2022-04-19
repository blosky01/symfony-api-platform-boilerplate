# JWT Refresh

### Paso 1: Descargar el Bundle

Añadimos el bundle [JWTRefreshTokenBundle](https://github.com/markitosgv/JWTRefreshTokenBundle)

```console
composer require gesdinet/jwt-refresh-token-bundle:dev-master
```
El propósito de este paquete es administrar tokens de actualización con JWT (Json Web Tokens) de una manera fácil. Este paquete usa LexikJWTAuthenticationBundle. Compatible con Doctrine ORM/ODM.


### Paso 2: Configurar el Bundle

Creamos el archivo `config/packages/gesdinet_jwt_refresh_token.yaml` con el siguiente contenido:

```yaml
#config/packages/gesdinet_jwt_refresh_token.yaml

gesdinet_jwt_refresh_token:
  ttl: 3600
  user_identity_field: email
  ttl_update: true
  token_parameter_name: refreshToken
  user_provider: security.user.provider.concrete.app_user_provider
  single_use: true

  cookie:
    enabled: true
    same_site: lax
    path: /
    domain: null
    http_only: true
    secure: true
    remove_token_from_body: true
```

### Paso 3: Creación entidad RefreshToken

Creamos nuestra clase `src/Entity/RefreshToken.php`:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Table(name: '`refresh_tokens`')]
#[ORM\Entity()]
class RefreshToken extends BaseRefreshToken
{
}

```

### Paso 4: Config route

Crearemos una ruta en nuestro `routes.yaml`

```diff
#config/routes.yaml

+api_auth_refresh_token:
+   path: /api/auth/refresh_token
```

### Paso 5: Config security

Configuraremos nuestro `security.yaml` para que la lógica de logout apunte a nuestra ruta:

```diff
#config/packages/security.yaml

security:
    # ...

    firewalls:
        main:
            # ...
+           refresh_jwt:
+               check_path: /api/auth/refresh_token
```

### Paso 6: Actualizar esquema de su base de datos

```console
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

« [JWT Login](./JWTLogin.md) • [User Logout](./UserLogout.md) »
