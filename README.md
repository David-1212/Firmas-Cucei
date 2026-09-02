# Firmas

Sistema web para el registro de **alumnos**, **documentos** y **firmas**. Permite importar alumnos por ciclo desde archivos CSV, generar documentos con folio automático, capturar firmas (dibujadas con tableta Wacom o con el mouse) y llevar un panel de control con indicadores del trabajo realizado.

## Características

- **Autenticación** con login, verificación de correo y **captcha**.
- **Roles de usuario**: `admin` y `ventanilla`.
- **Alumnos**: importación masiva por ciclo, búsqueda rápida por código/nombre, filtros por carrera y estatus, historial de documentos y firmas compartidos entre ciclos (por código).
- **Documentos**: creación con folio automático (`codigo-idAlumno-secuencia`), tipos de documento, estados (`pendiente`, `firmado`, `entregado`).
- **Firmas**: captura por firma dibujada, galería de firmas por alumno para comparación.
- **Importación CSV** por lotes en segundo plano (cola de trabajos), con seguimiento de progreso en vivo.
- **Dashboard** con contadores y desglose de documentos por tipo.
- **Optimizaciones de rendimiento**: índices B-tree/FULLTEXT y caché en los listados para manejar cientos de miles de alumnos y documentos.

## Requisitos

- PHP **^8.2** (probado con PHP 8.4)
- Composer
- MySQL (o compatible)
- Node.js + npm (para assets con Vite, solo si los recompilas)
- Extensión habilitada para las tablas (la DB se maneja con MySQL por defecto en la app; la configuramos abajo)

## Instalación

1. Clonar el repositorio y entrar a la carpeta:

```bash
git clone git@github.com:David-1212/Firmas-Cucei.git
cd Firmas-Cucei
```

2. Instalar dependencias de PHP:

```bash
composer install
```

3. Crear el archivo de entorno y configurar la base de datos:

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` y ajusta la conexión de base de datos (MySQL):

```env
APP_NAME=Firmas
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=firmas
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

4. Ejecutar migraciones y sembrar datos iniciales:

```bash
php artisan migrate --seed
```

El seeder crea los usuarios iniciales y los tipos de documento base.

> **Importante:** las contraseñas iniciales se definen en `database/seeders/DatabaseSeeder.php`. Cámbialas en producción.

5. Iniciar el servidor de desarrollo y el worker de colas (en dos terminales):

```bash
php artisan serve
```

```bash
php artisan queue:work --sleep=2 --tries=3 --timeout=300
```

> En Windows puedes usar el archivo `arrancar_worker.bat` para levantar el worker de forma estable.

6. (Opcional) Compilar assets con Vite si modificas el frontend:

```bash
npm install
npm run dev   # o: npm run build
```

## Estructura de datos

### Roles y permisos

- **admin**: acceso total (dashboard, importaciones, tipos de documento, usuarios, eliminar alumnos/documentos).
- **ventanilla**: genera documentos, captura firmas y consulta alumnos. No puede importar ni administrar.

### Migraciones destacadas

- Tablas: `users`, `alumnos`, `tipo_documentos`, `documentos`, `firmas`, `importaciones`, `alumno_importacion`.
- Un mismo alumno (mismo código) puede existir en **varios ciclos**; la relación se guarda en `alumno_importacion` y cada importación tiene un `ciclo`.
- El folio del documento incluye el id del alumno para evitar colisiones por código repetido: `codigo-idAlumno-secuencia`.
- Índices de rendimiento para listados grandes (`alumnos.codigo`, `documentos.created_at`, `(estado, created_at)`, `(tipo_documento_id, created_at)`, FULLTEXT en `documentos(folio, observaciones)` y `alumnos(nombre_completo, codigo)`).

## Uso

1. **Importar alumnos**: en la sección *Importar CSV*, sube un archivo CSV de alumnos. Se procesa en segundo plano por lotes y asigna el ciclo vigente.
2. **Generar documentos**: desde el perfil de un alumno, usa *Generar documento*; se crea el folio automáticamente.
3. **Firmar un documento**: abre el documento y usa *Firmar*; se captura la firma dibujada (Wacom o mouse).
4. **Tipos de documento**: admin puede crear, editar o eliminar (solo si el tipo no tiene documentos asociados).
5. **Dashboard**: contadores de alumnos del ciclo actual, documentos, firmas, usuarios y desglose de documentos por tipo.

## Pruebas

La suite de pruebas usa una base MySQL de test (`firmas_test`). Ejecuta:

```bash
vendor\bin\phpunit        # en Windows
./vendor/bin/phpunit      # en Linux/macOS
```

> **Nota:** usa siempre `vendor/bin/phpunit`, **no** `php artisan test --env=testing`, para no apuntar a la base de producción.

## Notas de rendimiento

Se decidió **no usar `%...%` (subcadena) en la búsqueda de códigos** porque, en tablas de cientos de miles de filas, fuerza un escaneo completo del pivote en vez de usar el índice. La búsqueda por código usa **prefijo** `LIKE 'termino%'` (índice B-tree) y la búsqueda por nombre usa **FULLTEXT**. Los listados por defecto se cachean y el caché se regenera al terminar una importación.
