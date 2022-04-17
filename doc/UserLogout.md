# User Logout

### Paso 1: Crear ruta

Crearemos una ruta en nuestro `config/routes.yaml`

```diff
#config/routes.yaml

+api_auth_logout:
+   path: /api/auth/logout
```

### Paso 2: Configurar security.yaml

Configuraremos nuestro `config/packages/security.yaml` para que la lógica de logout apunte a nuestra ruta:

```diff
#config/packages/security.yaml

security:
    # ...

    firewalls:
        main:
            # ...
+           logout:
+               path: api_auth_logout
```

### Paso 3: Crear LogoutEventListener

Creamos un EventListener que escuchará el evento `Symfony\Component\Security\Http\Event\LogoutEvent`:

```php
<?php
#src/EventListener/Auth/LogoutEventListener.php

namespace App\EventListener\Auth;

use App\Event\AuthEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LogoutEventListener
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $event)
    {
        $response = new JsonResponse(
            [
                'code' => 200,
                'message' => 'The supplied tokens has been invalidated.',
            ],
            JsonResponse::HTTP_OK
        );
        $response->headers->clearCookie('jwt_hp', '/', null);
        $response->headers->clearCookie('jwt_s', '/', null);
        $response->headers->clearCookie('refreshToken', '/', null);

        $this->eventDispatcher->dispatch(
            new AuthEvent($event),
            AuthEvent::LOGOUT_SUCCESS
        );

        $event->setResponse($response);
    }
}

```

### Paso 4: Añadir LogoutEventListener al services.yaml

Añadimos a nuestro `config/services.yaml` nuestro `src/EventListener/Auth/LogoutEventListener.php`:

```diff
#config/services.yaml

parameters:
    # ...

services:
    # ...

+   App\EventListener\Auth\LogoutEventListener:
+       tags:
+           - name: 'kernel.event_listener'
+               event: 'Symfony\Component\Security\Http\Event\LogoutEvent'
+               dispatcher: security.event_dispatcher.main

    # ...
```

« [JWT Refresh](./JWTRefresh.md) • [Login with OAuth](./LoginwithOAuth.md) »