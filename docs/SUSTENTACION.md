# Sustentación de ObraOS ante un ingeniero civil

> **Mensaje central:** ObraOS no es un CRUD de catálogos; es un **motor de costos**
> que convierte tu Excel de presupuestos en un sistema vivo: cambias un precio
> y todos tus APU, proyectos y exportaciones se recalculan solos.

---

## 1. Guion de 7 minutos

### 0:00 – 0:40 · El gancho (no abras con software)

> "Ingeniero, usted hoy arma sus precios unitarios en Excel: una hoja por APU,
> otra para la lista de precios, otra para el AIU. Cuando cambia el precio del
> cemento, tiene que abrir 40 hojas a corregir a mano y rezar para que no se
> quede ninguna. ObraOS hace exactamente ese trabajo, pero sin las 40 hojas."

No digas "tiene CRUD de materiales". Di: **"centraliza los costos y propaga los cambios"**.

### 0:40 – 1:30 · Dashboard: la obra en una pantalla

- Muestra los KPIs de **su** empresa: proyectos, APUs, materiales, equipos, mano de obra.
- Señala los proyectos recientes y el AIU configurado.
- Frase: *"Esto no es un listado; es el estado actual de sus costos."*

### 1:30 – 3:00 · Catálogos + importación masiva (la novedad)

1. Entra a **Materiales**.
2. Muestra precio, unidad y proveedor: *"la lista de precios de la obra"*.
3. Clic en **Subir Excel** → **Descargar plantilla**.
   - *"La plantilla ya viene con las columnas y un ejemplo. No tiene que adaptar su Excel a mí; yo me adapto a su Excel."*
4. Sube un archivo con 3–5 insumos (puede ser el que preparaste).
5. Muestra el reporte: **creados / actualizados / errores por fila**.
6. Abre una fila actualizada y muestra el precio nuevo.

> "Cargar 500 insumos a mano son dos días. Aquí son treinta segundos, y si una
> fila viene mal, me dice exactamente en qué fila y por qué, sin botar el resto."

Menciona de pasada: acepta `.xlsx`, `.xls`, `.csv`, columnas en cualquier orden,
tildes, alias (`VALOR`, `TARIFA DÍA`, `SALARIO`) y formato colombiano (`28.500,50`).

### 3:00 – 5:00 · El APU: el corazón del sistema

Abre un APU (ideal: **CONCRETO DE 3000 PSI EN ZAPATAS**, unidad M3).

> "Un APU responde una sola pregunta: **¿cuánto me cuesta producir una unidad?**
> En este caso, un metro cúbico de concreto de 3000 psi."

Recorre las tres secciones:

- **A. Materiales:** cantidad por unidad × (1 + desperdicio) × precio.
- **B. Mano de obra:** jornal × (1 + prestaciones) × días por unidad.
- **C. Equipos y herramientas:** (tarifa día ÷ 8) × horas + % de herramientas sobre la MO.

Cierra con el número:

> "Costo directo: **$348.671,72 por M3**. Sin AIU. El AIU no va aquí; va en el
> presupuesto, porque la administración, los imprevistos y la utilidad son del
> proyecto, no de la actividad."

**El momento clave:** cambia el precio del cemento en el catálogo y vuelve al APU.

> "¿Ve? No toqué ninguna fórmula. El APU se recalculó solo. Eso es lo que su
> Excel no puede hacer sin abrir hoja por hoja."

### 5:00 – 6:15 · Presupuesto y AIU

- Entra al proyecto, muestra capítulos e ítems con cantidades.
- Explica: `VR TOTAL = cantidad × vr unitario`; el AIU se aplica sobre el subtotal.
- Cambia una cantidad y muestra cómo se mueve el total.

### 6:15 – 7:00 · Exportación y cierre

- Exporta el proyecto a Excel.
- Muestra las hojas: `LISTA DE PRECIOS`, `ALQUILER DE EQUIPOS`, `RENDIMIENTO`,
  una hoja por APU, `MEMORIA DE CALCULO`, `PRESUPUESTO GENERAL`,
  `TABLA DE TAREAS` y `EJE UNIVERSAL`.
- Cierre:

> "Usted no tiene que dejar su Excel. ObraOS le entrega el mismo libro que ya
> conoce, pero construido desde una base de costos viva. Su trabajo deja de ser
> repetir fórmulas y vuelve a ser analizar la obra."

---

## 2. Cómo explicar el APU en lenguaje de obra

### Analogía

> "Un APU es la receta de un plato. Para hacer un metro cúbico de concreto
> necesito tantos kilos de cemento, tantos metros de arena, un ayudante durante
> tantos días y un trompo durante tantas horas. La receta me dice el costo de
> **una** porción. El presupuesto es multiplicar esa porción por lo que voy a
> construir."

### Las tres canastas

