# GPE Go API - Documentacion Tecnica

**Version:** 1.0
**Base URL:** `http://{host}/gpe_go_api/inputs.php`
**Formato:** Todos los endpoints reciben y responden en JSON
**Autenticacion:** JWT via header `Authorization: Bearer {token}`

---

## Formato de Respuestas

### Respuesta exitosa

```json
{
  "success": true,
  "data": { ... },
  "message": "Operacion realizada correctamente"
}
```

### Respuesta de error

```json
{
  "success": false,
  "error": {
    "codigo": "CODIGO_ERROR",
    "mensaje": "Descripcion del error"
  }
}
```

### Codigos HTTP

| Codigo | Descripcion |
|--------|-------------|
| 200 | Operacion exitosa |
| 201 | Recurso creado |
| 400 | Error de validacion / datos invalidos |
| 401 | No autenticado / token invalido |
| 403 | Sin permisos |
| 404 | Recurso no encontrado |
| 500 | Error interno del servidor |

---

## Roles de Usuario

| Rol | Descripcion |
|-----|-------------|
| `publico` | Usuario basico. Lectura + favoritos, resenas, ratings |
| `comercio` | Puede registrar su comercio (requiere aprobacion) |
| `moderador` | Gestiona lugares, eventos, resenas |
| `admin` | Control total del sistema |

---

## 1. Usuarios

### 1.1 Registro de usuario

```
POST ?modulo=usuarios&action=registro
```

**Acceso:** Publico

**Body:**
```json
{
  "nombre": "Juan Perez",
  "email": "juan@ejemplo.com"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan Perez",
    "email": "juan@ejemplo.com",
    "rol": "publico"
  },
  "message": "Usuario registrado correctamente"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `CAMPOS_REQUERIDOS` | Faltan campos requeridos: nombre, email |
| `EMAIL_INVALIDO` | El formato del email no es valido |
| `EMAIL_EXISTENTE` | Ya existe un usuario con este email |

---

### 1.2 Solicitar codigo 2FA

```
POST ?modulo=usuarios&action=solicitar_codigo
```

**Acceso:** Publico

**Body:**
```json
{
  "email": "juan@ejemplo.com"
}
```

**Respuesta exitosa (200) - Modo desarrollo:**
```json
{
  "success": true,
  "data": {
    "codigo": "482910"
  },
  "message": "Codigo generado (modo desarrollo)"
}
```

**Respuesta exitosa (200) - Produccion:**
```json
{
  "success": true,
  "message": "Codigo enviado a tu email"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `CAMPOS_REQUERIDOS` | Faltan campos requeridos: email |
| `EMAIL_INVALIDO` | El formato del email no es valido |
| `USUARIO_NO_ENCONTRADO` | No existe un usuario con este email |

---

### 1.3 Verificar codigo y obtener JWT

```
POST ?modulo=usuarios&action=verificar_codigo
```

**Acceso:** Publico

**Body:**
```json
{
  "email": "juan@ejemplo.com",
  "codigo": "482910"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "usuario": {
      "id": 1,
      "nombre": "Juan Perez",
      "email": "juan@ejemplo.com",
      "rol": "publico"
    }
  },
  "message": "Login exitoso"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `CAMPOS_REQUERIDOS` | Faltan campos requeridos: email, codigo |
| `USUARIO_NO_ENCONTRADO` | No existe un usuario con este email |
| `CODIGO_INVALIDO` | Codigo incorrecto / El codigo ha expirado |

---

### 1.4 Ver perfil propio

```
GET ?modulo=usuarios&action=perfil
```

**Acceso:** Autenticado (cualquier rol)

**Headers:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan Perez",
    "email": "juan@ejemplo.com",
    "rol": "publico"
  }
}
```

---

### 1.5 Editar perfil propio

```
PUT ?modulo=usuarios&action=editar
```

**Acceso:** Autenticado (cualquier rol)

**Headers:**
```
Authorization: Bearer {token}
```

**Body:**
```json
{
  "nombre": "Juan Alberto Perez"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan Alberto Perez",
    "email": "juan@ejemplo.com",
    "rol": "publico"
  },
  "message": "Perfil actualizado"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `SIN_CAMBIOS` | No se proporcionaron datos para actualizar |

---

### 1.6 Listar usuarios

```
GET ?modulo=usuarios&action=listar&pagina=1&por_pagina=10
```

**Acceso:** Admin

**Parametros opcionales en URL:**
| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| pagina | int | 1 | Numero de pagina |
| por_pagina | int | 10 | Resultados por pagina |

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "usuarios": [
      {
        "id": 1,
        "nombre": "Juan Perez",
        "email": "juan@ejemplo.com",
        "rol": "publico"
      }
    ],
    "total": 25,
    "pagina": 1,
    "por_pagina": 10
  }
}
```

---

### 1.7 Cambiar rol de usuario

```
PUT ?modulo=usuarios&action=cambiar_rol&id=2
```

**Acceso:** Admin

**Body:**
```json
{
  "rol": "comercio"
}
```

**Roles validos:** `publico`, `comercio`, `moderador`, `admin`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Maria Lopez",
    "email": "maria@ejemplo.com",
    "rol": "comercio"
  },
  "message": "Rol actualizado"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `ID_REQUERIDO` | Se requiere el ID del usuario |
