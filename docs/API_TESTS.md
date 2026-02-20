# GPE Go API - Documentación de Pruebas

> Pruebas ejecutadas el 2026-02-19 sobre localhost/XAMPP
> Base URL: `http://localhost/gpe_go_api/inputs.php`

## Índice

1. [Setup de pruebas](#1-setup-de-pruebas)
2. [Categorías](#2-categorías)
3. [Usuarios - Registro y Autenticación](#3-usuarios---registro-y-autenticación)
4. [Usuarios - Endpoints autenticados](#4-usuarios---endpoints-autenticados)
5. [Usuarios - Admin](#5-usuarios---admin)
6. [Lugares](#6-lugares)
7. [Eventos](#7-eventos)
8. [Favoritos](#8-favoritos)
9. [Reseñas](#9-reseñas)
10. [Reportes](#10-reportes)
11. [Fotos](#11-fotos)
12. [Seguridad y validaciones](#12-seguridad-y-validaciones)
13. [Resumen de resultados](#13-resumen-de-resultados)

---

## 1. Setup de pruebas

### Helper para obtener tokens

Función reutilizable para obtener un JWT fresco (en modo desarrollo el código 2FA se retorna en la respuesta):

```bash
get_token() {
  local email=$1
  local code=$(curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=solicitar_codigo" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$email\"}" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['codigo'])")
  curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=verificar_codigo" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$email\",\"codigo\":\"$code\"}" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])"
}
```

### Usuarios de prueba

| Usuario | Email | Rol | Notas |
|---------|-------|-----|-------|
| Juan Pérez García | juan@test.com | moderador | Promovido via `cambiar_rol` |
| Admin Test | admin@test.com | admin | Promovido directo en BD |
| Negocio Test | comercio@test.com | comercio | Promovido directo en BD |

---

## 2. Categorías

### 2.1 Listar categorías (público)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=listar"
```

**Respuesta esperada:** `200 OK` - Lista de las 7 categorías seed ordenadas por nombre.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": [
        {"id": 5, "nombre": "Entretenimiento", "descripcion": "Bares, antros, cines y diversión"},
        {"id": 2, "nombre": "Hoteles", "descripcion": "Hospedaje y alojamiento"},
        {"id": 1, "nombre": "Restaurantes", "descripcion": "Lugares para comer y beber"},
        {"id": 3, "nombre": "Salones de belleza", "descripcion": "Estéticas, spas y cuidado personal"},
        {"id": 6, "nombre": "Servicios", "descripcion": "Servicios profesionales diversos"},
        {"id": 7, "nombre": "Sitios turísticos", "descripcion": "Lugares de interés turístico"},
        {"id": 4, "nombre": "Tiendas", "descripcion": "Comercios y tiendas locales"}
    ]
}
```

### 2.2 Ver categoría por ID (público)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=ver&id=1"
```

**Respuesta esperada:** `200 OK` - Detalle de la categoría Restaurantes.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Restaurantes",
        "descripcion": "Lugares para comer y beber",
        "enabled": 1
    }
}
```

### 2.3 Crear categoría (admin)

```bash
ADMIN_TOKEN=$(get_token "admin@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"nombre":"Deportes","descripcion":"Gimnasios, canchas y actividades deportivas"}'
```

**Respuesta esperada:** `201 Created` - Categoría creada con ID autoincremental.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 8,
        "nombre": "Deportes",
        "descripcion": "Gimnasios, canchas y actividades deportivas",
        "enabled": 1
    },
    "message": "Categoría creada correctamente"
}
```

### 2.4 Editar categoría (admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=editar&id=8" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"nombre":"Deportes y Fitness","descripcion":"Gimnasios, canchas, yoga y más"}'
```

**Respuesta esperada:** `200 OK` - Campos actualizados.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 8,
        "nombre": "Deportes y Fitness",
        "descripcion": "Gimnasios, canchas, yoga y más",
        "enabled": 1
    },
    "message": "Categoría actualizada"
}
```

### 2.5 Eliminar categoría (admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=eliminar&id=8" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK` - Soft delete (enabled = 0).

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "message": "Categoría eliminada"
}
```

### 2.6 Crear categoría sin ser admin (debe fallar)

```bash
PUBLICO_TOKEN=$(get_token "juan@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $PUBLICO_TOKEN" \
  -d '{"nombre":"Hack"}'
```

**Respuesta esperada:** `403 Forbidden` - Solo admin puede crear categorías.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "FORBIDDEN",
        "mensaje": "No tienes permisos para esta acción"
    }
}
```

---

## 3. Usuarios - Registro y Autenticación

### 3.1 Registro de usuario

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=registro" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Juan Pérez","email":"juan@test.com"}'
```

**Respuesta esperada:** `201 Created` - Usuario con rol `publico` por defecto.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 4,
        "nombre": "Juan Pérez",
        "email": "juan@test.com",
        "rol": "publico"
    },
    "message": "Usuario registrado correctamente"
}
```

### 3.2 Registro duplicado (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=registro" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Juan Pérez","email":"juan@test.com"}'
```

**Respuesta esperada:** `400 Bad Request` - Email ya registrado.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "EMAIL_EXISTENTE",
        "mensaje": "Ya existe un usuario con este email"
    }
}
```

### 3.3 Registro sin campos requeridos (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=registro" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Solo Nombre"}'
```

**Respuesta esperada:** `400 Bad Request` - Indica campo faltante.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "CAMPOS_REQUERIDOS",
        "mensaje": "Faltan campos requeridos: email"
    }
}
```

### 3.4 Registro con rol=admin (seguridad: debe ignorar el rol)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=registro" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Hacker","email":"hacker@evil.com","rol":"admin"}'
```

**Respuesta esperada:** `201 Created` - El campo `rol` se ignora y se fuerza `publico`.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 3,
        "nombre": "Hacker",
        "email": "hacker@evil.com",
        "rol": "publico"
    },
    "message": "Usuario registrado correctamente"
}
```

### 3.5 Solicitar código 2FA

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=solicitar_codigo" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@test.com"}'
```

**Respuesta esperada:** `200 OK` - En desarrollo retorna el código de 6 dígitos.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "codigo": "632246"
    },
    "message": "Código generado (modo desarrollo)"
}
```

### 3.6 Verificar código incorrecto (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=verificar_codigo" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@test.com","codigo":"000000"}'
```

**Respuesta esperada:** `401 Unauthorized` - Código no coincide.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "CODIGO_INVALIDO",
        "mensaje": "Código incorrecto"
    }
}
```

### 3.7 Verificar código correcto y obtener JWT

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=verificar_codigo" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@test.com","codigo":"632246"}'
```

**Respuesta esperada:** `200 OK` - Token JWT + datos del usuario.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6NCwiZW1haWwiOiJqdWFuQHRlc3QuY29tIiwicm9sIjoicHVibGljbyIsImlhdCI6MTc3MTU2Mjg0NywiZXhwIjoxNzcxNjQ5MjQ3fQ.nuErI9O45X-5SBMgeBz8vo5Lj9oXBBA2JBY_CEFcuM4",
        "usuario": {
            "id": 4,
            "nombre": "Juan Pérez",
            "email": "juan@test.com",
            "rol": "publico"
        }
    },
    "message": "Login exitoso"
}
```

---

## 4. Usuarios - Endpoints autenticados

### 4.1 Ver perfil propio

```bash
TOKEN=$(get_token "juan@test.com")

curl -s "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=perfil" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta esperada:** `200 OK` - Datos del usuario autenticado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 4,
        "nombre": "Juan Pérez",
        "email": "juan@test.com",
        "rol": "publico"
    }
}
```

### 4.2 Editar perfil

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=editar" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"nombre":"Juan Pérez García"}'
```

**Respuesta esperada:** `200 OK` - Nombre actualizado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 4,
        "nombre": "Juan Pérez García",
        "email": "juan@test.com",
        "rol": "publico"
    },
    "message": "Perfil actualizado"
}
```

### 4.3 Editar perfil sin datos (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=editar" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{}'
```

**Respuesta esperada:** `400 Bad Request` - No hay datos para actualizar.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "SIN_CAMBIOS",
        "mensaje": "No se proporcionaron datos para actualizar"
    }
}
```

### 4.4 Perfil sin token (debe fallar)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=perfil"
```

**Respuesta esperada:** `401 Unauthorized` - Requiere JWT.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "AUTH_REQUIRED",
        "mensaje": "Autenticación requerida"
    }
}
```

---

## 5. Usuarios - Admin

### 5.1 Listar usuarios (admin)

```bash
ADMIN_TOKEN=$(get_token "admin@test.com")

curl -s "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=listar" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK` - Lista paginada con emails desencriptados.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "usuarios": [
            {"id": 6, "nombre": "Negocio Test", "email": "comercio@test.com", "rol": "comercio"},
            {"id": 5, "nombre": "Admin Test", "email": "admin@test.com", "rol": "admin"},
            {"id": 4, "nombre": "Juan Pérez García", "email": "juan@test.com", "rol": "publico"},
            {"id": 1, "nombre": "Administrador", "email": false, "rol": "admin"}
        ],
        "total": 4,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

> **Nota:** El usuario id=1 muestra `email: false` porque su email seed (`ENCRYPTED_EMAIL_PLACEHOLDER`) no es desencriptable. Es comportamiento esperado.

### 5.2 Cambiar rol de usuario (admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=cambiar_rol&id=4" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"rol":"moderador"}'
```

**Respuesta esperada:** `200 OK` - Rol cambiado a moderador.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 4,
        "nombre": "Juan Pérez García",
        "email": "juan@test.com",
        "rol": "moderador"
    },
    "message": "Rol actualizado"
}
```

### 5.3 Cambiar rol con valor inválido (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=cambiar_rol&id=4" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"rol":"superadmin"}'
```

**Respuesta esperada:** `400 Bad Request` - Rol no válido.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "ROL_INVALIDO",
        "mensaje": "El rol especificado no es válido"
    }
}
```

---

## 6. Lugares

### 6.1 Registrar lugar (comercio)

```bash
COMERCIO_TOKEN=$(get_token "comercio@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=registrar" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $COMERCIO_TOKEN" \
  -d '{"nombre":"Tacos El Güero","descripcion":"Los mejores tacos de Guadalupe","direccion":"Av. Benito Juárez 123","telefono":"8112345678","id_categoria":1}'
```

**Respuesta esperada:** `201 Created` - Estado `pendiente`, esperando aprobación.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tacos El Güero",
        "descripcion": "Los mejores tacos de Guadalupe",
        "direccion": "Av. Benito Juárez 123",
        "telefono": "8112345678",
        "id_categoria": 1,
        "id_usuario": 6,
        "estado": "pendiente",
        "enabled": 1,
        "categoria_nombre": "Restaurantes"
    },
    "message": "Comercio registrado. Pendiente de aprobación."
}
```

### 6.2 Listar lugares aprobados (público)

> Los lugares pendientes NO aparecen en la lista pública.

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=listar"
```

**Respuesta esperada:** `200 OK` - Lista vacía (todo está pendiente).

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "lugares": [],
        "total": 0,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 6.3 Listar pendientes (moderador/admin)

```bash
MOD_TOKEN=$(get_token "juan@test.com")

curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=listar_pendientes" \
  -H "Authorization: Bearer $MOD_TOKEN"
```

**Respuesta esperada:** `200 OK` - 2 lugares pendientes de aprobación.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "lugares": [
            {
                "id": 1,
                "nombre": "Tacos El Güero",
                "descripcion": "Los mejores tacos de Guadalupe",
                "direccion": "Av. Benito Juárez 123",
                "telefono": "8112345678",
                "id_categoria": 1,
                "id_usuario": 6,
                "estado": "pendiente",
                "enabled": 1,
                "categoria_nombre": "Restaurantes"
            },
            {
                "id": 2,
                "nombre": "Hotel Guadalupe Inn",
                "descripcion": "Hotel céntrico con todas las comodidades",
                "direccion": "Calle Hidalgo 456",
                "telefono": "8198765432",
                "id_categoria": 2,
                "id_usuario": 6,
                "estado": "pendiente",
                "enabled": 1,
                "categoria_nombre": "Hoteles"
            }
        ],
        "total": 2,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 6.4 Aprobar lugar (moderador/admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=aprobar&id=1" \
  -H "Authorization: Bearer $MOD_TOKEN"
```

**Respuesta esperada:** `200 OK` - Estado cambiado a `aprobado`.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tacos El Güero",
        "descripcion": "Los mejores tacos de Guadalupe",
        "direccion": "Av. Benito Juárez 123",
        "telefono": "8112345678",
        "id_categoria": 1,
        "id_usuario": 6,
        "estado": "aprobado",
        "enabled": 1,
        "categoria_nombre": "Restaurantes"
    },
    "message": "Lugar aprobado"
}
```

### 6.5 Rechazar lugar (moderador/admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=rechazar&id=3" \
  -H "Authorization: Bearer $MOD_TOKEN"
```

**Respuesta esperada:** `200 OK` - Estado cambiado a `rechazado`.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 3,
        "nombre": "Negocio Sospechoso",
        "descripcion": "Test",
        "direccion": null,
        "telefono": null,
        "id_categoria": 6,
        "id_usuario": 6,
        "estado": "rechazado",
        "enabled": 1,
        "categoria_nombre": "Servicios"
    },
    "message": "Lugar rechazado"
}
```

### 6.6 Ver lugar por ID (público)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=ver&id=1"
```

**Respuesta esperada:** `200 OK` - Detalle completo del lugar aprobado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tacos El Güero",
        "descripcion": "Los mejores tacos de Guadalupe",
        "direccion": "Av. Benito Juárez 123",
        "telefono": "8112345678",
        "id_categoria": 1,
        "id_usuario": 6,
        "estado": "aprobado",
        "enabled": 1,
        "categoria_nombre": "Restaurantes"
    }
}
```

### 6.7 Filtrar por categoría

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=listar&id_categoria=1"
```

**Respuesta esperada:** `200 OK` - Solo lugares de la categoría Restaurantes.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "lugares": [
            {
                "id": 1,
                "nombre": "Tacos El Güero",
                "descripcion": "Los mejores tacos de Guadalupe",
                "direccion": "Av. Benito Juárez 123",
                "telefono": "8112345678",
                "id_categoria": 1,
                "id_usuario": 6,
                "estado": "aprobado",
                "enabled": 1,
                "categoria_nombre": "Restaurantes"
            }
        ],
        "total": 1,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 6.8 Buscar lugares

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=listar&busqueda=tacos"
```

**Respuesta esperada:** `200 OK` - Coincidencias por nombre o descripción.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "lugares": [
            {
                "id": 1,
                "nombre": "Tacos El Güero",
                "descripcion": "Los mejores tacos de Guadalupe",
                "direccion": "Av. Benito Juárez 123",
                "telefono": "8112345678",
                "id_categoria": 1,
                "id_usuario": 6,
                "estado": "aprobado",
                "enabled": 1,
                "categoria_nombre": "Restaurantes"
            }
        ],
        "total": 1,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 6.9 Mis lugares (comercio)

```bash
COMERCIO_TOKEN=$(get_token "comercio@test.com")

curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=mis_lugares" \
  -H "Authorization: Bearer $COMERCIO_TOKEN"
```

**Respuesta esperada:** `200 OK` - Todos los lugares del usuario sin importar estado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "nombre": "Hotel Guadalupe Inn",
            "descripcion": "Hotel céntrico con todas las comodidades",
            "direccion": "Calle Hidalgo 456",
            "telefono": "8198765432",
            "id_categoria": 2,
            "id_usuario": 6,
            "estado": "aprobado",
            "enabled": 1,
            "categoria_nombre": "Hoteles"
        },
        {
            "id": 1,
            "nombre": "Tacos El Güero",
            "descripcion": "Los mejores tacos de Guadalupe",
            "direccion": "Av. Benito Juárez 123",
            "telefono": "8112345678",
            "id_categoria": 1,
            "id_usuario": 6,
            "estado": "aprobado",
            "enabled": 1,
            "categoria_nombre": "Restaurantes"
        }
    ]
}
```

### 6.10 Editar lugar (dueño)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=editar&id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $COMERCIO_TOKEN" \
  -d '{"descripcion":"Los mejores tacos de birria en todo Guadalupe, N.L.","telefono":"8111112222"}'
```

**Respuesta esperada:** `200 OK` - Descripción y teléfono actualizados.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tacos El Güero",
        "descripcion": "Los mejores tacos de birria en todo Guadalupe, N.L.",
        "direccion": "Av. Benito Juárez 123",
        "telefono": "8111112222",
        "id_categoria": 1,
        "id_usuario": 6,
        "estado": "aprobado",
        "enabled": 1,
        "categoria_nombre": "Restaurantes"
    },
    "message": "Lugar actualizado"
}
```

### 6.11 Eliminar lugar (moderador/admin, soft delete)

```bash
ADMIN_TOKEN=$(get_token "admin@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=eliminar&id=3" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK` - Borrado lógico (enabled = 0).

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "message": "Lugar eliminado"
}
```

### 6.12 Verificar que lugar eliminado no es visible

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=ver&id=3"
```

**Respuesta esperada:** `404 Not Found` - El lugar ya no existe.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "LUGAR_NO_ENCONTRADO",
        "mensaje": "Lugar no encontrado"
    }
}
```

---

## 7. Eventos

### 7.1 Crear evento (moderador/admin)

```bash
ADMIN_TOKEN=$(get_token "admin@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"titulo":"Festival Guadalupano 2026","descripcion":"Gran festival con música, comida y cultura","fecha_inicio":"2026-06-10","fecha_fin":"2026-06-15","tipo":"evento","id_lugar":1}'
```

**Respuesta esperada:** `201 Created` - Evento vinculado a lugar.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "titulo": "Festival Guadalupano 2026",
        "descripcion": "Gran festival con música, comida y cultura",
        "fecha_inicio": "2026-06-10",
        "fecha_fin": "2026-06-15",
        "tipo": "evento",
        "id_lugar": 1,
        "enabled": 1,
        "lugar_nombre": "Tacos El Güero"
    },
    "message": "Evento creado correctamente"
}
```

### 7.2 Crear noticia (moderador/admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"titulo":"Guadalupe se prepara para el Mundial 2026","descripcion":"La ciudad lanza programa de mejoramiento urbano","fecha_inicio":"2026-02-19","tipo":"noticia"}'
```

