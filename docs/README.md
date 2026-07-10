# Documentación de SISBIO

Sistema de administración de equipos biométricos ZKTeco (Gobierno del Beni).

## Bitácora de sesiones de trabajo

Cada día de trabajo se registra en un archivo dentro de la carpeta del mes:
`docs/sesiones/MM-AAAA/AAAA-MM-DD.md` (por ejemplo `docs/sesiones/07-2026/2026-07-10.md`).

### Cómo registrar una sesión

1. Copiar `docs/sesiones/_plantilla.md` a la carpeta del mes (`docs/sesiones/MM-AAAA/`).
2. Renombrarlo a la fecha del día (`AAAA-MM-DD.md`).
3. Completar cada bloque `Trabajo N` (commit, problema, archivos, solución) y el
   informe de presentación. Borrar los bloques que no apliquen.

### Convención

- Un archivo por día, agrupado por mes en `MM-AAAA/`.
- Un bloque `## Trabajo N` por tarea, en orden cronológico.
- Enlazar el commit relevante en cada bloque cuando exista.