| `CAMPOS_REQUERIDOS` | Faltan campos requeridos: rol |
| `ROL_INVALIDO` | El rol especificado no es valido |
| `USUARIO_NO_ENCONTRADO` | Usuario no encontrado |

---

## 2. Categorias

### 2.1 Listar categorias

```
GET ?modulo=categorias&action=listar
```

**Acceso:** Publico

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Restaurantes",
      "descripcion": "Lugares para comer y beber"
    },
    {
      "id": 2,
      "nombre": "Hoteles",
      "descripcion": "Hospedaje y alojamiento"
    }
  ]
}
```

---

### 2.2 Ver categoria

```
GET ?modulo=categorias&action=ver&id=1
```

**Acceso:** Publico

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Restaurantes",
    "descripcion": "Lugares para comer y beber"
  }
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `ID_REQUERIDO` | Se requiere el ID de la categoria |
| `CATEGORIA_NO_ENCONTRADA` | Categoria no encontrada |

---

### 2.3 Crear categoria

```
POST ?modulo=categorias&action=crear
```

**Acceso:** Admin

**Body:**
```json
{
  "nombre": "Deportes",
  "descripcion": "Gimnasios, canchas y centros deportivos"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 8,
    "nombre": "Deportes",
    "descripcion": "Gimnasios, canchas y centros deportivos"
  },
  "message": "Categoria creada correctamente"
}
```

---

### 2.4 Editar categoria

```
PUT ?modulo=categorias&action=editar&id=8
```

**Acceso:** Admin

**Body:**
```json
{
  "nombre": "Deportes y Recreacion",
  "descripcion": "Gimnasios, canchas, parques y centros deportivos"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 8,
    "nombre": "Deportes y Recreacion",
    "descripcion": "Gimnasios, canchas, parques y centros deportivos"
  },
  "message": "Categoria actualizada"
}
```

---

### 2.5 Eliminar categoria

```
DELETE ?modulo=categorias&action=eliminar&id=8
```

**Acceso:** Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Categoria eliminada"
}
```

---

## 3. Lugares

### 3.1 Listar lugares aprobados

```
GET ?modulo=lugares&action=listar&pagina=1&por_pagina=10
```

**Acceso:** Publico

**Parametros opcionales en URL:**
| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| pagina | int | 1 | Numero de pagina |
| por_pagina | int | 10 | Resultados por pagina |
| id_categoria | int | null | Filtrar por categoria |
| busqueda | string | null | Buscar por nombre/descripcion |

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "lugares": [
      {
        "id": 1,
        "nombre": "Taqueria El Rey",
        "descripcion": "Los mejores tacos de Guadalupe",
        "direccion": "Av. Benito Juarez 123",
        "telefono": "8112345678",
        "id_categoria": 1,
        "categoria_nombre": "Restaurantes",
        "id_usuario": 2,
        "estado": "aprobado"
      }
    ],
    "total": 15,
    "pagina": 1,
    "por_pagina": 10
  }
}
```

---

### 3.2 Listar lugares pendientes

```
GET ?modulo=lugares&action=listar_pendientes&pagina=1&por_pagina=10
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "lugares": [ ... ],
    "total": 3,
    "pagina": 1,
    "por_pagina": 10
  }
}
```

---

### 3.3 Ver detalle de lugar

```
GET ?modulo=lugares&action=ver&id=1
```

**Acceso:** Publico (solo aprobados). Dueno/Moderador/Admin pueden ver cualquier estado.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Taqueria El Rey",
    "descripcion": "Los mejores tacos de Guadalupe",
    "direccion": "Av. Benito Juarez 123",
    "telefono": "8112345678",
    "id_categoria": 1,
    "id_usuario": 2,
    "estado": "aprobado"
  }
}
```

