# DDD Rules - PROJECT_X

**Project**: PROJECT_X
**Architecture**: Domain-Driven Design (DDD)
**Last Updated**: 2026-01-15
**Version**: 1.0

---

## 🎯 Propósito

Este archivo contiene las reglas específicas de **Domain-Driven Design (DDD)** que el backend debe seguir. Estas reglas son fundamentales para mantener la arquitectura limpia y escalable.

---

## 📐 Arquitectura DDD

### Estructura de Capas

```
backend/src/
├── Domain/              # Capa de Dominio (Núcleo del negocio)
│   ├── Entity/
│   ├── ValueObject/
│   ├── Repository/      # Interfaces (NO implementaciones)
│   ├── Service/         # Domain Services
│   └── Event/
│
├── Application/         # Capa de Aplicación (Casos de uso)
│   ├── UseCase/
│   ├── DTO/
│   ├── Service/
│   └── Query/
│
└── Infrastructure/      # Capa de Infraestructura (Detalles técnicos)
    ├── Persistence/
    │   └── Repository/  # Implementaciones de repositorios
    ├── HTTP/
    │   └── Controller/
    ├── Messaging/
    └── External/
```

---

## 🏛️ Domain Layer (Dominio)

### Entities (Entidades)

**Qué son**: Objetos con identidad única que persisten en el tiempo.

**Reglas**:
- ✅ **Tienen ID** único (UUID recomendado)
- ✅ **Lógica de negocio** dentro de la entidad
- ✅ **Validaciones** de consistencia interna
- ✅ **Inmutabilidad** donde sea posible
- ❌ **NO** tienen dependencias de infraestructura
- ❌ **NO** usan anotaciones de Doctrine (usar en Infrastructure)
- ❌ **NO** tienen setters públicos (usar métodos con intención)

**Ejemplo**:
```php
namespace App\Domain\User\Entity;

class User
{
    private UserId $id;
    private Email $email;
    private UserName $name;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        UserId $id,
        Email $email,
        UserName $name
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(Email $email, UserName $name): self
    {
        // Validaciones de negocio
        return new self(UserId::generate(), $email, $name);
    }

    public function changeName(UserName $newName): void
    {
        // Lógica de negocio para cambiar nombre
        $this->name = $newName;
    }

    // Getters
    public function id(): UserId { return $this->id; }
    public function email(): Email { return $this->email; }
    public function name(): UserName { return $this->name; }
}
```

### Value Objects

**Qué son**: Objetos sin identidad única, definidos por sus atributos.

**Reglas**:
- ✅ **Inmutables** (no cambian después de crearse)
- ✅ **Validación** en el constructor
- ✅ **Comparación por valor** (`equals()` method)
- ✅ **Pequeños y específicos** (Email, Money, Address, etc.)
- ❌ **NO** tienen ID
- ❌ **NO** cambian de estado (crear uno nuevo si necesitas cambio)

**Ejemplo**:
```php
namespace App\Domain\User\ValueObject;

class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    private function validate(string $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($value);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Repository Interfaces

**Qué son**: Contratos para persistencia, **definidos** en Domain, **implementados** en Infrastructure.

**Reglas**:
- ✅ **Interface** en `Domain/Repository/`
- ✅ **Implementación** en `Infrastructure/Persistence/Repository/`
- ✅ **Métodos específicos** de negocio (`findByEmail`, no solo CRUD)
- ✅ **Return types**: Entidades del dominio
- ❌ **NO** exponen detalles de persistencia (Doctrine, SQL, etc.)

**Ejemplo**:
```php
namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function existsWithEmail(Email $email): bool;
    public function remove(User $user): void;
}
```

### Domain Services

**Qué son**: Lógica de negocio que no pertenece a una entidad específica.

**Cuándo usar**:
- Operaciones que involucran múltiples entidades
- Lógica de negocio sin estado
- Coordinación entre entidades

**Reglas**:
- ✅ **Stateless** (sin estado)
- ✅ **Lógica de dominio pura**
- ❌ **NO** acceden directamente a repositorios (eso es de Application)

**Ejemplo**:
```php
namespace App\Domain\User\Service;

class UserValidator
{
    public function isEmailAvailable(Email $email, UserRepositoryInterface $repository): bool
    {
        return !$repository->existsWithEmail($email);
    }
}
```

### Domain Events

**Qué son**: Eventos que representan algo que pasó en el dominio.

**Reglas**:
- ✅ **Inmutables**
- ✅ **Nombre en pasado** (`UserCreated`, `EmailChanged`)
- ✅ **Contienen datos** del evento
- ❌ **NO** tienen lógica de negocio

**Ejemplo**:
```php
namespace App\Domain\User\Event;

class UserCreated
{
    private UserId $userId;
    private Email $email;
    private \DateTimeImmutable $occurredOn;

    public function __construct(UserId $userId, Email $email)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function userId(): UserId { return $this->userId; }
    public function email(): Email { return $this->email; }
    public function occurredOn(): \DateTimeImmutable { return $this->occurredOn; }
}
```

---

## 🎯 Application Layer (Aplicación)

### Use Cases

**Qué son**: Casos de uso específicos de la aplicación (acciones que el usuario puede hacer).

**Reglas**:
- ✅ **Un caso de uso** = una acción de usuario
- ✅ **Orquestan** el dominio (usan repositories, entities, services)
- ✅ **Transaccionales** (todo o nada)
- ✅ **Reciben DTOs** como input
- ✅ **Retornan DTOs** como output (o void)
- ❌ **NO** tienen lógica de negocio (eso es del Domain)
- ❌ **NO** tienen lógica de presentación (eso es del Infrastructure)

**Ejemplo**:
```php
namespace App\Application\User\UseCase;

