# Requisitos de ObraOS

> Motor de presupuestos de obra para constructoras colombianas.
> Versión 1.1 · Octubre 2026 · Rama `feature/etapas-avance`

## 1. Propósito y alcance

ObraOS convierte la lista de precios de una constructora en **APUs y presupuestos que se recalculan solos**. El alcance de esta versión cubre:

- Catálogos propios por empresa (proveedores, materiales, equipos y mano de obra).
- Importación masiva desde Excel y exportación del libro completo.
- Motor de Análisis de Precios Unitarios (APU) con costo directo.
- Presupuesto de proyecto por capítulos, con AIU.
- Etapas y avance físico/financiero del proyecto.

**Fuera de alcance (esta versión):** contabilidad, nómina, inventario de almacén, compras, facturación electrónica, licitaciones y BIM.

## 2. Actores

| Actor | Descripción | Estado |
|---|---|---|
| Propietario / Ingeniero | Administra su empresa, catálogos, APUs y proyectos. | ✅ |
| Residente / auxiliar | Consulta y edita bajo permisos del propietario. | ⏳ |
| Cliente del proyecto | Consulta el avance de su obra (portal). | ⏳ |
| Administrador ObraOS | Soporte y operación de la plataforma. | ⏳ |

## 3. Convenciones

- ✅ Implementado · 🟡 Parcial · ⏳ Planeado
- **RF**: requisito funcional · **RNF**: requisito no funcional
- Todo requisito tiene criterio de aceptación verificable.

---

## 4. Requisitos funcionales

### RF-01 · Autenticación y empresas ✅

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-01.1 | Alta de empresa con su propietario | El super-admin crea la empresa y el usuario `propietario` en una sola transacción. El registro público está deshabilitado. |
| RF-01.2 | Inicio y cierre de sesión | Credenciales válidas → panel de empresa o `/admin`; inválidas → error sin revelar si el correo existe. |
| RF-01.3 | Aislamiento por empresa | Ningún usuario puede leer, editar ni eliminar registros de otra empresa (probado con tests). El super-admin es la excepción controlada. |
| RF-01.4 | Roles y permisos | El propietario invita usuarios con rol `admin`, `residente` o `lector`. ⏳ |
| RF-01.5 | Super-admin de plataforma | Usuario sin empresa que puede crear y editar empresas, dar de alta propietarios y usuarios, y consultar métricas globales. No opera dentro de una empresa. |

### RF-02 · Catálogos ✅

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-02.1 | Proveedores | Nombre único por empresa; contacto, teléfono, correo y notas. |
| RF-02.2 | Materiales | Código único por empresa, descripción, unidad, precio y proveedor. |
| RF-02.3 | Equipos | Código único, descripción, tipo, tarifa por día y unidad de alquiler (`hr`, `día`, `m²`, `m³`, `und`). |
| RF-02.4 | Mano de obra | Nombre único por empresa y jornal diario. |
| RF-02.5 | Búsqueda y paginación | Filtrar por código o descripción sin recargar la página. |

### RF-03 · Importación masiva desde Excel ✅

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-03.1 | Formatos | Acepta `.xlsx`, `.xls` y `.csv`. |
| RF-03.2 | Plantilla descargable por módulo | Cada catálogo ofrece una plantilla con encabezados y ejemplo. |
| RF-03.3 | Columnas flexibles | Se acepta cualquier orden y alias razonables (`VALOR`, `PRECIO`, `TARIFA`, `UNIDAD DE MEDIDA`…). |
| RF-03.4 | Formatos numéricos | Interpreta `1.234,56` (CO) y `1,234.56` (US), y detecta el separador decimal. |
| RF-03.5 | Upsert | Materiales/equipos se actualizan por `código`; proveedores/cargos por `nombre`; siempre dentro de la empresa. |
| RF-03.6 | Celda vacía no borra | Al actualizar, una celda vacía conserva el valor existente. |
| RF-03.7 | Reporte por fila | Muestra creados, actualizados y errores con número de fila y motivo. |
| RF-03.8 | Sin efectos parciales | Si faltan columnas obligatorias, no se importa ninguna fila. |

### RF-04 · APU (Análisis de Precios Unitarios) 🟡

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-04.1 | Cabecera | Código único por empresa (opcional), actividad, unidad, cuadrilla, días por unidad, % herramientas y notas. |
| RF-04.2 | Materiales | Cantidad y % de desperdicio; el precio se toma del catálogo vivo. |
| RF-04.3 | Mano de obra | Cargo y días por unidad; el jornal se afecta por el % de prestaciones de la empresa. |
| RF-04.4 | Equipos | Unidad de alquiler por línea (`hr`, `día`, `m²`, `m³`, `und`) y cantidad en esa unidad. | ✅ |
| RF-04.5 | Cálculo en vivo | Al editar cualquier línea, el costo directo se recalcula sin guardar fórmulas. |
| RF-04.6 | Herramientas menores | Porcentaje configurable sobre la mano de obra. |
| RF-04.7 | Costo directo | `A + B + C` con 4 decimales, sin redondeos intermedios. |
| RF-04.8 | Costos indirectos por APU | Administración/imprevistos/utilidad dentro del APU. ⏳ |
| RF-04.9 | Transportes | Sección de acarreo `m³-km × tarifa`. ⏳ |