---

### 3.4 Mis lugares

```
GET ?modulo=lugares&action=mis_lugares
```

**Acceso:** Autenticado

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Taqueria El Rey",
      "estado": "aprobado",
      ...
    }
  ]
}
```

---

### 3.5 Registrar comercio

```
POST ?modulo=lugares&action=registrar
```

**Acceso:** Comercio, Moderador, Admin

**Body:**
```json
{
  "nombre": "Taqueria El Rey",
  "descripcion": "Los mejores tacos de Guadalupe",
  "direccion": "Av. Benito Juarez 123",
  "telefono": "8112345678",
  "id_categoria": 1
}
```

**Campos requeridos:** `nombre`, `id_categoria`
**Campos opcionales:** `descripcion`, `direccion`, `telefono`

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Taqueria El Rey",
    "descripcion": "Los mejores tacos de Guadalupe",
    "direccion": "Av. Benito Juarez 123",
    "telefono": "8112345678",
    "id_categoria": 1,
    "id_usuario": 2,
    "estado": "pendiente"
  },
  "message": "Comercio registrado. Pendiente de aprobacion."
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `CAMPOS_REQUERIDOS` | Faltan campos requeridos: nombre, id_categoria |
| `CATEGORIA_NO_ENCONTRADA` | La categoria especificada no existe |

---

### 3.6 Editar lugar

```
PUT ?modulo=lugares&action=editar&id=1
```

**Acceso:** Dueno del lugar, Moderador, Admin

**Body (todos opcionales):**
```json
{
  "nombre": "Taqueria El Rey - Sucursal Centro",
  "descripcion": "Nueva descripcion",
  "direccion": "Nueva direccion",
  "telefono": "8198765432",
  "id_categoria": 2
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Lugar actualizado"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `FORBIDDEN` | No tienes permisos para editar este lugar |
| `CATEGORIA_NO_ENCONTRADA` | La categoria especificada no existe |

---

### 3.7 Aprobar lugar

```
PUT ?modulo=lugares&action=aprobar&id=1
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Taqueria El Rey",
    "estado": "aprobado",
    ...
  },
  "message": "Lugar aprobado"
}
```

---

### 3.8 Rechazar lugar

```
PUT ?modulo=lugares&action=rechazar&id=1
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "estado": "rechazado",
    ...
  },
  "message": "Lugar rechazado"
}
```

---

### 3.9 Eliminar lugar

```
DELETE ?modulo=lugares&action=eliminar&id=1
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Lugar eliminado"
}
```

---

## 4. Eventos

### 4.1 Listar eventos

```
GET ?modulo=eventos&action=listar&pagina=1&por_pagina=10
```

**Acceso:** Publico

**Parametros opcionales en URL:**
| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| pagina | int | 1 | Numero de pagina |
| por_pagina | int | 10 | Resultados por pagina |
| tipo | string | null | Filtrar: `evento` o `noticia` |
| id_lugar | int | null | Filtrar por lugar |
| busqueda | string | null | Buscar por titulo/descripcion |
| incluir_pasados | flag | false | Incluir eventos con fecha pasada |

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "eventos": [
      {
        "id": 1,
        "titulo": "Inauguracion Plaza Central",
        "descripcion": "Gran evento de inauguracion",
        "fecha_inicio": "2026-06-01",
        "fecha_fin": "2026-06-03",
        "tipo": "evento",
        "id_lugar": 1
      }
    ],
    "total": 5,
    "pagina": 1,
    "por_pagina": 10
  }
}
```

---

### 4.2 Ver detalle de evento

```
GET ?modulo=eventos&action=ver&id=1
```

**Acceso:** Publico

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "titulo": "Inauguracion Plaza Central",
    "descripcion": "Gran evento de inauguracion",
    "fecha_inicio": "2026-06-01",
    "fecha_fin": "2026-06-03",
    "tipo": "evento",
    "id_lugar": 1
  }
}
```

---

### 4.3 Crear evento

```
POST ?modulo=eventos&action=crear
```

**Acceso:** Moderador, Admin