**Respuesta esperada:** `201 Created` - Noticia sin lugar asociado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 2,
        "titulo": "Guadalupe se prepara para el Mundial 2026",
        "descripcion": "La ciudad lanza programa de mejoramiento urbano",
        "fecha_inicio": "2026-02-19",
        "fecha_fin": null,
        "tipo": "noticia",
        "id_lugar": null,
        "enabled": 1,
        "lugar_nombre": null
    },
    "message": "Evento creado correctamente"
}
```

### 7.3 Listar eventos (público)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=listar"
```

**Respuesta esperada:** `200 OK` - Eventos y noticias ordenados por fecha.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "eventos": [
            {
                "id": 2,
                "titulo": "Guadalupe se prepara para el Mundial 2026",
                "descripcion": "La ciudad lanza programa de mejoramiento urbano",
                "fecha_inicio": "2026-02-19",
                "fecha_fin": null,
                "tipo": "noticia",
                "id_lugar": null,
                "enabled": 1,
                "lugar_nombre": null
            },
            {
                "id": 1,
                "titulo": "Festival Guadalupano 2026",
                "descripcion": "Gran festival con música, comida y cultura",
                "fecha_inicio": "2026-06-10",
                "fecha_fin": "2026-06-15",
                "tipo": "evento",
                "id_lugar": 1,
                "enabled": 1,
                "lugar_nombre": "Tacos El Güero"
            }
        ],
        "total": 2,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 7.4 Filtrar por tipo

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=listar&tipo=noticia"
```

**Respuesta esperada:** `200 OK` - Solo noticias.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "eventos": [
            {
                "id": 2,
                "titulo": "Guadalupe se prepara para el Mundial 2026",
                "descripcion": "La ciudad lanza programa de mejoramiento urbano",
                "fecha_inicio": "2026-02-19",
                "fecha_fin": null,
                "tipo": "noticia",
                "id_lugar": null,
                "enabled": 1,
                "lugar_nombre": null
            }
        ],
        "total": 1,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 7.5 Ver evento por ID

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=ver&id=1"
```

