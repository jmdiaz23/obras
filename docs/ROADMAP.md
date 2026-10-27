# Roadmap de ObraOS

> De "Excel que se descuadra" a motor de presupuestos con avance de obra.
> Última actualización: 26 de octubre de 2026.

## Norte

**Que un ingeniero colombiano arme, revise y sustente un presupuesto sin volver a Excel, y que pueda mostrarle al cliente cuánto lleva ejecutado.**

El diferencial no es generar archivos: es que **el número se mantiene correcto** cuando cambian los precios, y que el **avance sale del mismo presupuesto**.

---

## Horizonte 1 · Cerrar el producto mínimo vendible (0–2 meses)

**Objetivo:** demo sólida con un ingeniero real y 3–5 usuarios piloto.

| Entregable | Estado |
|---|---|
| Catálogos + importación masiva | ✅ |
| Motor APU con costo directo | ✅ |
| Presupuesto por capítulos + AIU | ✅ |
| Exportación Excel | ✅ |
| **Equipos por unidad de obra** | ✅ |
| Subtotales por capítulo · IVA sobre utilidad | ✅ |
| **Etapas + avance físico/financiero** | ✅ |
| Super-admin de plataforma y alta por invitación | ✅ |
| Historial de precios | ⏳ |
| Documentación de requisitos y backlog | ✅ |

**Métricas de salida**
- Un presupuesto real de 200+ ítems armado y exportado en ObraOS.
- El ingeniero encuentra una diferencia de precio antes de que se la pregunten.
- Al menos 3 usuarios usan el sistema una semana seguida.

**Riesgos:** sobre-ingeniería antes de validar; cambios de alcance por cada reunión.

---

## Horizonte 2 · Primeros clientes pagos (3–6 meses)

**Objetivo:** 10–20 empresas usando ObraOS y pagando.

| Foco | Entregable |
|---|---|
| Confianza | Historial de precios, auditoría, respaldos automáticos |
| Alcance técnico | Transportes, costos indirectos por APU, importación de APU completos |
| Colaboración | Roles (admin/residente/lector) e invitaciones |
| Integración | `GET /api/v1` con Sanctum → portal de avance en Next.js |
| Comercial | Planes freemium / pago, onboarding guiado, soporte |

**Métricas**
- 10+ empresas activas y 30 % de retención al mes 3.
- ≥ 40 % de los usuarios exportan al menos un proyecto por semana.
- NPS ≥ 40 en los pilotos.

**Riesgos:** el precio del SaaS vs. el "gratis" de Excel/IA; soporte consumiendo todo el tiempo; dependencia de un solo desarrollador.

---

## Horizonte 3 · Escala y defensa (6–12 meses)

**Objetivo:** producto con datos propios y dos frentes (ingeniero y cliente final).

| Foco | Entregable |
|---|---|
| Producto | Curva S, programación semanal, PDF, portal del cliente |
| Datos | Índice propio de precios por región con datos anonimizados |
| Movilidad | PWA/móvil para reportar avance en obra |
| Plataforma | API de escritura, integraciones, multi-sucursal |
| Negocio | Facturación electrónica del SaaS, planes por volumen |

**Métricas**
- 100 empresas y 10 000 APUs sin cambio de arquitectura.
- Ingreso recurrente mensual positivo y creciente.
- Índice de precios usado como referencia por al menos 20 empresas.

---

## Decisiones de arquitectura

| Decisión | Estado | Motivo |
|---|---|---|
| Laravel 12 + Livewire 4 | ✅ vigente | Máxima velocidad de iteración para una herramienta interna. |
| No reescribir el front en React ahora | ✅ | 4–6 semanas sin features nuevas; riesgo alto. |
| API-first por capas (primero lectura) | ⏳ | Da trabajo al compañero sin tocar el motor. |
| Portal Next.js separado | ⏳ | Consume la API; no duplica el editor de APUs. |
| Monolito con MySQL | ✅ | Escala de sobra para 100 empresas; menos costo operativo. |

---

## Qué no vamos a hacer (por ahora)

- Contabilidad, nómina, inventario o compras.
- BIM o mediciones desde planos.
- Embeber o revender los APU del INVÍAS.
- App nativa antes de validar la web.
- Microservicios o Kubernetes.