**Body:**
```json
{
  "titulo": "Inauguracion Plaza Central",
  "descripcion": "Gran evento de inauguracion",
  "fecha_inicio": "2026-06-01",
  "fecha_fin": "2026-06-03",
  "tipo": "evento",
  "id_lugar": 1
}
```

**Campos requeridos:** `titulo`, `fecha_inicio`
**Campos opcionales:** `descripcion`, `fecha_fin`, `tipo` (default: `evento`), `id_lugar`

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Evento creado correctamente"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `TIPO_INVALIDO` | El tipo debe ser "evento" o "noticia" |
| `LUGAR_NO_ENCONTRADO` | El lugar especificado no existe |
| `FECHAS_INVALIDAS` | La fecha de fin no puede ser anterior a la fecha de inicio |

---

### 4.4 Editar evento

```
PUT ?modulo=eventos&action=editar&id=1
```

**Acceso:** Moderador, Admin

**Body (todos opcionales):**
```json
{
  "titulo": "Titulo actualizado",
  "descripcion": "Descripcion actualizada",
  "fecha_inicio": "2026-06-02",
  "fecha_fin": "2026-06-04",
  "tipo": "noticia",
  "id_lugar": 2
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Evento actualizado"
}
```

---

### 4.5 Eliminar evento

```
DELETE ?modulo=eventos&action=eliminar&id=1
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Evento eliminado"
}
```

---

## 5. Favoritos

### 5.1 Listar mis favoritos

```
GET ?modulo=favoritos&action=listar
```

**Acceso:** Autenticado

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "id_usuario": 1,
      "id_lugar": 3,
      "id_evento": null,
      "nombre_lugar": "Taqueria El Rey"
    },
    {
      "id": 2,
      "id_usuario": 1,
      "id_lugar": null,
      "id_evento": 1,
      "nombre_evento": "Inauguracion Plaza Central"
    }
  ]
}
```

---

### 5.2 Agregar a favoritos

```
POST ?modulo=favoritos&action=agregar
```

**Acceso:** Autenticado

**Body (uno u otro, no ambos):**
```json
{
  "id_lugar": 3
}
```
o
```json
{
  "id_evento": 1
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1
  },
  "message": "Agregado a favoritos"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `PARAMETROS_INVALIDOS` | Debe proporcionar id_lugar o id_evento, pero no ambos |
| `LUGAR_NO_ENCONTRADO` | El lugar especificado no existe o no esta disponible |
| `EVENTO_NO_ENCONTRADO` | El evento especificado no existe |

---

### 5.3 Quitar de favoritos

```
DELETE ?modulo=favoritos&action=quitar&id=1
```

**Acceso:** Autenticado (solo sus propios favoritos)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Eliminado de favoritos"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `FAVORITO_NO_ENCONTRADO` | Favorito no encontrado |
| `FORBIDDEN` | No puedes eliminar favoritos de otros usuarios |

---

## 6. Resenas

### 6.1 Listar resenas de un lugar

```
GET ?modulo=resenas&action=listar&id_lugar=1&pagina=1&por_pagina=10
```

**Acceso:** Publico

**Parametros en URL:**
| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| id_lugar | int | Si | ID del lugar |
| pagina | int | No (default 1) | Numero de pagina |
| por_pagina | int | No (default 10) | Resultados por pagina |

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "resenas": [
      {
        "id": 1,
        "id_usuario": 3,
        "id_lugar": 1,
        "comentario": "Excelentes tacos, muy recomendados",
        "calificacion": 5,
        "usuario_nombre": "Carlos Garcia"
      }
    ],
    "total": 12,
    "pagina": 1,
    "por_pagina": 10,
    "promedio": 4.3,
    "total_resenas": 12
  }
}
```

---

### 6.2 Crear resena

```
POST ?modulo=resenas&action=crear
```

**Acceso:** Autenticado

**Body:**
```json
{
  "id_lugar": 1,
  "comentario": "Excelentes tacos, muy recomendados",
  "calificacion": 5
}
```

**Campos requeridos:** `id_lugar`, `calificacion` (1-5)
**Campos opcionales:** `comentario`

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "id_usuario": 3,
    "id_lugar": 1,
    "comentario": "Excelentes tacos, muy recomendados",
    "calificacion": 5
  },
  "message": "Resena publicada"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `CALIFICACION_INVALIDA` | La calificacion debe ser entre 1 y 5 |
| `LUGAR_NO_ENCONTRADO` | El lugar especificado no existe o no esta disponible |

---

### 6.3 Eliminar resena

```
DELETE ?modulo=resenas&action=eliminar&id=1
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Resena eliminada"
}
```

---

## 7. Fotos de Lugares

### 7.1 Listar fotos de un lugar

```
GET ?modulo=fotos_lugares&action=listar&id_lugar=1
```

**Acceso:** Publico

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://gpego.s3.us-east-1.amazonaws.com/lugares/abc123_1709856000.jpg",
      "orden": 0,
      "id_usuario": 2,
      "usuario_nombre": "Maria Lopez"
    },
    {
      "id": 2,
      "url": "https://gpego.s3.us-east-1.amazonaws.com/lugares/def456_1709856100.jpg",
      "orden": 1,
      "id_usuario": 3,
      "usuario_nombre": "Carlos Garcia"
    }
  ]
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `ID_LUGAR_REQUERIDO` | Se requiere el ID del lugar |
| `LUGAR_NO_ENCONTRADO` | Lugar no encontrado |

---

### 7.2 Subir foto de lugar

```
POST ?modulo=fotos_lugares&action=subir
```

**Acceso:** Autenticado (cualquier usuario puede subir fotos a lugares aprobados)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "id_lugar": 1,
  "imagen": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "orden": 0
}
```