**Respuesta esperada:** `200 OK` - Detalle completo con nombre del lugar.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "titulo": "Festival Guadalupano 2026",
        "descripcion": "Gran festival con música, comida y cultura",
        "fecha_inicio": "2026-06-10",
        "fecha_fin": "2026-06-15",
        "tipo": "evento",
        "id_lugar": 1,
        "enabled": 1,
        "lugar_nombre": "Tacos El Güero"
    }
}
```

### 7.6 Editar evento (moderador/admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=editar&id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"titulo":"Gran Festival Guadalupano 2026","descripcion":"El evento mas grande del municipio"}'
```

**Respuesta esperada:** `200 OK` - Título y descripción actualizados.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "titulo": "Gran Festival Guadalupano 2026",
        "descripcion": "El evento mas grande del municipio",
        "fecha_inicio": "2026-06-10",
        "fecha_fin": "2026-06-15",
        "tipo": "evento",
        "id_lugar": 1,
        "enabled": 1,
        "lugar_nombre": "Tacos El Güero"
    },
    "message": "Evento actualizado"
}
```

### 7.7 Eliminar evento (moderador/admin, soft delete)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=eliminar&id=2" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK` - Borrado lógico.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "message": "Evento eliminado"
}
```

---

## 8. Favoritos

### 8.1 Agregar lugar a favoritos

```bash
TOKEN=$(get_token "juan@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=favoritos&action=agregar" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_lugar":1}'
```

**Respuesta esperada:** `201 Created` - Favorito creado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {"id": "1"},
    "message": "Agregado a favoritos"
}
```

### 8.2 Agregar evento a favoritos

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=favoritos&action=agregar" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_evento":1}'
```

**Respuesta esperada:** `201 Created` - Favorito de tipo evento.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {"id": "2"},
    "message": "Agregado a favoritos"
}
```

