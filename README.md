# CodeLab — Plugin de Editor de Código para Moodle

Plugin de actividad para Moodle que permite a los estudiantes escribir, ejecutar y entregar código directamente en la plataforma, con **calificación automática** basada en casos de prueba.

---

## Lo único que necesitas instalar

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) — incluye todo lo necesario

---

## Estructura del proyecto

```
ProyectoEditorMoodle/
│
├── docker-compose.yml     ← Orquesta TODOS los servicios
├── Dockerfile             ← Construye la imagen de Moodle
├── docker-entrypoint.sh   ← Instala Moodle automáticamente al arrancar
│
├── judge0/                ← Motor de ejecución de código
│   └── judge0.conf        ← Configuración de Judge0
│
└── mod/
    └── codelab/           ← El plugin que construimos
        ├── version.php
        ├── lib.php
        ├── view.php
        ├── execute.php
        └── ...
```

---

## Cómo correr el proyecto — Paso a paso

### Paso 1 — Abre Docker Desktop

Busca **Docker Desktop** en el menú inicio y ábrelo.
Espera a que el ícono de la ballena 🐳 en la barra de tareas deje de moverse.

---

### Paso 2 — Abre PowerShell en la carpeta del proyecto

Abre PowerShell y navega a la carpeta:

```powershell
cd "C:\Users\alanv\Downloads\ProyectoEditorMoodle"
```

---

### Paso 3 — Construir y levantar todo

**La primera vez** (construye la imagen de Moodle y descarga todo):

```powershell
docker compose up -d --build
```

> ⏱️ **La primera vez tarda 10-20 minutos** porque:
> - Descarga las imágenes de Docker (~2GB en total)
> - Descarga e instala Moodle 4.5 (~60MB)
> - Instala y configura la base de datos
>
> Las **siguientes veces** arranca en menos de 1 minuto con solo:
> ```powershell
> docker compose up -d
> ```

---

### Paso 4 — Verificar que todo está corriendo

```powershell
docker compose ps
```

Deberías ver esto (todos deben mostrar `running` o `Up`):

```
NAME              STATUS
mariadb           Up (healthy)
moodle            Up
judge0_db         Up (healthy)
judge0_redis      Up
judge0_server     Up
judge0_worker     Up
```

---

### Paso 5 — Ver el progreso de instalación de Moodle

Mientras Moodle se instala la primera vez, puedes ver el progreso:

```powershell
docker compose logs -f moodle
```

Cuando veas este mensaje ya está listo:

```
✅ ¡Moodle instalado correctamente!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  🌐 URL:        http://localhost:8080
  👤 Usuario:    admin
  🔑 Contraseña: Admin1234!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

Presiona `Ctrl + C` para salir de los logs.

---

### Paso 6 — Abrir Moodle en el navegador

```
http://localhost:8080
```

**Credenciales:**
| Campo | Valor |
|---|---|
| Usuario | `admin` |
| Contraseña | `Admin1234!` |

---

### Paso 7 — Instalar el plugin CodeLab

1. Entra a Moodle como `admin`
2. Ve a **Administración del sitio** (menú izquierdo)
3. Haz clic en **Notificaciones**
4. Moodle detecta el plugin automáticamente — haz clic en **Actualizar base de datos de Moodle**
5. Sigue los pasos hasta ver "✅ Plugin instalado"

---

### Paso 8 — Configurar el motor de ejecución (Judge0)

1. Ve a **Administración del sitio → Plugins → Módulos de actividad → CodeLab**
2. Configura estos campos:

| Campo | Valor |
|---|---|
| URL de la API Judge0 | `http://judge0_server:2358` |
| Instancia auto-alojada | ✅ Activado |
| Clave API | *(dejar vacío)* |

3. Guarda los cambios

---

### Paso 9 — Crear tu primera actividad CodeLab

1. Ve a un curso en Moodle
2. Activa el **modo de edición** (botón arriba a la derecha)
3. Clic en **"Añadir actividad o recurso" → CodeLab**
4. Configura la actividad:

**Nombre:** `Función suma en Python`

**Lenguaje por defecto:** Python

**Código inicial:**
```python
# Lee dos números de la entrada y muéstralos sumados
a, b = map(int, input().split())
print(a + b)
```

**Casos de prueba:**
| Nombre | Entrada | Salida esperada | Puntos |
|---|---|---|---|
| Suma básica | `2 4` | `6` | 1 |
| Suma con negativo | `-1 5` | `4` | 1 |
| Suma con cero | `0 10` | `10` | 1 |

