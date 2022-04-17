# Fixtures

### Examples

Fixtures [**`UserRole`**](src/Entity/UserRole.php)

```yaml
#fixtures/user_role.yaml

App\Entity\UserRole:
  user_role_1:
    name: usuario
    role: ROLE_USER
  user_role_2:
    name: administrador
    role: ROLE_ADMIN
  user_role_3:
    name: "super administrador"
    role: ROLE_SUPER_ADMIN
  user_role_4:
    name: mantenedor
    role: ROLE_MANTAINER
```

Fixtures [**`User`**](src/Entity/User.php)

```yaml
#fixtures/user.yaml

App\Entity\User:
  user_{1..235}:
    firstName: <firstName()>
    lastName: '<lastName()> <lastName()>'
    username: testusername_<current()>
    email: <email()>
    emailVerify: true
    locale: 'es'
    password: \$2y\$04\$xO8IP5AM/uY/kY.ROF0MBueBQ4T3KgKszeS7cqIaibTlsA9OnQ7h6
    userRoles: ['@user_role_1']
  #USER TEST
  user_236:
    firstName: User
    lastName: Test Test
    username: test_user
    email: user@test.com
    emailVerify: true
    locale: 'es'
    password: \$2y\$04\$ru/Ssvb7gjTk.AU02obamu4OKzhJhEpsb/sgOaBvp58EjhwtCycea
    userRoles: ['@user_role_1']
  #ADMIN TEST
  user_237:
    firstName: Administrator
    lastName: Test Test
    username: test_admin
    email: admin@test.com
    emailVerify: true
    locale: 'es'
    password: \$2y\$04\$ru/Ssvb7gjTk.AU02obamu4OKzhJhEpsb/sgOaBvp58EjhwtCycea
    userRoles: ['@user_role_1', '@user_role_2']
  #SUPER ADMIN TEST
  user_238:
    firstName: 'Super Administrator'
    lastName: 'Test Test'
    username: 'test_superadmin'
    email: 'superadmin@test.com'
    emailVerify: true
    locale: 'es'
    password: \$2y\$04\$ru/Ssvb7gjTk.AU02obamu4OKzhJhEpsb/sgOaBvp58EjhwtCycea
    userRoles: ['@user_role_1', '@user_role_2', '@user_role_3']
  #MANTAINER TEST
  user_239:
    firstName: Mantainer
    lastName: Test Test
    username: test_mantainer
    email: mantainer@test.com
    emailVerify: true
    locale: 'es'
    password: \$2y\$04\$ru/Ssvb7gjTk.AU02obamu4OKzhJhEpsb/sgOaBvp58EjhwtCycea
    userRoles: ['@user_role_1', '@user_role_2', '@user_role_3', '@user_role_4']
```
Contraseñas
    
* **`1..235`** _c2km3qm42TaZqkh3JNxsNLVB8LZTPfBnBFaXb9PBXtR8FpdgkX_
* **`236..239`** _fgVrsby?2t8-6k8*8BjqWUbH^zGh+b2@unA3n#%W%J@2hV9hyv_


### Run Fixtures:
Ahora puede cargar sus fixtures en la base de datos con el siguiente comando:

    bin/console hautelook:fixtures:load