| Canasta | Qué entra | Fórmula |
| --- | --- | --- |
| **A. Materiales** | cemento, arena, agua, acero… | `cantidad × (1 + desperdicio%) × precio` |
| **B. Mano de obra** | oficial, ayudante, cuadrilla… | `jornal × (1 + prestaciones%) × días_por_unidad` |
| **C. Equipos** | trompo, vibrador, retro… | `(tarifa_día ÷ 8) × horas_por_unidad` |
| **Herramientas** | palas, baldes, menor | `total_mano_obra × %herramientas` |

```
COSTO DIRECTO = A + B + C + herramientas
```

### Ejemplo para mostrar en pantalla (concreto 3000 psi, 1 M3)

| Componente | Detalle | Total |
| --- | --- | ---: |
| Materiales | Cemento 350 KG × 530 · Agua 0,18 M3 × 1.840 · Grueso 0,84 M3 × 82.048 · Fino 0,56 M3 × 64.191 | 290.698,48 |
| Mano de obra | Ayudante 30.284 × 1,85 × 0,89 | 49.862,61 |
| Equipos | Herramientas 10 % de la MO + Trompo (49.990 ÷ 8) × 0,5 h | 8.110,64 |
| **Costo directo** | | **348.671,72** |

Nunca digas "el sistema calcula magicamente". Di: **"reproduce exactamente la
hoja del ingeniero; lo validamos contra su Excel"**. (Hay tests que comparan
centavo a centavo.)

### Cómo se genera en la app

1. **Catálogos** (`/catalogos/...`): precios, jornales y tarifas una sola vez.
2. **Nuevo APU** (`/apus`): descripción, unidad, cuadrilla, rendimiento y % herramientas.
3. **Editor del APU**: agrega líneas de material, mano de obra y equipo.
4. El sistema calcula en vivo con `ApuCalculator` y guarda el APU.
5. **Proyecto** (`/proyectos`): agrega el APU como ítem, define cantidad y AIU.
6. **Exportar**: genera el libro Excel con la memoria de cálculo.

---

## 3. Preguntas probables (y respuestas)

**"¿Esto reemplaza mi Excel?"**
> "No. Lo alimenta. Usted puede seguir exportando el mismo libro; la diferencia
> es que ya no lo arma a mano."

**"¿Puedo cargar mi lista de precios actual?"**
> "Sí. Descarga la plantilla o sube su archivo: aceptamos cualquier orden de
> columnas y formatos de número. Lo que no reconozcamos se lo decimos por fila."

**"¿Y si cambian los precios a mitad de obra?"**
> "Actualiza el catálogo (a mano o por Excel) y todos los APU y presupuestos que
> lo usan se recalculan. No hay copias congeladas."

**"¿Maneja prestaciones sociales?"**
> "Sí. El porcentaje es de la empresa (por ejemplo 85 %) y se aplica al jornal
> en cada APU. No se digita cargo por cargo."

**"¿El AIU va dentro del precio unitario?"**
> "No. El precio unitario es costo directo. El AIU (administración, imprevistos,
> utilidad) se aplica sobre el subtotal del proyecto, con los porcentajes que
> usted defina."

**"¿Varios usuarios o varias empresas?"**
> "Cada empresa tiene sus catálogos y proyectos aislados. Un usuario nunca ve
> datos de otra empresa."

**"¿Qué pasa si me equivoco en una fila?"**
> "La importación reporta la fila y el motivo, y sigue con las demás. Nada se
> guarda a medias en esa fila."

**"¿Es difícil de implementar en mi empresa?"**
> "El flujo es el que ya usa: lista de precios → APU → presupuesto → Excel.
> Solo cambia dónde vive la información."

---

## 4. Qué evitar

- ❌ "Tiene un CRUD de proveedores." → ✅ "Centraliza y valida la lista de precios."
- ❌ "Es un sistema web moderno." → ✅ "Elimina el retrabajo de fórmulas."
- ❌ "Calcula automáticamente." → ✅ "Reproduce y propaga su propia metodología."
- ❌ Mostrar código o base de datos. → ✅ Mostrar el antes/después del Excel.
- ❌ Prometer funciones que no existen (roles avanzados, contabilidad, app móvil).

---

## 5. Checklist antes de la demo

- [ ] `npm run build` ejecutado (estilos al día).
- [ ] `php artisan migrate:fresh --seed` **solo si** no hay datos que conservar.
- [ ] Usuario demo: `jesu@obras.com` / `password123`.
- [ ] Archivo Excel de prueba listo en el escritorio (3–5 materiales).
- [ ] Ejecutar `docker compose exec app php artisan db:seed --class=DemoObraSeeder` (idempotente; crea APUs y proyecto si faltan).
- [ ] APU de concreto 3000 psi cargado (`APU-001`, $348.671,72/m³).
- [ ] Proyecto *Casa lote 12, Altos de la Sabana* con capítulos *Obra gruesa* y *Acabados*.
- [ ] Probar la descarga de plantilla y la exportación del proyecto.
- [ ] Tener a mano la tabla del ejemplo (sección 2) por si preguntan números.
