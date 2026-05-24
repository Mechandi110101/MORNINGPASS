<div align="center">

# 🎾 Morning Pass

### Sistema de gestión de horarios y reservas para academias de tenis y pádel

[![Version](https://img.shields.io/badge/version-2.0.0-brightgreen?style=for-the-badge)](https://github.com/Mechandi110101/MORNINGPASS/releases/tag/v2.0.0)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://apache.org)
[![License](https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge)](LICENSE)

*Digitaliza en segundos la gestión completa de horarios, inscripciones, membresías y clases especiales — sin Excel, sin papel.*

[**Demo en vivo →**](http://165.99.9.16) &nbsp;·&nbsp; [Reportar un bug](https://github.com/Mechandi110101/MORNINGPASS/issues) &nbsp;·&nbsp; [Ver changelog](#-changelog)

</div>

---

## 📋 Tabla de contenidos

- [¿Qué es Morning Pass?](#-qué-es-morning-pass)
- [Novedades v2.0](#-novedades-v20)
- [Funcionalidades completas](#-funcionalidades-completas)
- [Arquitectura](#-arquitectura)
- [Esquema de base de datos](#-esquema-de-base-de-datos)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Migraciones](#-migraciones)
- [Configuración](#-configuración)
- [Guía de uso](#-guía-de-uso)
- [API Reference](#-api-reference)
- [Estructura del proyecto](#-estructura-del-proyecto)
- [Changelog](#-changelog)

---

## 📖 ¿Qué es Morning Pass?

**Morning Pass** es una aplicación web interna desarrollada en PHP puro + MySQL que reemplaza completamente el control manual de asistencia en Excel para una academia de deportes de raqueta.

La recepcionista puede desde una sola pantalla:

- Ver la semana completa de todos los profesores en una grilla visual
- Inscribir o quitar estudiantes de un grupo con un solo clic
- Controlar cupos disponibles, clases de prueba, clases premio y membresías
- Buscar cualquier alumno para ver en qué grupos está inscrito
- Gestionar múltiples programas (Morning Pass, Academia, Team Competition)

**Sin frameworks pesados.** PHP puro + PDO, JavaScript vanilla, sin dependencias npm.

---

## 🆕 Novedades v2.0

| # | Feature | Descripción |
|---|---------|-------------|
| 1 | **📊 Dashboard** | Métricas en tiempo real: grupos llenos, cupos disponibles hoy, clases especiales de la semana, alertas de membresía |
| 2 | **🌙 Modo oscuro** | Toggle persiste en `localStorage`; todos los elementos con contraste correcto garantizado |
| 3 | **💳 Control de membresías** | Estado (`active / courtesy / expired`) + fecha de vencimiento por estudiante; alertas automáticas en el dashboard |
| 4 | **🏆 Clase Premio** | Tercer tipo de inscripción date-specific (además de regular y prueba); badge ámbar dorado en horarios |
| 5 | **📝 Notas por inscripción** | Campo de texto libre al inscribir; se muestra en el modal y en la lista |
| 6 | **📋 Exportar lista** | Copia la lista completa del grupo formateada para WhatsApp con un clic |
| 7 | **🔍 Buscador en horario** | Filtra la grilla en tiempo real — solo muestra los grupos donde está ese alumno |
| 8 | **✏️ Edición inline** | Edita hora, nombre y capacidad de un bloque directamente en la tabla sin recargar la página |
| 9 | **➕ Creación rápida** | Haz clic en `+` en cualquier celda vacía del horario para crear un nuevo grupo preconfigurado |
| 10 | **👥 Lista plegable** | Pestaña Estudiantes con sección colapsable, búsqueda en vivo y eliminación con cascade automático |
| 11 | **🔄 Estado de grupos** | Ciclo de vida completo: `Pendiente → Activo → Cerrado`; visual diferenciado en horarios y vista por profesor |
| 12 | **📸 Gestión de profesores** | Alta, baja, edición, foto de perfil, color personalizado, disponibilidad semanal |
| 13 | **3 Programas** | Morning Pass 🌅 / Academia 🏫 / Team Competition 🏆 — cada uno con su propio horario y gestión |

---

## ✨ Funcionalidades completas

### Vista de Horario (`index.php`)
- Grilla semanal Lunes–Viernes con franjas horarias
- Tarjetas de colores por profesor con contador de cupos (`inscritos/máximo`)
- Filtro por profesor (chips de color, toggle individual)
- **Buscador** integrado al lado del filtro: escribe un nombre y solo se muestran los grupos donde está ese alumno; `Escape` limpia el filtro
- Navegación por semanas (anterior / hoy / siguiente)
- Clic en tarjeta → modal de inscripción con información completa del grupo
- Clic en `+` en celda vacía → modal de creación rápida
- Tres tipos de inscripción: **Regular** (recurrente), **Clase de Prueba** (fecha única, badge rojo), **Clase Premio** (fecha única, badge ámbar)
- Exportación de lista del grupo a portapapeles (formato WhatsApp)

### Dashboard (`dashboard.php`)
- Estadísticas en tiempo real por programa:
  - Grupos llenos vs. total activos
  - Cupos disponibles hoy
  - Clases especiales (prueba + premio) de la semana
  - Profesores activos
  - Alertas de membresía (vencida o por vencer este mes)
- Horario de hoy como tarjetas horizontales
- Lista de clases especiales de la semana con badges visuales
- Renovación de membresía con un clic desde el dashboard

### Por Profesor (`professor.php`)
- Vista de la semana organizada por columnas (un día por columna)
- Muestra todos los grupos activos, pendientes y cerrados
- Contador de alumnos inscritos por grupo
- Indicador de estado con badge visual

### Estudiantes (`students.php`)
- Formulario de alta: nombre, sexo, categoría, teléfono, estado de membresía, fecha de vencimiento
- Lista plegable con buscador en vivo (filtra por nombre sin recargar)
- Badge de membresía: verde con fecha ✓ / rojo "Membresía vencida" / gris "Cortesía"
- Eliminación con cascade: elimina automáticamente todas las inscripciones asociadas

### Admin Horarios (`admin/slots.php`)
- Tabla completa de todos los bloques del programa
- Formulario de creación con todos los campos (programa, profesor, día, hora, nombre, máx. estudiantes, estado inicial)
- **Edición inline**: edita hora, nombre y capacidad directamente en la fila sin modal ni recarga
- Botones de activación / cierre de grupos
- Eliminación de bloques (con confirmación)

### Admin Profesores (`admin/professors.php`)
- Alta y edición de profesores (nombre, color, disponibilidad, foto)
- Upload de foto de perfil
- Toggle activo/inactivo

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────┐
│                   Navegador Web                      │
│         HTML + CSS + Vanilla JavaScript              │
│  (sin frameworks: no React, no Vue, no jQuery)       │
└──────────────────────┬──────────────────────────────┘
                       │  HTTP / JSON
                       ▼
┌─────────────────────────────────────────────────────┐
│              Apache 2.4  /  PHP 8.3                  │
│                                                      │
│   Páginas PHP        │       API REST (JSON)         │
│   ─────────────      │       ──────────────────      │
│   index.php          │       api/bookings.php        │
│   dashboard.php      │       api/slots.php           │
│   professor.php      │       api/students.php        │
│   students.php       │       api/professors.php      │
│   admin/slots.php    │                               │
│   admin/professors   │                               │
└──────────────────────┬──────────────────────────────┘
                       │  PDO
                       ▼
┌─────────────────────────────────────────────────────┐
│                  MySQL 8.0                           │
│          Base de datos: morning_pass                 │
│                                                      │
│   programs    professors   students   time_slots     │
│                                                      │
│                     enrollments                      │
└─────────────────────────────────────────────────────┘
```

**Decisiones de diseño:**
- **Sin ORM**: consultas PDO directas para control total y simplicidad
- **API REST mínima**: solo los endpoints necesarios, respuestas JSON consistentes
- **CSS custom properties**: tema claro/oscuro con una sola definición de variables
- **JavaScript modular**: funciones con responsabilidad única, sin estado global innecesario

---

## 🗄️ Esquema de base de datos

```sql
programs
  id · name · icon · color_hex

professors
  id · name · color_hex · availability (TEXT) · photo · active

students
  id · name · gender · category · phone
  membership_status  (active | courtesy | expired)
  membership_expires (DATE)
  active

time_slots
  id · program_id → programs
  professor_id    → professors
  day_of_week     (1=Lun … 5=Vie)
  start_time · end_time
  class_name · class_type · max_students · notes
  slot_status     (active | pending | closed)
  start_date · end_date · active

enrollments
  id · time_slot_id → time_slots
  student_id        → students
  status            (active | cancelled)
  is_trial   (0/1) · trial_date  (DATE)   -- Clase de prueba
  is_award   (0/1) · award_date  (DATE)   -- Clase premio
  notes
  created_at
```

**Relaciones clave:**
- Un `time_slot` pertenece a un `program` y a un `professor`
- Un `enrollment` une un `student` con un `time_slot` para una semana concreta (trial/award) o recurrente (regular)
- Al eliminar un estudiante → `DELETE CASCADE` en todas sus inscripciones

---

## 🔧 Requisitos

| Componente | Versión mínima | Notas |
|-----------|---------------|-------|
| PHP | 8.1+ | Extensiones: `pdo_mysql`, `mbstring`, `fileinfo` |
| MySQL | 8.0+ | Requerido `information_schema` para migraciones |
| Apache | 2.4+ | `mod_rewrite` habilitado |
| Disco | 50 MB | Para fotos de profesores |

> **MySQL nota:** `ADD COLUMN IF NOT EXISTS` no está disponible en MySQL 8.0. Las migraciones usan stored procedures que verifican `information_schema.COLUMNS` antes de agregar columnas.

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Mechandi110101/MORNINGPASS.git
cd MORNINGPASS
```

### 2. Crear base de datos y usuario MySQL

```sql
-- Como root en MySQL
CREATE DATABASE morning_pass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mpuser'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON morning_pass.* TO 'mpuser'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Importar el schema

```bash
mysql -u mpuser -p morning_pass < setup.sql
```

`setup.sql` crea todas las tablas y los datos de ejemplo (programas, profesores iniciales, bloques de horario de muestra).

### 4. Configurar la conexión

Editar `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'mpuser');
define('DB_PASS', 'tu_contraseña_segura');
define('DB_NAME', 'morning_pass');
```

### 5. Servidor de desarrollo local

```bash
php -S localhost:8080
# Abrir: http://localhost:8080
```

### 6. Deploy en producción (Apache)

```bash
# Copiar archivos
rsync -avz --exclude='.git' ./ /var/www/html/morning-pass/

# Permisos
chown -R www-data:www-data /var/www/html/morning-pass/
chmod 755 /var/www/html/morning-pass/

# Crear directorio de uploads (fotos profesores)
mkdir -p /var/www/html/morning-pass/uploads/professors
chmod 775 /var/www/html/morning-pass/uploads/professors
```

**VirtualHost Apache mínimo:**

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/html/morning-pass
    <Directory /var/www/html/morning-pass>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 🔄 Migraciones

Si actualizas desde una versión anterior, aplica las migraciones en orden:

```bash
# v1 → v2 (base)
mysql -u mpuser -p morning_pass < migrate.sql

# v2 → v3 (group status, trial classes, professor photos)
mysql -u mpuser -p morning_pass < migrate_v3.sql

# v3 → v4 (award classes)
mysql -u mpuser -p morning_pass < migrate_v4.sql

# v4 → v5 / v2.0 (membership tracking)
mysql -u mpuser -p morning_pass < migrate_v5.sql
```

> **Instalación limpia:** solo necesitas `setup.sql` — ya incluye el schema completo v2.0.

---

## ⚙️ Configuración

### `includes/db.php` — Conexión a base de datos

```php
define('DB_HOST', 'localhost');   // Host MySQL
define('DB_USER', 'mpuser');      // Usuario
define('DB_PASS', '...');         // Contraseña
define('DB_NAME', 'morning_pass');// Nombre de la BD
```

### Capacidad por grupo

El campo `max_students` en `time_slots` controla el máximo de inscripciones por grupo. Default: **4**. Configurable desde `Admin → Horarios` en la interfaz.

### Programas

Los programas están definidos en `includes/nav.php` con su ícono, nombre y color. Para agregar un programa nuevo también debes insertarlo en la tabla `programs`.

```php
$programs = [
    1 => ['name' => 'Morning Pass',     'icon' => '🌅', 'color' => '#B8232a'],
    2 => ['name' => 'Academia',         'icon' => '🏫', 'color' => '#27393f'],
    3 => ['name' => 'Team Competition', 'icon' => '🏆', 'color' => '#1a6a8a'],
];
```

---

## 📖 Guía de uso

### Inscribir un estudiante

1. Ir a **Horario**, navegar a la semana deseada
2. Hacer clic en el bloque del profesor y horario correcto
3. En el modal: buscar el nombre del estudiante, seleccionarlo
4. Elegir tipo de inscripción:
   - **Regular** → inscripción recurrente (aparece en todas las semanas)
   - **Clase de Prueba** → seleccionar fecha específica (badge rojo)
   - **Clase Premio** → seleccionar fecha específica (badge ámbar)
5. Agregar nota opcional → clic en **Inscribir estudiante**

### Buscar un alumno en el horario

1. En la pestaña **Horario**, escribir el nombre en el buscador (junto a los chips de profesores)
2. La grilla filtra en tiempo real — solo muestra los grupos donde está ese alumno
3. Las celdas sin coincidencia se oscurecen
4. `Escape` o clic en ✕ para limpiar el filtro

### Gestionar membresías

- Desde **Estudiantes**: al agregar o editar un alumno, asignar estado y fecha de vencimiento
- Desde el **Dashboard**: la sección de alertas muestra estudiantes con membresía vencida o por vencer; clic en **Renovar** para extender un mes automáticamente
- En el modal de inscripción: si el alumno tiene membresía vencida, aparece una alerta naranja

### Ciclo de vida de un grupo

```
Pendiente ──▶ Activo ──▶ Cerrado
   (creado)    (acepta      (no acepta
               inscrip.)    más inscrip.)
```

Desde `Admin → Horarios`: botón ▶ **Activar** o 🔒 **Cerrar** por grupo.

### Exportar lista de un grupo (WhatsApp)

1. Abrir el modal de cualquier grupo
2. Clic en **📋 Copiar** (esquina superior del modal)
3. La lista formateada queda en el portapapeles, lista para pegar en WhatsApp

---

## 🔌 API Reference

Todos los endpoints aceptan y devuelven `Content-Type: application/json`.

**Respuesta exitosa:** `{ "ok": true, ...datos }`
**Error:** `{ "ok": false, "error": "descripción" }` + HTTP status code

---

### `GET /api/bookings.php`

Devuelve el horario completo de una semana con todos sus grupos e inscripciones.

```
GET /api/bookings.php?week=2025-05-19&p=1
```

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `week` | `YYYY-MM-DD` | ✓ | Lunes de la semana deseada |
| `p` | `int` | ✓ | ID del programa |

**Respuesta:** `{ schedule: { 1: { slotId: {..., bookings: [...]} } } }`

---

### `POST /api/bookings.php`

Crea una inscripción.

```json
{
  "slot_id": 12,
  "student_id": 45,
  "week_start": "2025-05-19",
  "is_trial": 0,
  "trial_date": null,
  "is_award": 0,
  "award_date": null,
  "notes": "Viene con su hermano"
}
```

> Verifica automáticamente la capacidad máxima antes de inscribir.

---

### `DELETE /api/bookings.php`

Cancela una inscripción.

```json
{ "booking_id": 88 }
```

---

### `GET /api/slots.php`

Lista los bloques de horario.

```
GET /api/slots.php?p=1
```

---

### `POST /api/slots.php`

Crea un bloque de horario.

```json
{
  "program_id": 1,
  "professor_id": 3,
  "day_of_week": 2,
  "start_time": "07:00",
  "end_time": "08:00",
  "class_name": "CAT 5TA JEAN",
  "max_students": 4,
  "slot_status": "active",
  "start_date": "2025-01-01",
  "end_date": null
}
```

---

### `PUT /api/slots.php`

Edita un bloque o cambia su estado.

```json
// Editar campos
{ "slot_id": 5, "class_name": "CAT 4TA", "max_students": 6, ... }

// Activar grupo
{ "slot_id": 5, "action": "activate" }

// Cerrar grupo
{ "slot_id": 5, "action": "close" }
```

---

### `DELETE /api/slots.php`

Elimina un bloque y todas sus inscripciones.

```json
{ "slot_id": 5 }
```

---

### `GET /api/students.php`

Lista o busca estudiantes.

```
GET /api/students.php                          # todos
GET /api/students.php?q=JEAN                   # búsqueda
GET /api/students.php?with_slots=1&q=JEAN      # con sus grupos inscritos
GET /api/students.php?p=1                      # filtrar por programa
```

---

### `POST /api/students.php`

Crea un estudiante.

```json
{
  "name": "JUAN PÉREZ",
  "gender": "M",
  "category": "CAT 4TA",
  "phone": "8888-0000",
  "membership_status": "active",
  "membership_expires": "2025-12-31"
}
```

---

### `PUT /api/students.php`

Edita un estudiante. Acepta modo `membership_only` para actualizar solo la membresía.

```json
// Actualización completa
{ "id": 10, "name": "JUAN PÉREZ", "membership_status": "active", ... }

// Solo membresía (desde dashboard)
{ "id": 10, "membership_only": true, "membership_status": "active", "membership_expires": "2026-01-31" }
```

---

### `DELETE /api/students.php`

Elimina un estudiante y **todas sus inscripciones** (cascade real, no soft-delete).

```json
{ "id": 10 }
```

---

### `GET /api/professors.php`

Lista todos los profesores activos.

---

### `POST /api/professors.php`

Crea o edita un profesor. Soporta multipart/form-data para upload de foto.

---

## 📁 Estructura del proyecto

```
MORNINGPASS/
│
├── index.php                   # Horario semanal + buscador + modal inscripción
├── dashboard.php               # Métricas, alertas membresía, horario de hoy
├── professor.php               # Vista semanal por profesor
├── students.php                # Listado plegable + alta de estudiantes
│
├── admin/
│   ├── slots.php               # Gestión de bloques de horario (CRUD + inline edit)
│   └── professors.php          # Gestión de profesores (CRUD + foto)
│
├── api/
│   ├── bookings.php            # Inscripciones: GET semana, POST inscribir, DELETE cancelar
│   ├── slots.php               # Bloques de horario: CRUD + activar/cerrar
│   ├── students.php            # Estudiantes: CRUD + búsqueda enriquecida
│   └── professors.php          # Profesores: CRUD + upload foto
│
├── includes/
│   ├── db.php                  # Conexión PDO (configurar credenciales aquí)
│   ├── functions.php           # getScheduleForWeek(), getStudents(), helpers
│   └── nav.php                 # Barra de navegación compartida + dark mode init
│
├── assets/
│   ├── css/style.css           # Todos los estilos (custom properties, dark mode)
│   └── js/app.js               # Lógica frontend: grilla, modales, filtros, toast
│
├── uploads/
│   └── professors/             # Fotos de perfil (generado automáticamente)
│
├── setup.sql                   # Schema completo v2.0 + datos iniciales
├── migrate.sql                 # Migración v1 → v2
├── migrate_v3.sql              # Migración v2 → v3 (group status, trial, professor photos)
├── migrate_v4.sql              # Migración v3 → v4 (award classes)
├── migrate_v5.sql              # Migración v4 → v5 / v2.0 (membership tracking)
└── .gitignore
```

---

## 📊 Changelog

### v2.0.0 — Mayo 2026

**Nuevas funcionalidades:**
- `dashboard.php` — Dashboard con 5 métricas en tiempo real + alertas de membresía + horario de hoy + clases especiales de la semana
- Modo oscuro completo con toggle persistente (`localStorage`) y contraste garantizado en todos los elementos
- Control de membresías: campo `membership_status` + `membership_expires` en `students`; renovación con un clic
- Tipo de inscripción "Clase Premio" (`is_award` / `award_date`) con badge ámbar dorado
- Notas por inscripción (campo libre en el modal de inscripción)
- Exportar lista del grupo al portapapeles en formato WhatsApp
- Buscador en la pestaña Horario: filtra la grilla en tiempo real por nombre de alumno (display:none en no-coincidencias, persiste al navegar semanas)
- Edición inline en Admin Horarios (edita hora/nombre/capacidad sin recargar)
- Creación rápida de grupos desde la grilla (clic en `+` en celda vacía)
- Lista de estudiantes plegable (`<details>`) con búsqueda en vivo y DELETE en cascada
- Gestor de profesores con foto de perfil y disponibilidad semanal
- Ciclo de vida de grupos: `pending → active → closed` con visual diferenciado
- Soporte para 3 programas independientes con `?p=N`

**Correcciones:**
- Dark mode: todos los elementos con `color: var(--blue)` como texto (labels, títulos, botones secundarios, week nav) ahora tienen override explícito
- DELETE de estudiantes: hard delete con cascade en lugar de soft-delete (eliminaba alumno pero dejaba enrollments huérfanos)
- Filtro de horario: reimplementado con `display:none` en lugar de `opacity` — ahora los grupos sin el alumno desaparecen completamente
- Barra de búsqueda: rediseñada con SVG icon real, clear button circular, hint de resultados

### v1.0.0 — 2025

- Vista de horario semanal con grilla de profesor × día × hora
- Inscripción y cancelación de estudiantes con modal
- Control de cupos por bloque
- Tres programas: Morning Pass, Academia, Team Competition
- Vista "Por Profesor" con todos los grupos de la semana
- Gestión básica de estudiantes
- Admin de horarios (CRUD de bloques)

---

## 🤝 Contribuir

1. Fork del repositorio
2. Crear rama descriptiva: `git checkout -b feature/nueva-funcionalidad`
3. Commit con prefijo semántico: `git commit -m "feat: descripción"`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Abrir Pull Request contra `main`

**Prefijos de commit usados:**
- `feat:` — nueva funcionalidad
- `fix:` — corrección de bug
- `refactor:` — refactorización sin cambio funcional
- `style:` — cambios visuales / CSS
- `docs:` — documentación

---

## 📄 Licencia

Distribuido bajo la licencia MIT. Ver [`LICENSE`](LICENSE) para más información.

---

<div align="center">

Desarrollado con ❤️ para la gestión de academias deportivas

**Morning Pass v2.0** · PHP 8.3 · MySQL 8.0 · Vanilla JS

</div>
