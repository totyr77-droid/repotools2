# Presentación única y preparación de audio EAN — main3.21

El selector mantenía dos opciones boxes/packs con el mismo texto; packs usaba hidden, que no impide que el menú nativo de Safari lo muestre. Se retira físicamente la opción legacy de agregar/restar. Productos, preferencias y borradores packs se presentan como boxes, conservando unidades/pack_size. Referencias antiguas packs siguen disponibles como fallback. No migración masiva ni modificación de stock al abrir.

Guardar y escanear otro abre el lector después de guardado/consulta asíncronos. El adaptador ahora expone prepareFeedback y el botón lo llama sincrónicamente en el gesto antes de requestSubmit. Así puede recuperar AudioContext interrumpido mientras Safari admite resume; no depende de que el futuro start conserve activación del usuario. Respeta preferencias del servicio y no genera pitido de guardado. No modifica audio/cámara globales ni cantidad de tonos.

Validación: ean-scanner y scan-feedback unit; product-rescan-audio-ui Chrome/WebKit con adaptador/EAN reales y cámara/audio simulados, primera lectura y dos reaperturas después de espera, tres tonos. QR pallet-rescan WebKit conservado. stock-inner-packs-ui Chrome/WebKit guarda/edita/resta composición; Safari aserta una sola opción Bultos cerrados incluso contando todas las opciones del DOM. Audición física en iPhone pendiente: esta prueba no demuestra todo problema de sonido ni primera lectura real. Consulta al usuario pendiente sobre primer escaneo o Guardar y escanear otro.

Reversión: revertir commit y publicar caché superior. No borrar productos ni transformar datos almacenados como reversión. PENDIENTES.md/ROADMAP.md locales preservados fuera del commit; solo main, beta intacta.

Prueba adicional: stock-pack-references-offline-ui anterior se detiene al intentar seleccionar packs, opción retirada por este pedido; ese recorrido presupone dos presentaciones independientes. No se atribuye como aprobado. Se añade cobertura específica de edición de producto legacy packs con una sola opción, pack_size36/units72/cantidad2 y sin mutación al abrir. El almacenamiento offline sigue conservando referencias antiguas; no se altera su formato.