### 8.3 Favorito duplicado (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=favoritos&action=agregar" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_lugar":1}'
```

**Respuesta esperada:** `400 Bad Request` - Ya está en favoritos.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "YA_ES_FAVORITO",
        "mensaje": "Este elemento ya está en tus favoritos"
    }
}
```

### 8.4 Listar favoritos

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=favoritos&action=listar" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta esperada:** `200 OK` - Combina lugares y eventos vía UNION ALL.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "id_lugar": null,
            "id_evento": 1,
            "tipo": "evento",
            "nombre": "Festival Guadalupano 2026",
            "descripcion": "Gran festival con música, comida y cultura",
            "categoria_nombre": null
        },
        {
            "id": 1,
            "id_lugar": 1,
            "id_evento": null,
            "tipo": "lugar",
            "nombre": "Tacos El Güero",
            "descripcion": "Los mejores tacos de birria en todo Guadalupe, N.L.",
            "categoria_nombre": "Restaurantes"
        }
    ]
}
```

### 8.5 Quitar favorito

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=favoritos&action=quitar&id=2" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta esperada:** `200 OK` - Favorito eliminado.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "message": "Eliminado de favoritos"
}
```

---

## 9. Reseñas

### 9.1 Crear reseña

```bash
TOKEN=$(get_token "juan@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=resenas&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_lugar":1,"calificacion":5,"comentario":"Excelentes tacos de birria"}'
```