**Campos requeridos:** `id_lugar`, `imagen` (base64)
**Campos opcionales:** `orden` (default: 0)

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "url": "https://gpego.s3.us-east-1.amazonaws.com/lugares/xyz789_1709856200.jpg"
  },
  "message": "Foto subida correctamente"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `CAMPOS_REQUERIDOS` | Faltan campos requeridos: id_lugar, imagen |
| `LUGAR_NO_ENCONTRADO` | Lugar no encontrado o no aprobado |
| `IMAGEN_INVALIDA` | La imagen proporcionada no es valida |
| `S3_ERROR` | Error al subir la imagen |

---

### 7.3 Eliminar foto de lugar

```
DELETE ?modulo=fotos_lugares&action=eliminar&id=3
```

**Acceso:** Dueno de la foto, Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Foto eliminada"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `FOTO_NO_ENCONTRADA` | Foto no encontrada |
| `FORBIDDEN` | No tienes permisos para eliminar esta foto |

---

## 8. Fotos de Eventos

### 8.1 Listar fotos de un evento

```
GET ?modulo=fotos_eventos&action=listar&id_evento=1
```

**Acceso:** Publico

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://gpego.s3.us-east-1.amazonaws.com/eventos/abc123_1709856000.jpg",
      "orden": 0
    }
  ]
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `ID_EVENTO_REQUERIDO` | Se requiere el ID del evento |
| `EVENTO_NO_ENCONTRADO` | Evento no encontrado |

---

### 8.2 Subir foto de evento

```
POST ?modulo=fotos_eventos&action=subir
```

**Acceso:** Moderador, Admin

**Body:**
```json
{
  "id_evento": 1,
  "imagen": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "orden": 0
}
```

**Campos requeridos:** `id_evento`, `imagen` (base64)
**Campos opcionales:** `orden` (default: 0)

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "url": "https://gpego.s3.us-east-1.amazonaws.com/eventos/xyz789_1709856200.jpg"
  },
  "message": "Foto subida correctamente"
}
```

---

### 8.3 Eliminar foto de evento

```
DELETE ?modulo=fotos_eventos&action=eliminar&id=2
```

**Acceso:** Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Foto eliminada"
}
```

---

## 9. Fotos de Resenas

### 9.1 Listar fotos de una resena

```
GET ?modulo=fotos_resenas&action=listar&id_resena=1
```

**Acceso:** Publico

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://gpego.s3.us-east-1.amazonaws.com/resenas/abc123_1709856000.jpg",
      "orden": 0
    }
  ]
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `ID_RESENA_REQUERIDO` | Se requiere el ID de la resena |
| `RESENA_NO_ENCONTRADA` | Resena no encontrada |

---

### 9.2 Subir foto de resena

```
POST ?modulo=fotos_resenas&action=subir
```

**Acceso:** Autor de la resena unicamente

**Body:**
```json
{
  "id_resena": 1,
  "imagen": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "orden": 0
}
```

