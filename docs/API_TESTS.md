# GPE Go API - Documentación de Pruebas

> Pruebas realizadas el 2026-02-19 sobre localhost/XAMPP
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `403 Forbidden`
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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `201 Created` (rol forzado a `publico`)
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

**Respuesta esperada:** `200 OK` (en desarrollo retorna el código)
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

**Respuesta esperada:** `401 Unauthorized`
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `401 Unauthorized`
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "usuarios": [
            {"id": 6, "nombre": "Negocio Test", "email": "comercio@test.com", "rol": "comercio"},
            {"id": 5, "nombre": "Admin Test", "email": "admin@test.com", "rol": "admin"},
            {"id": 4, "nombre": "Juan Pérez García", "email": "juan@test.com", "rol": "publico"}
        ],
        "total": 4,
        "pagina": 1,
        "por_pagina": 10
    }
}
```

### 5.2 Cambiar rol de usuario (admin)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=usuarios&action=cambiar_rol&id=4" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"rol":"moderador"}'
```

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `201 Created` (estado: pendiente)
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

**Respuesta esperada (sin lugares aprobados):** `200 OK`
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "lugares": [
            {
                "id": 1,
                "nombre": "Tacos El Güero",
                "estado": "pendiente",
                "categoria_nombre": "Restaurantes"
            },
            {
                "id": 2,
                "nombre": "Hotel Guadalupe Inn",
                "estado": "pendiente",
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tacos El Güero",
        "estado": "aprobado",
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 3,
        "nombre": "Negocio Sospechoso",
        "estado": "rechazado"
    },
    "message": "Lugar rechazado"
}
```

### 6.6 Ver lugar por ID (público)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=ver&id=1"
```

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK` (solo restaurantes)
```json
{
    "success": true,
    "data": {
        "lugares": [
            {"id": 1, "nombre": "Tacos El Güero", "categoria_nombre": "Restaurantes"}
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

**Respuesta esperada:** `200 OK` (coincidencias)
```json
{
    "success": true,
    "data": {
        "lugares": [
            {"id": 1, "nombre": "Tacos El Güero"}
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

**Respuesta esperada:** `200 OK` (todos los lugares del usuario, sin importar estado)
```json
{
    "success": true,
    "data": [
        {"id": 2, "nombre": "Hotel Guadalupe Inn", "estado": "aprobado"},
        {"id": 1, "nombre": "Tacos El Güero", "estado": "aprobado"}
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tacos El Güero",
        "descripcion": "Los mejores tacos de birria en todo Guadalupe, N.L.",
        "telefono": "8111112222"
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "message": "Lugar eliminado"
}
```

**Verificar que ya no es visible:**
```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=lugares&action=ver&id=3"
```

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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `201 Created`
```json
{
    "success": true,
    "data": {
        "id": 2,
        "titulo": "Guadalupe se prepara para el Mundial 2026",
        "fecha_inicio": "2026-02-19",
        "fecha_fin": null,
        "tipo": "noticia",
        "id_lugar": null,
        "lugar_nombre": null
    },
    "message": "Evento creado correctamente"
}
```

### 7.3 Listar eventos (público)

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=listar"
```

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "eventos": [
            {"id": 2, "titulo": "Guadalupe se prepara para el Mundial 2026", "tipo": "noticia"},
            {"id": 1, "titulo": "Festival Guadalupano 2026", "tipo": "evento"}
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

**Respuesta esperada:** `200 OK` (solo noticias)
```json
{
    "success": true,
    "data": {
        "eventos": [
            {"id": 2, "titulo": "Guadalupe se prepara para el Mundial 2026", "tipo": "noticia"}
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "titulo": "Gran Festival Guadalupano 2026",
        "descripcion": "El evento mas grande del municipio"
    },
    "message": "Evento actualizado"
}
```

### 7.7 Eliminar evento (moderador/admin, soft delete)

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=eventos&action=eliminar&id=2" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `200 OK` (combina lugares y eventos)
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "message": "Reseña eliminada"
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

**Respuesta esperada:** `201 Created`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `200 OK`
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

**Respuesta esperada:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "tipo_entidad": "resena",
        "id_entidad": 1,
        "motivo": "Contenido inapropiado",
        "estado": "revisado"
    },
    "message": "Reporte actualizado"
}
```

---

## 11. Fotos

> Los endpoints de fotos requieren credenciales AWS S3 configuradas en `.env`.
> La estructura de los endpoints es idéntica para los 3 módulos de fotos.

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

### 11.2 Listar fotos de un lugar

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=fotos_lugares&action=listar&id_lugar=1"
```

### 11.3 Eliminar foto de lugar

```bash
curl -s -X POST "http://localhost/gpe_go_api/inputs.php?modulo=fotos_lugares&action=eliminar&id=1" \
  -H "Authorization: Bearer $TOKEN"
```

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

---

## 12. Seguridad y validaciones

### 12.1 Detección de SQL injection

```bash
curl -s "http://localhost/gpe_go_api/inputs.php?modulo=categorias&action=listar&busqueda=DROP%20TABLE%20usuarios"
```

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `400 Bad Request`
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

**Respuesta esperada:** `200 OK` con headers CORS
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

**Respuesta esperada:** `401 Unauthorized`
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

**Respuesta esperada:** `403 Forbidden`
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

| # | Módulo | Endpoint | Resultado |
|---|--------|----------|-----------|
| 1 | Categorías | listar | PASS |
| 2 | Categorías | ver | PASS |
| 3 | Categorías | crear (admin) | PASS |
| 4 | Categorías | editar (admin) | PASS |
| 5 | Categorías | eliminar (admin) | PASS |
| 6 | Categorías | crear sin admin | PASS (403) |
| 7 | Usuarios | registro | PASS |
| 8 | Usuarios | registro duplicado | PASS (error) |
| 9 | Usuarios | registro sin campos | PASS (error) |
| 10 | Usuarios | registro con rol=admin | PASS (forzado publico) |
| 11 | Usuarios | solicitar_codigo | PASS |
| 12 | Usuarios | verificar_codigo incorrecto | PASS (error) |
| 13 | Usuarios | verificar_codigo correcto | PASS + JWT |
| 14 | Usuarios | perfil | PASS |
| 15 | Usuarios | editar | PASS |
| 16 | Usuarios | editar sin datos | PASS (error) |
| 17 | Usuarios | perfil sin token | PASS (401) |
| 18 | Usuarios | listar (admin) | PASS + paginación |
| 19 | Usuarios | cambiar_rol (admin) | PASS |
| 20 | Usuarios | cambiar_rol inválido | PASS (error) |
| 21 | Lugares | registrar (comercio) | PASS (pendiente) |
| 22 | Lugares | listar (público, sin pendientes) | PASS |
| 23 | Lugares | listar_pendientes (moderador) | PASS |
| 24 | Lugares | aprobar (moderador) | PASS |
| 25 | Lugares | rechazar (admin) | PASS |
| 26 | Lugares | ver | PASS |
| 27 | Lugares | filtrar por categoría | PASS |
| 28 | Lugares | buscar | PASS |
| 29 | Lugares | mis_lugares | PASS |
| 30 | Lugares | editar (dueño) | PASS |
| 31 | Lugares | eliminar (soft delete) | PASS |
| 32 | Lugares | ver eliminado | PASS (404) |
| 33 | Eventos | crear evento | PASS |
| 34 | Eventos | crear noticia | PASS |
| 35 | Eventos | listar | PASS |
| 36 | Eventos | filtrar por tipo | PASS |
| 37 | Eventos | ver | PASS |
| 38 | Eventos | editar | PASS |
| 39 | Eventos | eliminar (soft delete) | PASS |
| 40 | Favoritos | agregar lugar | PASS |
| 41 | Favoritos | agregar evento | PASS |
| 42 | Favoritos | duplicado | PASS (error) |
| 43 | Favoritos | listar | PASS |
| 44 | Favoritos | quitar | PASS |
| 45 | Reseñas | crear | PASS |
| 46 | Reseñas | duplicada | PASS (error) |
| 47 | Reseñas | listar (con promedio) | PASS |
| 48 | Reseñas | eliminar (admin) | PASS |
| 49 | Reportes | crear | PASS |
| 50 | Reportes | duplicado | PASS (error) |
| 51 | Reportes | listar (admin) | PASS |
| 52 | Reportes | revisar | PASS |
| 53 | Seguridad | SQL injection | PASS (bloqueado) |
| 54 | Seguridad | módulo inválido | PASS (error) |
| 55 | Seguridad | sin parámetros | PASS (error) |
| 56 | Seguridad | action inválido | PASS (error) |
| 57 | Seguridad | CORS preflight | PASS (headers) |
| 58 | Seguridad | sin auth | PASS (401) |
| 59 | Seguridad | rol insuficiente | PASS (403) |

**Total: 59 pruebas | 59 PASS | 0 FAIL**

> **Nota:** Los endpoints de fotos (módulos `fotos_lugares`, `fotos_eventos`, `fotos_resenas`) requieren credenciales AWS S3 configuradas y no fueron probados en localhost. Su estructura y permisos están documentados en la sección 11.