### RF-05 · Proyectos y presupuesto 🟡

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-05.1 | Cabecera | Nombre, cliente, ubicación y estado (`borrador`, `en ejecución`, `cerrado`). |
| RF-05.2 | Capítulos e ítems | Numeración jerárquica (`1`, `1.1`, `2`), orden y título del capítulo. |
| RF-05.3 | Ítems de APU | Cada ítem referencia un APU de la empresa y una cantidad. |
| RF-05.4 | AIU | Porcentajes configurables de administración, imprevistos y utilidad sobre el subtotal. |
| RF-05.5 | IVA sobre utilidad | Porcentaje opcional (por defecto 0 %); se calcula solo sobre la utilidad. | ✅ |
| RF-05.6 | Subtotal por capítulo | Cada capítulo muestra su total en el editor y en la exportación. | ✅ |
| RF-05.7 | Duración | Días estimados = cantidad × días por unidad del APU. |
| RF-05.8 | Totales | Subtotal, AIU y total con dos decimales. |

### RF-06 · Etapas y avance 🟡

| ID | Requisito | Criterio de aceptación | Estado |
|---|---|---|---|
| RF-06.1 | Etapas | El proyecto define etapas con nombre, orden y fechas; cada capítulo pertenece a una etapa. | ✅ |
| RF-06.2 | Cantidad ejecutada | Se registra por ítem. El histórico con fecha queda pendiente (P1-11). | 🟡 |
| RF-06.3 | Avance financiero | `Σ(cantidad ejecutada × valor unitario) / Σ(cantidad plan × valor unitario)` por etapa y proyecto. | ✅ |
| RF-06.4 | Avance físico | Por capítulo/etapa; nunca se suman unidades distintas. | ✅ |
| RF-06.5 | Visualización | Barras de progreso en el editor, vista de avance y dashboard. | ✅ |
| RF-06.6 | Exportación | El Excel incluye ejecutado y % de avance por etapa. | ✅ |

### RF-07 · Exportación a Excel ✅

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-07.1 | Libro completo | Hojas: `LISTA DE PRECIOS`, `ALQUILER DE EQUIPOS`, `RENDIMIENTO`, un APU por hoja, `MEMORIA DE CALCULO`, `PRESUPUESTO GENERAL`, `AVANCE DE OBRA`, `TABLA DE TAREAS`, `EJE UNIVERSAL`. |
| RF-07.2 | Fidelidad de cálculo | Los valores exportados coinciden con los de pantalla (2 decimales en dinero, 4 en costos unitarios). |
| RF-07.3 | Aislamiento | Solo se exportan datos de la empresa del usuario. |

### RF-08 · API para integraciones ⏳

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-08.1 | API de lectura `/api/v1` | Proyectos, presupuesto, APUs, etapas y avance en JSON. |
| RF-08.2 | Autenticación por token | Laravel Sanctum; cada token ligado a un usuario y su empresa. |
| RF-08.3 | Aislamiento | Un token solo accede a datos de su empresa. |
| RF-08.4 | API de escritura | Crear/editar ítems y registrar avance. ⏳ |

### RF-09 · Administración y datos ⏳

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-09.1 | Historial de precios | Cada cambio de precio de material guarda fecha y usuario; se puede ver el impacto en APUs. |
| RF-09.2 | Respaldos | Respaldo diario de base de datos con restauración probada. |
| RF-09.3 | Auditoría | Registro de quién cambió precios, ítems y avance. |
| RF-09.4 | Importación de APU completos | Desde el Excel del ingeniero (hojas por APU). ⏳ |

---

## 5. Requisitos no funcionales

| ID | Categoría | Requisito y criterio medible |
|---|---|---|
| RNF-01 | Rendimiento | Cálculo de un APU < 100 ms; presupuesto de 500 ítems < 200 ms en servidor; primera carga < 2 s en red local. |
| RNF-02 | Disponibilidad | 99,5 % mensual en producción; ventana de mantenimiento anunciada. |
| RNF-03 | Seguridad | OWASP Top 10 mitigado; contraseñas con hash; CSRF en formularios; rate limit en login; sesiones expiran. |
| RNF-04 | Aislamiento | 100 % de consultas filtradas por `empresa_id`; tests de fuga entre empresas en cada módulo. |
| RNF-05 | Exactitud | Sin redondeos intermedios; 4 decimales en APU y 2 en dinero; pruebas con los números del Excel del ingeniero. |
| RNF-06 | Respaldo y recuperación | RPO ≤ 24 h; RTO ≤ 4 h; restauración probada cada trimestre. |
| RNF-07 | Usabilidad | Español de Colombia; flujo "lista de precios → APU → presupuesto → Excel" en ≤ 3 clics por paso; usable en móvil para consulta. |
| RNF-08 | Accesibilidad | Objetivo WCAG 2.1 AA en formularios y navegación. |
| RNF-09 | Portabilidad | Docker Compose (PHP 8.3, MySQL 8, Nginx); despliegue en un comando. |
| RNF-10 | Legal | No se redistribuyen los APU del INVÍAS (propiedad de la entidad); el usuario importa sus referencias. Datos personales bajo Ley 1581 de 2012. |
| RNF-11 | Mantenibilidad | Cobertura de pruebas del dominio > 70 %; Laravel Pint; pruebas obligatorias en cada cambio de cálculo. |
| RNF-12 | Escalabilidad | 100 empresas y 10 000 APUs sin cambio de arquitectura; importaciones grandes procesadas fuera de la petición. |
| RNF-13 | Observabilidad | Logs estructurados, errores con contexto y alerta ante fallos de importación/exportación. |

---

## 6. Supuestos y restricciones

- El usuario es responsable de la veracidad de sus precios y rendimientos.
- Moneda única: peso colombiano (COP).
- Una empresa no comparte catálogos con otra.
- El sistema no reemplaza la revisión técnica del ingeniero (los APU de referencia son orientativos).
- Sin conexión a internet no hay operación (offline queda para el portal móvil futuro).

## 7. Trazabilidad

Cada RF y RNF se refleja en `docs/BACKLOG.md` con su tarea y en `docs/ROADMAP.md` con su horizonte. Todo cambio de cálculo debe venir con una prueba que reproduzca un caso real.