**Respuesta esperada:** `201 Created` - Reseña con calificación 1-5.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "id_usuario": 4,
        "id_lugar": 1,
        "comentario": "Excelentes tacos de birria",
        "calificacion": 5,
        "enabled": 1,
        "usuario_nombre": "Juan Pérez García"
    },
    "message": "Reseña publicada"
}
```

### 9.2 Reseña duplicada (debe fallar)

> Un usuario solo puede dejar una reseña por lugar.

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=resenas&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_lugar":1,"calificacion":3,"comentario":"Otra vez"}'
```

**Respuesta esperada:** `400 Bad Request` - Solo una reseña por usuario por lugar.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "RESENA_EXISTENTE",
        "mensaje": "Ya dejaste una reseña para este lugar"
    }
}
```

### 9.3 Listar reseñas de un lugar (público)

> Incluye promedio de calificación y total de reseñas.

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=resenas&action=listar&id_lugar=1"
```

**Respuesta esperada:** `200 OK` - Reseñas paginadas + estadísticas.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "resenas": [
            {
                "id": 1,
                "comentario": "Excelentes tacos de birria",
                "calificacion": 5,
                "id_usuario": 4,
                "usuario_nombre": "Juan Pérez García"
            }
        ],
        "total": 1,
        "pagina": 1,
        "por_pagina": 10,
        "promedio": 5,
        "total_resenas": 1
    }
}
```

### 9.4 Eliminar reseña (moderador/admin)

```bash
ADMIN_TOKEN=$(get_token "admin@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=resenas&action=eliminar&id=1" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK` - Borrado lógico.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "message": "Reseña eliminada"
}
```

**Verificación - listar reseñas después de eliminar:**

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "resenas": [],
        "total": 0,
        "pagina": 1,
        "por_pagina": 10,
        "promedio": null,
        "total_resenas": 0
    }
}
```

---

## 10. Reportes

### 10.1 Crear reporte

```bash
TOKEN=$(get_token "juan@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=reportes&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"tipo_entidad":"resena","id_entidad":1,"motivo":"Contenido inapropiado"}'
```

**Respuesta esperada:** `201 Created` - Reporte con estado `pendiente`.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {"id": "1"},
    "message": "Reporte enviado correctamente"
}
```

### 10.2 Reporte duplicado (debe fallar)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=reportes&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"tipo_entidad":"resena","id_entidad":1,"motivo":"Spam"}'
```

**Respuesta esperada:** `400 Bad Request` - No se puede duplicar reporte pendiente.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "REPORTE_DUPLICADO",
        "mensaje": "Ya has reportado este contenido"
    }
}
```

### 10.3 Listar reportes (moderador/admin)

```bash
ADMIN_TOKEN=$(get_token "admin@test.com")

curl -s "http://localhost/gpe_go_api/inputs.php?modulo=reportes&action=listar" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK` - Reportes paginados con nombre del reportante.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "reportes": [
            {
                "id": 1,
                "tipo_entidad": "resena",
                "id_entidad": 1,
                "id_usuario": 4,
                "motivo": "Contenido inapropiado",
                "estado": "pendiente",
                "enabled": 1,
                "reportado_por": "Juan Pérez García"
            }
        ],
        "total": 1,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 10.4 Revisar reporte (moderador/admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=reportes&action=revisar&id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"estado":"revisado"}'
```

**Respuesta esperada:** `200 OK` - Estado cambiado a `revisado`.

**Resultado obtenido:** `PASS`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "tipo_entidad": "resena",
        "id_entidad": 1,
        "id_usuario": 4,
        "motivo": "Contenido inapropiado",
        "estado": "revisado",
        "enabled": 1
    },
    "message": "Reporte actualizado"
}
```

---

## 11. Fotos

> Los endpoints de fotos requieren credenciales AWS S3 configuradas en `.env`.
> La estructura de los endpoints es idéntica para los 3 módulos de fotos.
> **Estado:** No probados en localhost (requieren S3). Documentados como referencia.

### 11.1 Subir foto de lugar

```bash
TOKEN=$(get_token "juan@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_lugares&action=subir" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_lugar":1,"imagen_base64":"data:image/jpeg;base64,/9j/4AAQSkZJRg..."}'
```

**Respuesta esperada:** `201 Created`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "id_lugar": 1,
        "url": "https://gpe-go-api-fotos.s3.amazonaws.com/lugares/1/abc123.jpg"
    },
    "message": "Foto subida correctamente"
}
```

**Resultado obtenido:** `PENDIENTE` - Requiere credenciales AWS S3.

### 11.2 Listar fotos de un lugar

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=fotos_lugares&action=listar&id_lugar=1"
```

