# ObraOS

**ObraOS** es una aplicación web para constructoras y presupuestistas que centraliza la **elaboración de presupuestos de obra** con memoria de cálculo, análisis de precios unitarios (APU) y catálogos de costos, todo aislado por empresa (multi-tenant).

El sistema reproduce las fórmulas de la hoja de cálculo tradicional de presupuestos ("PRESUPUESTO GENERAL", "MEMORIA DE CALCULO", etc.) y permite **exportar el libro completo a Excel** con el mismo formato que usa el usuario.

---

## 🌿 Rama de trabajo

> **Importante:** la rama `main` está estable y funcionando. **No se toca ni se mezcla.**
> Todo el trabajo de Docker y documentación vive únicamente en la rama **`feature/docker`**, que se mantiene separada de `main`.

```bash
git checkout feature/docker
```

Esta rama se sube a `origin` para que el equipo la pruebe, pero **no se fusiona con `main`**. Si en el futuro se decide integrar, se hará de forma explícita y revisada.

---

## ✨ Funcionalidades

- **Autenticación y registro por empresa**: cada empresa crea su cuenta y sus catálogos quedan aislados del resto.
- **Dashboard** con KPIs reales de la empresa (proyectos, APUs, materiales, equipos, mano de obra, proveedores), proyectos recientes y configuración del AIU.
- **Catálogos de costos**:
  - Proveedores
  - Materiales (con precio por unidad y proveedor)
  - Equipos (alquiler, tipo)
  - Mano de obra (cargos, jornal, prestaciones)
- **Importación masiva desde Excel**: carga o actualiza cualquiera de los 4 catálogos desde `.xlsx`, `.xls` o `.csv`, con plantilla descargable y reporte de creados, actualizados y errores por fila.
- **APUs (Análisis de Precios Unitarios)**: composición de un precio unitario a partir de materiales, mano de obra y equipos, con rendimiento y desperdicio.
- **Proyectos / Presupuestos**: editor con capítulos e ítems, cantidades, duración y cálculo automático de AIU.
- **Exportación a Excel** (`.xlsx`) con múltiples hojas: `LISTA DE PRECIOS`, `ALQUILER DE EQUIPOS`, `RENDIMIENTO`, una hoja por APU, `MEMORIA DE CALCULO`, `PRESUPUESTO GENERAL`, `TABLA DE TAREAS` y `EJE UNIVERSAL`.

---

## 🧱 Stack técnico

| Capa | Tecnología |
| --- | --- |
| Backend | PHP 8.2+ · Laravel 12 |
| Frontend reactivo | Livewire 4 · Alpine.js |
| Estilos | Tailwind CSS 4 |
| Bundler | Vite 7 |
| Base de datos | MySQL (o SQLite para tests) |
| Excel | PhpSpreadsheet 5 |
| Tests | PHPUnit |

---

## ✅ Requisitos

