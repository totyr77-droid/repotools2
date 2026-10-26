# Avisos exactos en tarjetas de palets — main y beta

Se reemplaza «por vencer o vencidos» por conteos separados: vencidos, vencen hoy y por vencer; singular/plural correctos y combinaciones con punto medio. Texto con tono rojo si hay vencidos, hoy diferenciado y aviso próximo para futuros. Conserva el conteo total previo y el plazo de aviso configurado por usuario. Fecha de corte según America/Buenos_Aires, misma convención de Stock. No se cambia unidad del conteo existente (filas/lotes, no unidades de mercadería).

Stock y respuestas de equipos incluyen expired_count/today_count además de expiry_count; presentación compartida en stock-utils para Stock y equipo. Copias antiguas sin desglose muestran «N con aviso de vencimiento» en vez de inventar estado; al actualizar la copia se reciben los conteos. Sin migración ni datos escritos.

Validación main y beta: pallet-expiry-counts comprueba ayer/hoy/mañana/límite30/fuera31, y preferencia0. stock-utils verifica textos/tonos/singular/mix/caché anterior. team-inventory y branch-team aprobados en ambas ramas; Stock API main aprobado. Sintaxis PHP/JS/diff. Reversión: restaurar los seis archivos de implementación anteriores con caché superior; sin datos que revertir. No mezclar funciones comerciales beta en main.
