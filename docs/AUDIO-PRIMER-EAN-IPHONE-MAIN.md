# Audio desde el primer EAN en iPhone — main3.22

Usuario aclara que falta desde primera lectura; preparación de reescaneo3.21 no basta para ese reporte. Servicio global incorpora HTMLAudioElement para iOS/iPadOS, preparado desde gesto con WAV PCM local (250ms iniciales en silencio, tono1600Hz/150ms). Pausa/posiciona tras preparación; confirmación reproduce desde tono, solicita playback durante reproducción y restaura política al terminar/con límite500ms. No audio continuo ni recursos externos. Beep fuera de iOS conserva WebAudio; fallo/timeout200ms de media cae a WebAudio sin impedir entrega de código. Respeta sonido desactivado y vibración; reusar servicio no corta tonos por módulos. Cancela/restituye sesión de tono media anterior al confirmar otro.

Fuente de compatibilidad investigada: https://bugs.webkit.org/show_bug.cgi?id=237322 (silencio de WebAudio/iOS); el caso individual aún requiere audición física, no atribuirlo con certeza solo al interruptor. WAV agregado al shell de SW para uso sin conexión.

Validación: ios-first-scan-audio mocks comprueba desbloqueo con gesto, silencio inicial, primer tono fuera de gesto, repetición, preferencia off y restauración de sesión; WAV real decodifica/reproduce en Chrome con ios-audio-file-ui. WebKit de Windows devuelve NotSupportedError/mediaerror4 al PCM en prueba real, se registra SKIP, no como aprobado; tests de lector/audio simulado EAN WebKit y QR/servicio global conservados. No prueba física de Safari/iPhone ni garantía de audición en todos los dispositivos/volúmenes.

Reversión: revertir commit y publicar caché superior. Sin datos/permisos/migraciones. PENDIENTES.md/ROADMAP.md del usuario preservados. Solo main.