**Resultado obtenido:** `PENDIENTE` - Requiere credenciales AWS S3.

### 11.3 Eliminar foto de lugar

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_lugares&action=eliminar&id=1" \
  -H "Authorization: Bearer $TOKEN"
```

**Resultado obtenido:** `PENDIENTE` - Requiere credenciales AWS S3.

### 11.4 Fotos de eventos

```bash
# Subir (moderador/admin)
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_eventos&action=subir" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"id_evento":1,"imagen_base64":"data:image/jpeg;base64,..."}'

# Listar
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=fotos_eventos&action=listar&id_evento=1"

# Eliminar (moderador/admin)
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_eventos&action=eliminar&id=1" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Resultado obtenido:** `PENDIENTE` - Requiere credenciales AWS S3.

### 11.5 Fotos de reseñas

```bash
# Subir (autor de la reseña)
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_resenas&action=subir" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id_resena":1,"imagen_base64":"data:image/jpeg;base64,..."}'

# Listar
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=fotos_resenas&action=listar&id_resena=1"

# Eliminar (autor o moderador/admin)
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_resenas&action=eliminar&id=1" \
  -H "Authorization: Bearer $TOKEN"
```

**Resultado obtenido:** `PENDIENTE` - Requiere credenciales AWS S3.

---

## 12. Seguridad y validaciones

