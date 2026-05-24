<div align="center">

# 🎓 Morning Pass

### Sistema de gestión de horarios y reservas para academias de idiomas

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://apache.org)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

*Permite a la recepcionista gestionar en tiempo real los horarios de cada profesor y las reservas de los estudiantes — sin Excel, sin papel.*

</div>

---

## 📋 Tabla de contenidos

- [Descripción](#-descripción)
- [Funcionalidades](#-funcionalidades)
- [Arquitectura](#-arquitectura)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Uso](#-uso)
- [API Reference](#-api-reference)
- [Estructura del proyecto](#-estructura-del-proyecto)
- [Contribuir](#-contribuir)

---

## 📖 Descripción

**Morning Pass** es una aplicación web interna desarrollada en PHP + MySQL que digitaliza la gestión de clases matutinas de una academia de idiomas.

La recepcionista puede visualizar la semana completa de horarios, ver qué cupos están disponibles en cada bloque de cada profesor y reservar estudiantes en segundos — reemplazando completamente el control manual en Excel.

---

## ✨ Funcionalidades

| Módulo | Descripción |
|--------|-------------|
| 📅 **Horario semanal** | Vista tipo Excel con todos los profesores y sus bloques del día, navegable semana a semana |
| 👨‍🏫 **Vista por profesor** | Filtra por JEAN, JR, ECHANDI o ALLEN y ve su semana completa con todos los estudiantes asignados |
| 📝 **Reservas en tiempo real** | Haz clic en cualquier bloque → modal con capacidad visual → busca y agrega estudiantes al instante |
| 🚫 **Control de cupos** | Cada bloque tiene máximo configurable; el sistema bloquea reservas cuando está lleno |
| 👥 **Gestión de estudiantes** | Lista completa de 80+ estudiantes, búsqueda en vivo, agregar o dar de baja |
| 🗓️ **Admin de horarios** | Crear y eliminar bloques de horario por profesor, día, hora y tipo de clase |
| 🔔 **Notificaciones toast** | Confirmaciones y errores visuales en cada acción |
| 📱 **Responsive** | Diseño adaptado para tablets y pantallas de escritorio |

---

## 🏗️ Arquitectura

```
Browser (HTML + CSS + JS vanilla)
        │
        ▼
   Apache 2.4  ──▶  PHP 8.3
        │
        ▼
   MySQL 8.0  (base de datos: morning_pass)
        │
  ┌─────┴──────┐
  │  4 tablas  │
  │ professors │
  │  students  │
  │ time_slots │
  │  bookings  │
  └────────────┘
```

**Sin frameworks pesados.** PHP puro + PDO para la capa de datos, JavaScript vanilla para la UI dinámica.

---

## 🔧 Requisitos

- PHP **7.4+** con extensiones `pdo_mysql` y `mbstring`
- MySQL **5.7+** / MariaDB **10.3+**
- Apache **2.4+** con `mod_rewrite` habilitado
- (Opcional) `rsync` para deploy automatizado

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone git@github.com:Mechandi110101/MORNINGPASS.git
cd MORNINGPASS
```

### 2. Crear la base de datos

```bash
# Desde terminal
mysql -u root -p < setup.sql

# O desde phpMyAdmin: importar el archivo setup.sql
```

Esto crea automáticamente:
- La base de datos `morning_pass`
- Las 4 tablas (profesores, estudiantes, horarios, reservas)
- Datos iniciales: **4 profesores**, **80 estudiantes**, **54 bloques de horario**

### 3. Configurar la conexión

Editar `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'morning_pass');
```

### 4. Iniciar (desarrollo local)

```bash
php -S localhost:8080
```

Abrir: **http://localhost:8080**

### 5. Deploy en producción (Apache)

```bash
# Copiar archivos al web root
rsync -avz ./ /var/www/html/morning-pass/

# Configurar VirtualHost (ver /docs/apache-vhost.conf)
# Dar permisos
chown -R www-data:www-data /var/www/html/morning-pass/
```

---

## ⚙️ Configuración

### Variables de base de datos (`includes/db.php`)

| Variable | Descripción | Default |
|----------|-------------|---------|
| `DB_HOST` | Host de MySQL | `localhost` |
| `DB_USER` | Usuario de MySQL | `root` |
| `DB_PASS` | Contraseña | `` |
| `DB_NAME` | Nombre de la base de datos | `morning_pass` |

### Capacidad por bloque

Cada bloque de horario tiene un campo `max_students` (default: **4**). Se puede cambiar desde `Admin → Horarios` en la interfaz o directamente en la tabla `time_slots`.

---

## 📖 Uso

### Vista principal — Horario semanal

La pantalla de inicio muestra la semana actual en una cuadrícula similar al Excel original:

- **Filas** = franjas horarias (6 AM, 7 AM … 3 PM)
- **Columnas** = días de lunes a viernes
- **Tarjetas de color** = bloques de cada profesor
- **Número en la esquina** = cupos ocupados / total (ej. `2/4`)

**Para reservar un estudiante:**
1. Hacer clic en el bloque deseado
2. En el modal, buscar el nombre del estudiante
3. Seleccionarlo del desplegable
4. Clic en **"Agregar reserva"**

**Para cancelar una reserva:**
- Desde la tarjeta en el horario: clic en `×` junto al nombre
- Desde el modal: botón **"Cancelar"** junto al estudiante

### Filtrar por profesor

Los chips de colores en la parte superior permiten mostrar u ocultar cada profesor individualmente.

### Navegación por semana

Usar las flechas `← Semana anterior` / `Siguiente semana →` o el botón `Hoy` para volver a la semana actual.

---

## 🔌 API Reference

Todos los endpoints devuelven JSON con `{ "ok": true, ... }` en éxito o `{ "ok": false, "error": "..." }` en error.

### Reservas — `api/bookings.php`

| Método | Descripción | Parámetros |
|--------|-------------|------------|
| `GET` | Obtener horario de la semana | `?week=YYYY-MM-DD` |
| `POST` | Crear reserva | `{ slot_id, student_id, week_start, notes? }` |
| `DELETE` | Cancelar reserva | `{ booking_id }` |

### Horarios — `api/slots.php`

| Método | Descripción | Parámetros |
|--------|-------------|------------|
| `GET` | Listar bloques | `?professor_id=N` (opcional) |
| `POST` | Crear bloque | `{ professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students }` |
| `PUT` | Editar bloque | `{ slot_id, ...campos }` |
| `DELETE` | Eliminar bloque | `{ slot_id }` |

### Estudiantes — `api/students.php`

| Método | Descripción | Parámetros |
|--------|-------------|------------|
| `GET` | Listar / buscar | `?q=texto` (opcional) |
| `POST` | Agregar estudiante | `{ name }` |
| `PUT` | Editar nombre | `{ id, name }` |
| `DELETE` | Dar de baja | `{ id }` |

---

## 📁 Estructura del proyecto

```
MORNINGPASS/
├── index.php               # Horario semanal (página principal)
├── professor.php           # Vista por profesor
├── students.php            # Gestión de estudiantes
├── setup.sql               # Schema + datos iniciales
│
├── includes/
│   ├── db.php              # Conexión PDO a MySQL
│   └── functions.php       # Helpers: consultas, formato de tiempo
│
├── api/
│   ├── bookings.php        # CRUD de reservas
│   ├── slots.php           # CRUD de bloques de horario
│   └── students.php        # CRUD de estudiantes
│
├── assets/
│   ├── css/style.css       # Estilos (paleta beige + marrón)
│   └── js/app.js           # Lógica frontend: grid, modal, toast
│
└── admin/
    └── slots.php           # Panel admin para gestionar horarios
```

---

## 🗄️ Esquema de base de datos

```sql
professors  (id, name, color_hex, active)
    │
    ├──▶ time_slots (id, professor_id, day_of_week, start_time, end_time,
    │                class_name, class_type, max_students, notes, active)
    │                    │
students ◀──────────────┴──▶ bookings (id, time_slot_id, student_id,
(id, name, active)                      week_start, status, notes, created_at)
```

---

## 🌐 Demo en producción

| Entorno | URL |
|---------|-----|
| Producción | http://165.99.9.16 |

---

## 🤝 Contribuir

1. Fork del repositorio
2. Crear rama: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -m "feat: descripción del cambio"`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Abrir Pull Request

---

## 📄 Licencia

Distribuido bajo la licencia MIT. Ver `LICENSE` para más información.

---

<div align="center">

Desarrollado con ❤️ para la gestión académica · 2025

</div>