- PHP **8.2** o superior con las extensiones habituales de Laravel (`zip`, `gd`, `mbstring`, `pdo_mysql`).
- Composer 2
- Node.js 18+ y npm
- MySQL 8 (recomendado) o SQLite
- Opcional: [Laragon](https://laragon.org/) para levantar el entorno en Windows.

> ¿Solo quieres probar la aplicación? Salta a [Ejecutar con Docker](#-ejecutar-con-docker-recomendado) y olvídate de instalar PHP, Composer, Node y MySQL.

---

## 🐳 Ejecutar con Docker (recomendado)

Es la forma más rápida de correr ObraOS y funciona igual en **Windows, macOS y Linux**. No necesitas PHP, Composer, Node ni MySQL en tu máquina: todo se construye dentro de los contenedores.

### Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) en Windows/macOS, o Docker Engine + Compose en Linux.

### 1. Clonar y levantar

```bash
git clone https://github.com/jmdiaz23/obras.git obras
cd obras
git checkout feature/docker
docker compose up -d --build
```

La primera vez tarda unos minutos (descarga imágenes, instala dependencias y compila los assets). El contenedor `app` espera a que MySQL esté listo, genera el `APP_KEY` y ejecuta las migraciones automáticamente.

### 2. Abrir la aplicación

👉 **http://localhost:8090**

### 3. Cargar los datos de demostración (opcional)

```bash
# Opción A: al levantar por primera vez
OBRAOS_SEED_DEMO=true docker compose up -d --build

# En PowerShell:
$env:OBRAOS_SEED_DEMO="true"; docker compose up -d --build

# Opción B: con la aplicación ya corriendo
docker compose exec app php artisan db:seed --class=DemoObraSeeder
```

Después inicia sesión con `jesu@obras.com` / `password123`.

El seeder es idempotente: se puede volver a ejecutar sin duplicar ni borrar datos. Carga:

- 3 proveedores, 47 materiales, 6 equipos y 2 cargos de mano de obra.
- 3 APUs: concreto 3000 psi (`APU-001`, $348.671,72/m³), muro de bloque (`APU-002`) y piso cerámico (`APU-003`).
- Proyecto **Casa lote 12, Altos de la Sabana** (Barranquilla), con capítulos *Obra gruesa* y *Acabados*, AIU 10/5/10, subtotal **$57.491.848,24** y total **$71.864.810,29**.

### Puertos

Se eligieron puertos distintos a los habituales para no chocar con otros proyectos locales (80, 3306, 8000 y 8080 suelen estar ocupados):

| Servicio | Host | Contenedor | Cómo cambiarlo |
| --- | --- | --- | --- |
| Aplicación web | `8090` | `80` | `OBRAOS_APP_PORT=9000 docker compose up -d` |
| MySQL | `3307` | `3306` | `OBRAOS_DB_PORT=3310 docker compose up -d` |

Credenciales de MySQL: base `obras`, usuario `obras`, contraseña `secret`, root `root`. Desde el host se accede en `127.0.0.1:3307`.

### Comandos útiles

```bash
docker compose ps                       # estado de los servicios
docker compose logs -f app              # logs de Laravel / PHP
docker compose logs -f web              # logs de Nginx
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec db mysql -uobras -psecret obras
docker compose restart app              # reiniciar PHP
docker compose down                     # detener (conserva los datos)
docker compose down -v                  # detener y borrar la base de datos
```

### Recompilar tras cambios

La imagen no monta el código (es inmutable), así que después de cambiar PHP, CSS o JS:

```bash
docker compose up -d --build
```

### Variables de entorno

El compose usa el prefijo `OBRAOS_` para no chocar con el `.env` local de Laravel:

| Variable | Por defecto | Descripción |
| --- | --- | --- |
| `OBRAOS_APP_PORT` | `8090` | Puerto web en el host |
| `OBRAOS_DB_PORT` | `3307` | Puerto de MySQL en el host |
| `OBRAOS_DB_DATABASE` | `obras` | Nombre de la base de datos |
| `OBRAOS_DB_USERNAME` | `obras` | Usuario de la base de datos |
| `OBRAOS_DB_PASSWORD` | `secret` | Contraseña de la base de datos |
| `OBRAOS_DB_ROOT_PASSWORD` | `root` | Contraseña de root |
| `OBRAOS_SEED_DEMO` | `false` | Carga los datos demo al iniciar |
| `OBRAOS_APP_ENV` | `local` | Entorno de Laravel |
| `OBRAOS_APP_DEBUG` | `true` | Modo depuración |

### Solución de problemas

- **`port is already allocated`**: cambia `OBRAOS_APP_PORT` o `OBRAOS_DB_PORT`.
- **MySQL reiniciándose**: revisa `docker compose logs db`. Si cambiaste credenciales después del primer arranque, ejecuta `docker compose down -v` para reinicializar la base.
- **Los cambios no aparecen**: reconstruye con `docker compose up -d --build`.
- **Error de permisos en `storage`**: `docker compose exec app chown -R www-data:www-data storage bootstrap/cache`.

> El `docker-compose.yml` está pensado para desarrollo y demostración. Para producción revisa la sección [Despliegue](#-despliegue).

---

## 🚀 Instalación

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repositorio> obras
cd obras
composer install
npm install
```

### 2. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus datos de base de datos. Ejemplo con MySQL (Laragon):

```dotenv
APP_NAME=ObraOS
APP_URL=http://obras.test

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=obras
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrar y poblar datos de demostración (opcional)

```bash
php artisan migrate

# Datos de ejemplo: empresa, usuario demo, proveedores, materiales, equipos, mano de obra y APUs
php artisan db:seed --class=DemoObraSeeder
```

### 4. Compilar assets y levantar el servidor

```bash
npm run build      # producción
# o, durante el desarrollo:
npm run dev

php artisan serve
```

El atajo incluido `composer setup` ejecuta instalación, migración, `npm install` y `npm run build` de una sola vez.

---

## 🔑 Usuario de demostración

Si ejecutaste `DemoObraSeeder`:

| Campo | Valor |
| --- | --- |
| Empresa | Constructora Jesu S.A.S. |
| Correo | `jesu@obras.com` |
| Contraseña | `password123` |
| Rol | `propietario` |

También puedes **registrarte** desde `/registro` para crear una empresa nueva desde cero.

---

## 📸 Capturas

> Guarda las imágenes en `docs/screenshots/` y descomenta las líneas de abajo para mostrarlas en este README.

| Pantalla | Archivo sugerido |
| --- | --- |
| Inicio de sesión | `docs/screenshots/login.png` |
| Dashboard | `docs/screenshots/dashboard.png` |
| Editor de presupuesto | `docs/screenshots/presupuesto.png` |
| Exportación a Excel | `docs/screenshots/excel.png` |

<!--
![Inicio de sesión](docs/screenshots/login.png)
![Dashboard](docs/screenshots/dashboard.png)
![Editor de presupuesto](docs/screenshots/presupuesto.png)
![Exportación a Excel](docs/screenshots/excel.png)
-->

---

## 🗺️ Rutas principales

| Ruta | Descripción |
| --- | --- |
| `/` | Redirige al dashboard o al login |
| `/login`, `/registro` | Autenticación |
| `/dashboard` | Panel con KPIs y proyectos recientes |
| `/catalogos/proveedores` | Catálogo de proveedores |
| `/catalogos/materiales` | Catálogo de materiales |
| `/catalogos/equipos` | Catálogo de equipos |
| `/catalogos/mano-de-obra` | Catálogo de mano de obra |
| `/catalogos/plantilla/{modulo}` | Descarga la plantilla Excel de importación |
| `/apus` | Listado de APUs |
| `/apus/{apu}/editar` | Editor de un APU |
| `/proyectos` | Listado de proyectos |
| `/proyectos/{proyecto}/editar` | Editor del presupuesto |
| `/proyectos/{proyecto}/exportar` | Descarga del presupuesto en Excel |

---

## 🧮 Cómo se calcula un presupuesto

Las fórmulas viven en `app/Services/PresupuestoService.php` y reproducen la hoja de Excel del usuario:

```
VR. UNITARIO = costo directo del APU   (el AIU NO va dentro del precio unitario)
VR. TOTAL    = cantidad × vr_unitario
DURACIÓN     = cantidad × días_por_unidad de la cuadrilla del APU

Subtotal       = Σ vr_total                        (= costo directo del proyecto)
Administración = subtotal × pct_administracion %
Imprevistos    = subtotal × pct_imprevistos %
Utilidad       = subtotal × pct_utilidad %
TOTAL          = subtotal + administración + imprevistos + utilidad
```

Los porcentajes de AIU se toman por proyecto (con los valores por defecto de la empresa). El costo de mano de obra incluye prestaciones según `pct_prestaciones` de la empresa (`app/Services/ApuCalculator.php`).

---

## 🧩 Cómo se construye un APU

Un **APU (Análisis de Precios Unitarios)** responde una sola pregunta: *¿cuánto cuesta producir **una** unidad de esta actividad?* No incluye AIU; el AIU se aplica después, a nivel de presupuesto.

### 1. Definir la actividad

- **Descripción**: `CONCRETO DE 3000 PSI EN ZAPATAS`
- **Unidad**: `M3`
- **Cuadrilla y rendimiento**: quiénes trabajan y en cuánto tiempo hacen 1 unidad (`días_por_unidad`, `cuadrilla`).

### 2. Materiales (A)

Por cada insumo:

```
total = cantidad_por_unidad × (1 + desperdicio%) × precio_unitario
```

El desperdicio se maneja por línea (ej. 5 % en cemento, 10 % en arena).

### 3. Mano de obra (B)

```
jornal_con_prestaciones = jornal × (1 + pct_prestaciones / 100)
total                   = jornal_con_prestaciones × días_por_unidad
```

Las prestaciones salen del porcentaje configurado en la empresa (ej. 85 %), no se digitan por APU.

### 4. Equipos (C)

```
tarifa_hora = tarifa_día / 8
total       = tarifa_hora × horas_por_unidad
```

Además, las **herramientas menores** se calculan como un porcentaje de la mano de obra (10 % por defecto):

```
herramientas = total_mano_obra × pct_herramientas / 100
```

### 5. Costo directo

```
COSTO DIRECTO = A + B + C (equipos + herramientas)
```

### Ejemplo real (hoja del ingeniero)

`CONCRETO DE 3000 PSI EN ZAPATAS` — 1 M3:

| Componente | Detalle | Total |
| --- | --- | ---: |
| A. Materiales | Cemento 350 KG × 530 · Agua 0,18 M3 × 1.840 · Grueso 0,84 M3 × 82.048 · Fino 0,56 M3 × 64.191 | 290.698,48 |
| B. Mano de obra | Ayudante: 30.284 × 1,85 × 0,89 | 49.862,61 |
| C. Equipos | Herramientas 10 % de la MO (4.986,26) + Trompo (49.990 / 8) × 0,5 h (3.124,38) | 8.110,64 |
| **Costo directo** | | **348.671,72** |

En la app este cálculo es automático y se recalcula al cambiar cualquier precio del catálogo, cantidad, rendimiento o porcentaje de prestaciones. El editor de APU está en `/apus/{apu}/editar` y las fórmulas viven en `app/Services/ApuCalculator.php`.

---

## 📥 Importación masiva desde Excel

Cada catálogo tiene un botón **Subir Excel** en la parte superior. El flujo es:

1. Descargar la plantilla del catálogo (botón *Descargar plantilla*).
2. Llenar una fila por registro (la plantilla ya trae un ejemplo).
3. Subir el archivo y revisar el reporte: **creados**, **actualizados** y **errores por fila**.

### Formatos y columnas

| Catálogo | Archivo | Columnas (la llave en negrita) | Llave de actualización |
| --- | --- | --- | --- |
| Proveedores | `.xlsx`, `.xls`, `.csv` | **NOMBRE**, CONTACTO, TELEFONO, EMAIL, DIRECCION | `nombre` |
| Materiales | `.xlsx`, `.xls`, `.csv` | **CODIGO**, DESCRIPCION, UNIDAD, PRECIO, PROVEEDOR | `codigo` |
| Equipos | `.xlsx`, `.xls`, `.csv` | **CODIGO**, DESCRIPCION, TIPO, TARIFA_DIA, PROVEEDOR | `codigo` |
| Mano de obra | `.xlsx`, `.xls`, `.csv` | **CARGO**, JORNAL, DESCRIPCION | `nombre` |

### Reglas

- Se aceptan columnas en **cualquier orden**, columnas extra y encabezados con tildes o alias (`PRECIO UNITARIO`, `VALOR`, `TARIFA DÍA`, `SALARIO`, `CÉLULAR`, etc.).
- Se leen números en formato colombiano (`28.500,50`, `$ 45.000`) y también `28,500.50`.
- Si la llave ya existe **en la misma empresa**, se actualiza; nunca se toca data de otra empresa.
- En una actualización, **una celda vacía no borra** el dato existente.
- El proveedor debe existir antes de importar materiales o equipos.
- Los errores se reportan por número de fila y no detienen el resto (máximo 5.000 filas y 10 MB por archivo).
- Todo el motor está en `app/Services/ImportacionExcelService.php`; las plantillas se generan en `app/Http/Controllers/PlantillaController.php` a partir de `config/importaciones.php`.

---

## 🏢 Multi-empresa

Todos los modelos de negocio (`Proyecto`, `Apu`, `Material`, `Equipo`, `CargoMo`, `Proveedor`) usan el trait `App\Models\Concerns\BelongsToEmpresa`, que:

1. Aplica un **scope global** filtrando por `empresa_id` del usuario autenticado.
2. **Asigna automáticamente** `empresa_id` al crear registros.

Esto garantiza que una empresa nunca vea datos de otra, sin filtros manuales en cada consulta.

---

## 🧪 Tests

```bash
php artisan test
# o
composer test
```

La suite cubre autenticación, catálogos, APUs, cálculo de presupuestos, exportación a Excel y aislamiento entre empresas.

Formatear el código con Laravel Pint:

```bash
./vendor/bin/pint
```

---

## 📁 Estructura relevante

```
app/
├── Http/Controllers/ExportController.php   # Descarga del .xlsx
├── Livewire/                                # Componentes (Auth, Catalogos, Apus, Proyectos)
├── Models/                                  # Eloquent + trait BelongsToEmpresa
└── Services/
    ├── ApuCalculator.php                    # Costo de un APU
    ├── PresupuestoService.php               # Cálculo del presupuesto y AIU
    └── ExcelExportService.php               # Generación del libro Excel

database/
├── migrations/                              # Esquema completo
└── seeders/DemoObraSeeder.php               # Datos de ejemplo

resources/
├── css/app.css                              # Tailwind + clase .campo
├── js/app.js                                # Utilidades front (descarga Excel)
└── views/
    ├── dashboard.blade.php
    ├── components/                          # Layout, formularios y modales
    └── livewire/                            # Vistas de cada componente

docker/
├── entrypoint.sh                            # Espera MySQL, genera key y migra
├── nginx/default.conf                       # Configuración de Nginx
└── php/php.ini                              # Límites de PHP y OPcache

Dockerfile                                   # Build multi-stage (assets, vendor, app, web)
docker-compose.yml                           # Servicios app + web + db
```

---

## 🛣️ Próximas fases

- Cronograma de obra.
- Órdenes de compra y control de ejecución.
- Reportes de avance y comparativos presupuesto vs. real.
- Importación/exportación masiva de catálogos.

---

## 🚢 Despliegue

### Docker Compose en un servidor

El `docker-compose.yml` incluido sirve para demo y desarrollo. Para un servidor:

1. Clona la rama `feature/docker` (o el resultado de una integración explícita y revisada).
2. Crea un archivo `.env` junto al compose con valores seguros (no uses los valores por defecto):

```dotenv
OBRAOS_APP_ENV=production
OBRAOS_APP_DEBUG=false
OBRAOS_APP_PORT=80
OBRAOS_DB_PASSWORD=<contraseña-fuerte>
OBRAOS_DB_ROOT_PASSWORD=<contraseña-fuerte>
OBRAOS_SEED_DEMO=false
```

3. Levanta los servicios:

```bash
docker compose up -d --build
```

4. Coloca un proxy inverso con HTTPS (Nginx, Caddy o Traefik) delante del puerto de la aplicación.

### Checklist de producción

- [ ] `APP_ENV=production` y `APP_DEBUG=false`.
- [ ] Contraseñas fuertes y respaldos de la base (`docker compose exec db mysqldump -uobras -p obras > respaldo.sql`).
- [ ] HTTPS obligatorio.
- [ ] `APP_URL` con el dominio real.
- [ ] Correo real (`MAIL_MAILER=smtp` en lugar de `log`).
- [ ] Volúmenes `dbdata` y `storage` respaldados.

### Hosting administrado

También funciona en cualquier hosting con PHP 8.2+, MySQL y Node para compilar los assets (ver [Instalación](#-instalación)).

---

## 📄 Licencia

Proyecto privado. Todos los derechos reservados.