5. Guarda y prueba la actividad

---

## Guía completa de pruebas del plugin

### Como PROFESOR — Crear un curso y una tarea CodeLab

#### 1. Crear un curso

1. Ve a **Administración del sitio → Gestionar cursos y categorías**
2. Haz clic en **Crear nuevo curso**
3. Rellena los datos mínimos:

| Campo | Valor de ejemplo |
|---|---|
| Nombre completo | `Programación I` |
| Nombre corto | `PROG1` |

4. Guarda y entra al curso

#### 2. Matricular estudiantes de prueba

1. Dentro del curso, ve al menú **Participantes**
2. Haz clic en **Matricular usuarios**
3. Si aún no tienes una cuenta de estudiante, créala primero:
   - Ve a **Administración del sitio → Usuarios → Cuentas → Añadir usuario**
   - Crea un usuario de prueba (ej. `estudiante1` / `Estudiante1234!`)
4. Vuelve al curso y matricula al usuario con el rol **Estudiante**

#### 3. Crear la actividad CodeLab

1. Dentro del curso, activa el **modo de edición** (botón arriba a la derecha)
2. Haz clic en **"+ Añadir actividad o recurso"** en cualquier sección
3. Selecciona **CodeLab** de la lista
4. Configura la actividad con estos valores de ejemplo:

**Sección "General":**
| Campo | Valor |
|---|---|
| Nombre | `Tarea 1: Función Suma` |
| Descripción | `Crea una función que sume dos números ingresados por teclado.` |

**Sección "Configuración del editor":**
| Campo | Valor |
|---|---|
| Lenguaje por defecto | `python` |
| Código inicial | *(ver abajo)* |
| Calificación máxima | `100` |
| Intentos máximos | `0` *(ilimitados)* |

**Código inicial sugerido (Modo simplificado):**
```python
# Escribe solo tu función — no necesitas usar input() ni print()
def sumar(a, b):
    # TODO: retorna la suma de a y b
    pass
```

**Sección "Código envolvente (runner automático)"** — esto permite que el estudiante solo escriba su función:
```python
a, b = map(int, input().split())
{{CODE}}
print(sumar(a, b))
```
> Con el runner activo, el estudiante escribe **solo** `def sumar(a, b): return a + b` y el sistema se encarga de leer la entrada y mostrar el resultado. Si prefieres el modo clásico (stdin manual), deja el runner vacío.

**Sección "Casos de prueba"** — añade estos tres casos:

| Nombre | Entrada (stdin) | Salida esperada | Puntos | Oculto |
|---|---|---|---|---|
| Suma básica | `2 4` | `6` | 30 | No |
| Suma con negativo | `-1 5` | `4` | 30 | No |
| Caso oculto (evaluación) | `100 200` | `300` | 40 | Sí |

> Los casos **ocultos** son visibles solo para el profesor — el estudiante sabe que existen pero no puede ver la entrada/salida.

5. Haz clic en **Guardar y mostrar**

---

### Como ESTUDIANTE — Resolver y entregar la tarea

