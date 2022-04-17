# Login Throttling (limitación de inicio de sesión)

Un ataque común de fuerza bruta contra las aplicaciones web consiste en que un atacante envíe un formulario de inicio de sesión muchas veces con la esperanza de adivinar la contraseña de alguna cuenta de usuario.

Una de las mejores contramedidas para estos ataques se llama 'limitación de inicio de sesión', que impide que un usuario intente iniciar sesión después de una cierta cantidad de intentos fallidos. Gracias al componente RateLimiter agregado en Symfony 5.2 proporcionará aceleración de inicio de sesión lista para usar.

Primero, asegúrese de estar utilizando la nueva seguridad basada en el autenticador. Luego, agregue la siguiente configuración a su firewall (`config/packages/security.yaml`):

```yaml
#config/packages/security.yaml

security:
    #...
    firewalls:
        main:
            #...
            login_throttling:
                max_attempts: 5
                interval: '15 minutes'
    #...
```

« [Login With OAuth](./LoginwithOAuth.md) • [Complete Reference](../Readme.md) »