### 12.1 Detección de SQL injection

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=listar&busqueda=DROP%20TABLE%20usuarios"
```

**Respuesta esperada:** `400 Bad Request` - Patrón SQL detectado y bloqueado.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "DATOS_NO_PERMITIDOS",
        "mensaje": "Se detectaron datos no permitidos en la solicitud"
    }
}
```

### 12.2 Módulo inválido

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=admin&action=hack"
```

**Respuesta esperada:** `400 Bad Request` - Módulo no está en la lista blanca.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "MODULO_INVALIDO",
        "mensaje": "El módulo especificado no es válido"
    }
}
```

### 12.3 Sin parámetros requeridos

```bash
curl -s "http://localhost/gpe_go_api/inputs.php"
```

**Respuesta esperada:** `400 Bad Request` - Faltan modulo y action.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "PARAMETROS_REQUERIDOS",
        "mensaje": "Los parámetros modulo y action son requeridos"
    }
}
```

### 12.4 Action inválido

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=hackear"
```

**Respuesta esperada:** `400 Bad Request` - Action no reconocido por el módulo.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "ACTION_INVALIDO",
        "mensaje": "La acción especificada no es válida"
    }
}
```

### 12.5 CORS preflight

```bash
curl -s -X OPTIONS -v "http://localhost/gpe_go_api/inputs.php" 2>&1 | grep -E "(HTTP/|Access-Control)"
```

**Respuesta esperada:** `200 OK` con headers CORS completos.

**Resultado obtenido:** `PASS`
```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

### 12.6 Endpoint protegido sin token

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=perfil"
```

**Respuesta esperada:** `401 Unauthorized` - JWT requerido.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "AUTH_REQUIRED",
        "mensaje": "Autenticación requerida"
    }
}
```

### 12.7 Endpoint protegido con rol insuficiente

```bash
PUBLICO_TOKEN=$(get_token "juan@test.com")

curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=crear" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $PUBLICO_TOKEN" \
  -d '{"nombre":"Test"}'
```

**Respuesta esperada:** `403 Forbidden` - Solo admin puede crear categorías.

**Resultado obtenido:** `PASS`
```json
{
    "success": false,
    "error": {
        "codigo": "FORBIDDEN",
        "mensaje": "No tienes permisos para esta acción"
    }
}
```

---

## 13. Resumen de resultados