**Campos requeridos:** `id_resena`, `imagen` (base64)
**Campos opcionales:** `orden` (default: 0)

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://gpego.s3.us-east-1.amazonaws.com/resenas/xyz789_1709856200.jpg"
  },
  "message": "Foto subida correctamente"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `RESENA_NO_ENCONTRADA` | Resena no encontrada |
| `FORBIDDEN` | Solo puedes subir fotos a tus propias resenas |

---

### 9.3 Eliminar foto de resena

```
DELETE ?modulo=fotos_resenas&action=eliminar&id=1
```

**Acceso:** Autor de la resena, Moderador, Admin

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Foto eliminada"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `FOTO_NO_ENCONTRADA` | Foto no encontrada |
| `FORBIDDEN` | No tienes permisos para eliminar esta foto |

---

## 10. Reportes

### 10.1 Crear reporte

```
POST ?modulo=reportes&action=crear
```

**Acceso:** Autenticado

**Body:**
```json
{
  "tipo_entidad": "foto_lugar",
  "id_entidad": 5,
  "motivo": "Contenido inapropiado"
}
```

**Campos requeridos:** `tipo_entidad`, `id_entidad`, `motivo`

**Tipos de entidad validos:** `foto_lugar`, `foto_evento`, `foto_resena`, `resena`

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1
  },
  "message": "Reporte enviado correctamente"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `TIPO_INVALIDO` | El tipo de entidad no es valido |
| `REPORTE_DUPLICADO` | Ya has reportado este contenido |

---

### 10.2 Listar reportes

```
GET ?modulo=reportes&action=listar&estado=pendiente&pagina=1&por_pagina=10
```

**Acceso:** Moderador, Admin

**Parametros opcionales en URL:**
| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| estado | string | pendiente | Filtrar: `pendiente`, `revisado`, `descartado` |
| pagina | int | 1 | Numero de pagina |
| por_pagina | int | 10 | Resultados por pagina |

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "reportes": [
      {
        "id": 1,
        "tipo_entidad": "foto_lugar",
        "id_entidad": 5,
        "id_usuario": 3,
        "motivo": "Contenido inapropiado",
        "estado": "pendiente"
      }
    ],
    "total": 3,
    "pagina": 1,
    "por_pagina": 10
  }
}
```

---

### 10.3 Revisar reporte

```
PUT ?modulo=reportes&action=revisar&id=1
```

**Acceso:** Moderador, Admin

**Body:**
```json
{
  "estado": "revisado"
}
```

**Estados validos:** `revisado`, `descartado`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tipo_entidad": "foto_lugar",
    "id_entidad": 5,
    "id_usuario": 3,
    "motivo": "Contenido inapropiado",
    "estado": "revisado"
  },
  "message": "Reporte actualizado"
}
```

**Errores posibles:**
| Codigo | Mensaje |
|--------|---------|
| `ESTADO_INVALIDO` | El estado debe ser "revisado" o "descartado" |
| `REPORTE_NO_ENCONTRADO` | Reporte no encontrado |

---

## Errores Globales

Estos errores pueden ocurrir en cualquier endpoint:

| Codigo | HTTP | Mensaje |
|--------|------|---------|
| `PARAMETROS_REQUERIDOS` | 400 | Los parametros modulo y action son requeridos |
| `MODULO_INVALIDO` | 400 | El modulo especificado no es valido |
| `ACTION_INVALIDO` | 400 | La accion especificada no es valida |
| `DATOS_NO_PERMITIDOS` | 400 | Se detectaron datos no permitidos en la solicitud |
| `AUTH_TOKEN_REQUERIDO` | 401 | Se requiere token de autenticacion |
| `AUTH_INVALID_TOKEN` | 401 | Token invalido o expirado |
| `FORBIDDEN` | 403 | No tienes permisos para esta accion |
| `DB_CONNECTION_ERROR` | 500 | Error de conexion a la base de datos |

---

## Notas Tecnicas

- **Borrado logico:** Todos los DELETE son logicos (campo `enabled = 0`), no se eliminan registros de la BD
- **Fotos:** Se envian como base64 en el body JSON. La API decodifica y sube a AWS S3. Se retorna la URL publica
- **Paginacion:** Los listados soportan `pagina` y `por_pagina` como parametros en la URL
- **Emails:** Se almacenan encriptados con AES-256-CBC en la base de datos
- **Codigo 2FA:** 6 digitos, expira en 4 horas
- **JWT:** Expira en 24 horas (configurable via `JWT_EXPIRATION`)