use App\Application\User\DTO\CreateUserRequest;
use App\Application\User\DTO\CreateUserResponse;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\UserName;

class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(CreateUserRequest $request): CreateUserResponse
    {
        $email = new Email($request->email);

        // Validar email único
        if ($this->userRepository->existsWithEmail($email)) {
            throw new EmailAlreadyExistsException($email);
        }

        $user = User::create(
            $email,
            new UserName($request->name)
        );

        $this->userRepository->save($user);

        return new CreateUserResponse(
            $user->id()->value(),
            $user->email()->value(),
            $user->name()->value()
        );
    }
}
```

### DTOs (Data Transfer Objects)

**Qué son**: Objetos simples para transferir datos entre capas.

**Reglas**:
- ✅ **Propiedades públicas** (o getters simples)
- ✅ **Sin lógica** de negocio
- ✅ **Validación básica** (tipos, requeridos)
- ✅ **Serializables** (pueden convertirse a JSON)
- ❌ **NO** son entidades del dominio

**Ejemplo**:
```php
namespace App\Application\User\DTO;

class CreateUserRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $name
    ) {}
}

class CreateUserResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name
    ) {}
}
```

---

## 🔧 Infrastructure Layer (Infraestructura)

### Repository Implementations

**Qué son**: Implementaciones concretas de los interfaces de repositorio definidos en Domain.

**Reglas**:
- ✅ **Implementan** interface de `Domain/Repository/`
- ✅ **Usan Doctrine** (o cualquier ORM)
- ✅ **Mapean** entre entidades de Domain y entidades de Doctrine
- ✅ **Manejan persistencia** (save, find, remove)
- ❌ **NO** exponen Doctrine fuera de Infrastructure

**Ejemplo**:
```php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email.value' => $email->value()]);
    }

    public function existsWithEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function remove(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
```

### Controllers

**Qué son**: Puntos de entrada HTTP (API endpoints).

**Reglas**:
- ✅ **Delgados** (thin controllers)
- ✅ **Llaman a Use Cases**
- ✅ **Convierten** Request HTTP → DTO → Use Case → Response HTTP
- ✅ **Manejan errores** HTTP (400, 404, 500)
- ❌ **NO** tienen lógica de negocio
- ❌ **NO** acceden directamente a repositorios

**Ejemplo**:
```php
namespace App\Infrastructure\HTTP\Controller;

use App\Application\User\UseCase\CreateUserUseCase;
use App\Application\User\DTO\CreateUserRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreateUserController
{
    public function __construct(
        private CreateUserUseCase $createUserUseCase
    ) {}

    #[Route('/api/users', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateUserRequest(
            email: $data['email'] ?? '',
            name: $data['name'] ?? ''
        );

        try {
            $response = $this->createUserUseCase->execute($dto);

            return new JsonResponse([
                'id' => $response->id,
                'email' => $response->email,
                'name' => $response->name,
            ], Response::HTTP_CREATED);

        } catch (EmailAlreadyExistsException $e) {
            return new JsonResponse([
                'error' => 'Email already exists',
                'email' => $data['email'],
            ], Response::HTTP_CONFLICT);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
```

---

## ✅ Checklist DDD

Antes de implementar, verifica:

- [ ] **Domain Layer**:
  - [ ] Entities tienen ID único
  - [ ] Value Objects son inmutables
  - [ ] Repository interfaces en Domain (NO implementaciones)
  - [ ] No hay dependencias de infraestructura

- [ ] **Application Layer**:
  - [ ] Use Cases orquestan el dominio
  - [ ] Use Cases son transaccionales
  - [ ] DTOs para input/output
  - [ ] No hay lógica de negocio en Use Cases

- [ ] **Infrastructure Layer**:
  - [ ] Repository implementations en Infrastructure
  - [ ] Controllers delgados (thin)
  - [ ] Controllers llaman a Use Cases
  - [ ] No hay lógica de negocio en Controllers

---

## 🚫 Anti-patterns (Evitar)

### ❌ Anemic Domain Model
```php
// MAL: Entidad sin comportamiento
class User {
    public $id;
    public $email;
    public $name;
}

// BIEN: Entidad con comportamiento
class User {
    private UserId $id;
    private Email $email;

    public function changeName(UserName $newName): void {
        // Lógica de validación y cambio
    }
}
```

### ❌ Fat Controllers
```php
// MAL: Controller con lógica de negocio
public function createUser(Request $request): Response {
    $email = $request->get('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
    if ($this->userRepository->findByEmail($email)) { ... }
    $user = new User();
    $user->setEmail($email);
    // ...
}

// BIEN: Controller delgado
public function createUser(Request $request): Response {
    $dto = new CreateUserRequest(...);
    $response = $this->createUserUseCase->execute($dto);
    return new JsonResponse($response);
}
```

### ❌ Repositories en Domain que usan Doctrine
```php
// MAL: Repository en Domain con Doctrine
namespace App\Domain\User\Repository;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository { ... }

// BIEN: Interface en Domain, implementación en Infrastructure
namespace App\Domain\User\Repository;
interface UserRepositoryInterface { ... }

namespace App\Infrastructure\Persistence\Repository;
class DoctrineUserRepository implements UserRepositoryInterface { ... }
```

---

## 📚 Recursos

- [Domain-Driven Design by Eric Evans](https://www.amazon.com/Domain-Driven-Design-Tackling-Complexity-Software/dp/0321125215)
- [Implementing Domain-Driven Design by Vaughn Vernon](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
- [DDD in PHP](https://github.com/dddinphp)

---

**Recuerda**: DDD no es solo una estructura de carpetas. Es una **forma de pensar** sobre el dominio del negocio. El código debe reflejar el lenguaje y las reglas del negocio, no los detalles técnicos.

**Última actualización**: 2026-01-15
**Actualizado por**: Planner
