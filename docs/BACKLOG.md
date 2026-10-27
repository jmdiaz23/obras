# Backlog de ObraOS

> Prioridad: **P0** = bloquea la demo o el uso real · **P1** = siguiente versión · **P2** = exploración.
> Tamaño: **S** ≤ 0,5 día · **M** = 1–2 días · **L** = 3–5 días.
> Última actualización: 26 de octubre de 2026.

## Definición de "terminado"

Una tarea está terminada cuando: cumple su criterio de aceptación, tiene prueba automatizada si toca cálculo, `php artisan test` está en verde, la interfaz funciona en escritorio y móvil, y está documentada (README o `docs/`).

---

## 1. Hecho ✅

| ID | Tarea | Evidencia |
|---|---|---|
| H-01 | Autenticación, alta de empresas por super-admin y multiempresa | `AuthTest`, `AdminTest`, scope `BelongsToEmpresa` |
| H-02 | Catálogos de proveedores, materiales, equipos y mano de obra | `CatalogosTest` |
| H-03 | Importación masiva Excel/CSV con plantilla, alias y reporte | `ImportacionExcelTest` (9 pruebas) |
| H-04 | Motor APU (materiales, mano de obra, equipos, herramientas) | `ApuTest` reproduce el concreto 3000 psi |
| H-05 | Presupuesto por capítulos con AIU y duración | `ProyectoTest` |
| H-06 | Exportación completa a Excel | `ExcelExportTest` |
| H-07 | Título de capítulos persistido | migración `2026_10_22_000001` |
| H-08 | Seeder demo idempotente con 3 APUs y proyecto | `DemoSeederTest` |
| H-09 | Docker (web 8090, MySQL 3307) y README | `docker-compose.yml` |
| H-10 | Guion de sustentación | `docs/SUSTENTACION.md` |
| H-11 | Equipos por unidad de obra (`hr`, `día`, `m²`, `m³`, `und`) con valor unitario | `ApuTest` (3 pruebas nuevas), importación y exportación |
| H-12 | Subtotal por capítulo en editor y Excel | `ProyectoTest`, `ExcelExportTest` |
| H-13 | IVA opcional sobre la utilidad (0 % por defecto) | `ProyectoTest`, `ExcelExportTest` |
| H-14 | **Etapas de obra** (`proyecto_etapas`) con asignación capítulo → ítems | `ProyectoTest`; migración `2026_10_26_000003` |
| H-15 | **Cantidad ejecutada** por ítem y persistencia inmediata | `ProyectoTest`; editor de proyecto |
| H-16 | **`AvanceService`**: valor ganado, avance financiero y físico por unidad | `ProyectoTest`, `DemoSeederTest` |
| H-17 | UI de avance: resumen en editor, gestión de etapas, vista "Avance" y tarjeta en dashboard | `ProyectoTest` |
| H-18 | Exportación de avance: columnas ejecutado/% y hoja `AVANCE DE OBRA` | `ExcelExportTest` |
| H-19 | Regresión y documentación de etapas/avance | Suite completa y README/`docs/` al día |
| H-20 | **Super-admin de plataforma** y registro público deshabilitado | Panel `/admin`, `AdminTest`, comando `obraos:superadmin` |

**Estado de pruebas:** 66 pruebas / 312 aserciones.

---

## 2. P0 · Ahora (cierra la promesa al ingeniero)

| ID | Tarea | Tamaño | Responsable | Criterio de aceptación |
|---|---|---|---|---|
| P0-09 | **Historial de precios de materiales** y aviso de impacto en APUs. | M | Tú | Cambiar cemento guarda fecha y muestra qué APUs se afectaron. |

**Salida de P0:** demo con etapas, avance y equipos por m²/m³ frente al ingeniero.

---

## 3. P1 · Siguiente versión

| ID | Tarea | Tamaño | Criterio |
|---|---|---|---|
| P1-01 | Transportes `m³-km × tarifa` por APU | M | El APU suma una sección de transporte como en el Excel del ingeniero. |
| P1-02 | Costos indirectos dentro del APU (administración/imprevistos/utilidad) | M | Se puede replicar el APU con 30 % de administración. |
| P1-03 | Importar **APU completos** desde Excel (hojas `APU x.y`) | L | Un archivo de 20 APUs se importa con materiales, MO y equipos. |
| P1-04 | **Regionalizador propio**: factores por provincia sobre la lista del usuario | L | "¿Cuánto vale mi APU en otra provincia?" sin usar contenido del INVÍAS. |
| P1-05 | Roles `admin` / `residente` / `lector` e invitaciones | M | Un lector no puede editar precios. |
| P1-06 | Respaldo automático y botón de restauración | M | Restauración probada en un ambiente limpio. |
| P1-07 | Exportación a PDF del presupuesto y del APU | M | PDF con la misma estructura del Excel. |
| P1-08 | Importación en segundo plano (colas) para archivos grandes | M | 5 000 filas se procesan sin timeout. |
| P1-09 | Auditoría de cambios (quién y cuándo) | M | Historial visible por registro. |
| P1-10 | `GET /api/v1` de solo lectura con Sanctum | M | Proyectos, APUs, etapas y avance en JSON aislados por empresa. |
| P1-11 | Histórico de reportes de avance (fecha, usuario, cantidad y nota) | M | Cada reporte queda auditado; el avance vigente es el último reporte válido. |

---

## 4. P2 · Exploración

| ID | Tarea | Tamaño | Nota |
|---|---|---|---|
| P2-01 | `POST/PATCH /api/v1` para registrar avance desde móvil | M | Requiere P1-10. |
| P2-02 | Portal del cliente en Next.js (avance, fotos, actas) | L | Consume la API; no reescribe el front actual. |
| P2-03 | Curva S: programado vs. ejecutado por semana | M | Sobre etapas y avance. |
| P2-04 | App móvil/PWA para reportar avance en obra | L | Evalúa necesidad real de offline. |
| P2-05 | Facturación y planes del SaaS | M | Pasarela de pagos colombiana. |
| P2-06 | Índice propio de precios por región (datos anonimizados) | L | Activo defendible a largo plazo. |
| P2-07 | Multi-sucursal y centros de costo | M | Solo si lo piden clientes. |
| P2-08 | Integración con licitaciones (SECOP) | L | Explorar demanda antes. |

---

## 5. Deuda técnica

| ID | Tarea | Prioridad |
|---|---|---|
| D-01 | Restricción única `(proyecto_id, item)` en `proyecto_apu` | P1 |
| D-02 | Casts `decimal` en modelos para evitar flotantes en dinero | P1 |
| D-03 | Mover importación y exportación grandes a colas | P1 |
| D-04 | CI (GitHub Actions) con tests + Pint | P1 |
| D-05 | Índices para búsqueda por descripción en catálogos | P2 |
| D-06 | Cobertura de pruebas del dominio > 70 % | P1 |

---

## 6. Riesgos abiertos

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Redistribuir APU del INVÍAS | Legal | Importación por el usuario + regionalizador propio (ver RNF-10). |
| Cambios de precio sin trazabilidad | Pérdida de confianza | P0-09 historial de precios. |
| Un solo desarrollador | Velocidad | API-first para que el compañero trabaje en paralelo. |
| Complejidad de avance (unidades mixtas) | Datos engañosos | Avance financiero como indicador principal; físico por capítulo. |