1. Cierra sesión como `admin` o abre una **ventana privada** del navegador
2. Entra a [http://localhost:8080](http://localhost:8080) con las credenciales del estudiante:
   - Usuario: `estudiante1`
   - Contraseña: `Estudiante1234!`
3. Ve al curso **Programación I**
4. Haz clic en la actividad **Tarea 1: Función Suma**
5. Verás el editor de código con el código inicial ya cargado

#### Probar el código antes de entregar

1. Escribe tu solución en el editor (si el profesor activó el modo simplificado, solo escribe la función):
```python
def sumar(a, b):
    return a + b
```
2. Haz clic en **"Ejecutar y probar"** (botón verde)
3. El panel derecho mostrará los resultados de cada caso de prueba:
   - ✅ **Verde** = caso pasado
   - ❌ **Rojo** = caso fallado (muestra la salida esperada vs. la obtenida)
   - El marcador superior muestra el puntaje: ej. `2 / 3` (el oculto no se puede ver)
4. Ajusta el código hasta que todos los casos visibles pasen

#### Entregar la solución

1. Cuando el código funcione correctamente, haz clic en **"Entregar"** (botón azul)
2. Moodle ejecuta todos los casos (incluidos los ocultos) y calcula la nota automáticamente
3. Aparece un mensaje de confirmación con:
   - Fecha y hora de entrega
   - Nota obtenida (ej. `90.0 / 100`)
   - Número de intentos usados

---

### Como PROFESOR — Revisar las entregas

1. Entra de nuevo como `admin`
2. Ve al curso y haz clic en la actividad **Tarea 1: Función Suma**
3. Verás la vista del profesor con dos pestañas:

#### Pestaña "Entregas"

Muestra una tabla con todos los estudiantes que entregaron:

| Columna | Descripción |
|---|---|
| Estudiante | Nombre del alumno |
| Entregado | Fecha y hora de entrega |
| Lenguaje | Lenguaje usado |
| Tests pasados | Ej. `3 / 3` |
| Calificación | Nota automática calculada |
| Acciones | Botón "Ver" para revisar el código |

#### Ver el código de un estudiante

1. Haz clic en **Ver** en la fila del estudiante
2. Podrás ver:
   - El código completo que entregó
   - El resultado de cada caso de prueba
   - La nota calculada automáticamente
3. Si quieres **ajustar la nota manualmente**, escribe una calificación diferente y añade un comentario de retroalimentación

#### Pestaña "Estadísticas"

Muestra métricas generales de la actividad:
- Total de estudiantes que entregaron
- Promedio de calificaciones
- Tasa de aprobados
- Qué casos de prueba fallan más los estudiantes

---

### Flujo rápido de verificación (checklist)

Usa esta lista para confirmar que todo funciona de extremo a extremo:

- [ ] El profesor puede crear una actividad CodeLab con casos de prueba
- [ ] El estudiante ve el editor con el código inicial
- [ ] Al hacer clic en "Ejecutar y probar", los casos visibles se evalúan
- [ ] Los casos pasados aparecen en verde, los fallados en rojo
- [ ] Al hacer clic en "Entregar", se registra la entrega y aparece la nota
- [ ] El profesor ve la entrega en la lista con la nota correcta
- [ ] El profesor puede ver el código del estudiante y modificar la nota
- [ ] Las estadísticas de la actividad se actualizan

---

## Comandos útiles del día a día

```powershell
# Iniciar el proyecto
docker compose up -d

# Parar el proyecto (sin borrar datos)
docker compose down

# Ver qué está corriendo
docker compose ps

# Ver logs de Moodle en tiempo real
docker compose logs -f moodle  

# Ver logs de Judge0 (ejecución de código)
docker compose logs -f judge0_server

# Reconstruir la imagen de Moodle (solo si cambias el Dockerfile)
docker compose up -d --build moodle

# Borrar TODO y empezar desde cero
docker compose down -v
docker compose up -d --build
```

---

## Solución de problemas

### Moodle dice "sitio en mantenimiento" o pantalla en blanco
```powershell
docker compose logs moodle --tail=30
```
Espera unos minutos. Si la instalación no ha terminado, dale más tiempo.

### Error "No se pudo conectar a la base de datos"
La base de datos tarda en arrancar. El sistema reintenta automáticamente 30 veces.
Si persiste:
```powershell
docker compose restart mariadb
```

### Judge0 no ejecuta código
```powershell
# Verificar que Judge0 responde
curl http://localhost:2358/system_info
# O abre en el navegador: http://localhost:2358/languages
```

### El puerto 8080 ya está en uso
Edita `docker-compose.yml`, cambia `"8080:80"` por ejemplo a `"8090:80"`,
y accede a `http://localhost:8090`

---

## Arquitectura del sistema

```
Tu navegador
    │
    ▼  http://localhost:8080
┌─────────────────────┐
│       MOODLE        │  ← Plugin CodeLab incluido
│   (PHP + Apache)    │
└─────────────────────┘
    │                 │
    ▼                 ▼
┌─────────┐    ┌──────────────────────┐
│ MariaDB │    │   Judge0 Server      │
│(base de │    │   :2358              │
│ datos)  │    │                      │
└─────────┘    │ Ejecuta código en    │
               │ sandbox seguro       │
               └──────────────────────┘
                    │          │
               ┌────┘     ┌────┘
               ▼          ▼
          Postgres      Redis
          (judge0_db) (judge0_redis)
```

---

## Lenguajes de programación soportados

Python · JavaScript (Node.js) · Java · C · C++ · PHP · C# · Ruby · Go · Kotlin

---

## Licencia

GNU GPL v3
