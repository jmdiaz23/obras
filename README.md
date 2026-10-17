# ObraOS

**ObraOS** es una aplicación web para constructoras y presupuestistas que centraliza la **elaboración de presupuestos de obra** con memoria de cálculo, análisis de precios unitarios (APU) y catálogos de costos, todo aislado por empresa (multi-tenant).

El sistema reproduce las fórmulas de la hoja de cálculo tradicional de presupuestos ("PRESUPUESTO GENERAL", "MEMORIA DE CALCULO", etc.) y permite **exportar el libro completo a Excel** con el mismo formato que usa el usuario.

---

## ✨ Funcionalidades

- **Autenticación y registro por empresa**: cada empresa crea su cuenta y sus catálogos quedan aislados del resto.
- **Dashboard** con KPIs reales de la empresa (proyectos, APUs, materiales, equipos, mano de obra, proveedores), proyectos recientes y configuración del AIU.
- **Catálogos de costos**:
  - Proveedores
  - Materiales (con precio por unidad y proveedor)
  - Equipos (alquiler, tipo)
  - Mano de obra (cargos, jornal, prestaciones)
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
```

---

## 🛣️ Próximas fases

- Cronograma de obra.
- Órdenes de compra y control de ejecución.
- Reportes de avance y comparativos presupuesto vs. real.
- Importación/exportación masiva de catálogos.

---

## 📄 Licencia

Proyecto privado. Todos los derechos reservados.