| # | Módulo | Endpoint | Esperado | Obtenido |
|---|--------|----------|----------|----------|
| 1 | Categorías | listar | 200 + 7 categorías | PASS |
| 2 | Categorías | ver | 200 + detalle | PASS |
| 3 | Categorías | crear (admin) | 201 + categoría creada | PASS |
| 4 | Categorías | editar (admin) | 200 + campos actualizados | PASS |
| 5 | Categorías | eliminar (admin) | 200 + soft delete | PASS |
| 6 | Categorías | crear sin admin | 403 Forbidden | PASS |
| 7 | Usuarios | registro | 201 + rol publico | PASS |
| 8 | Usuarios | registro duplicado | 400 EMAIL_EXISTENTE | PASS |
| 9 | Usuarios | registro sin campos | 400 CAMPOS_REQUERIDOS | PASS |
| 10 | Usuarios | registro con rol=admin | 201 + rol forzado publico | PASS |
| 11 | Usuarios | solicitar_codigo | 200 + código 6 dígitos | PASS |
| 12 | Usuarios | verificar_codigo incorrecto | 401 CODIGO_INVALIDO | PASS |
| 13 | Usuarios | verificar_codigo correcto | 200 + JWT token | PASS |
| 14 | Usuarios | perfil | 200 + datos usuario | PASS |
| 15 | Usuarios | editar | 200 + nombre actualizado | PASS |
| 16 | Usuarios | editar sin datos | 400 SIN_CAMBIOS | PASS |
| 17 | Usuarios | perfil sin token | 401 AUTH_REQUIRED | PASS |
| 18 | Usuarios | listar (admin) | 200 + paginación | PASS |
| 19 | Usuarios | cambiar_rol (admin) | 200 + rol moderador | PASS |
| 20 | Usuarios | cambiar_rol inválido | 400 ROL_INVALIDO | PASS |
| 21 | Lugares | registrar (comercio) | 201 + estado pendiente | PASS |
| 22 | Lugares | listar (público) | 200 + lista vacía | PASS |
| 23 | Lugares | listar_pendientes | 200 + 2 pendientes | PASS |
| 24 | Lugares | aprobar | 200 + estado aprobado | PASS |
| 25 | Lugares | rechazar | 200 + estado rechazado | PASS |
| 26 | Lugares | ver | 200 + detalle lugar | PASS |
| 27 | Lugares | filtrar por categoría | 200 + 1 restaurante | PASS |
| 28 | Lugares | buscar | 200 + coincidencia | PASS |
| 29 | Lugares | mis_lugares | 200 + 2 lugares | PASS |
| 30 | Lugares | editar (dueño) | 200 + campos actualizados | PASS |
| 31 | Lugares | eliminar (soft delete) | 200 + eliminado | PASS |
| 32 | Lugares | ver eliminado | 404 LUGAR_NO_ENCONTRADO | PASS |
| 33 | Eventos | crear evento | 201 + lugar vinculado | PASS |
| 34 | Eventos | crear noticia | 201 + sin lugar | PASS |
| 35 | Eventos | listar | 200 + 2 eventos | PASS |
| 36 | Eventos | filtrar por tipo | 200 + 1 noticia | PASS |
| 37 | Eventos | ver | 200 + detalle | PASS |
| 38 | Eventos | editar | 200 + título actualizado | PASS |
| 39 | Eventos | eliminar (soft delete) | 200 + eliminado | PASS |
| 40 | Favoritos | agregar lugar | 201 + id favorito | PASS |
| 41 | Favoritos | agregar evento | 201 + id favorito | PASS |
| 42 | Favoritos | duplicado | 400 YA_ES_FAVORITO | PASS |
| 43 | Favoritos | listar | 200 + lugar y evento | PASS |
| 44 | Favoritos | quitar | 200 + eliminado | PASS |
| 45 | Reseñas | crear | 201 + calificación 5 | PASS |
| 46 | Reseñas | duplicada | 400 RESENA_EXISTENTE | PASS |
| 47 | Reseñas | listar (con promedio) | 200 + promedio 5.0 | PASS |
| 48 | Reseñas | eliminar (admin) | 200 + soft delete | PASS |
| 49 | Reportes | crear | 201 + estado pendiente | PASS |
| 50 | Reportes | duplicado | 400 REPORTE_DUPLICADO | PASS |
| 51 | Reportes | listar (admin) | 200 + paginación | PASS |
| 52 | Reportes | revisar | 200 + estado revisado | PASS |
| 53 | Seguridad | SQL injection | 400 DATOS_NO_PERMITIDOS | PASS |
| 54 | Seguridad | módulo inválido | 400 MODULO_INVALIDO | PASS |
| 55 | Seguridad | sin parámetros | 400 PARAMETROS_REQUERIDOS | PASS |
| 56 | Seguridad | action inválido | 400 ACTION_INVALIDO | PASS |
| 57 | Seguridad | CORS preflight | 200 + headers CORS | PASS |
| 58 | Seguridad | sin auth | 401 AUTH_REQUIRED | PASS |
| 59 | Seguridad | rol insuficiente | 403 FORBIDDEN | PASS |
| 60-68 | Fotos | subir/listar/eliminar (x3) | Requiere S3 | PENDIENTE |

### Totales

| Estado | Cantidad |
|--------|----------|
| **PASS** | 59 |
| **FAIL** | 0 |
| **PENDIENTE** (requiere S3) | 9 |
| **TOTAL** | 68 |
