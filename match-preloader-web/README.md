# MATCH · Preloader

Preloader web, responsive (desktop, tablet y mobile). Sin dependencias: HTML + CSS + JS vanilla.

## Archivos
- `index.html`: versión lista, todo en un solo archivo. Ábrela con doble clic para verla.
- `integracion/`: la misma versión separada para tu web.
  - `match-preloader.css`: estilos.
  - `match-preloader.js`: animación y carga.
  - `snippet-body.html`: el HTML del preloader.
  - `index.html`: ejemplo de cómo se conecta todo.

## Cómo integrarlo en la web real
1. Sube `match-preloader.css` y `match-preloader.js` a tu proyecto.
2. En el `<head>`:
   ```html
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap">
   <link rel="stylesheet" href="/ruta/match-preloader.css">
   <script src="/ruta/match-preloader.js" defer></script>
   ```
3. Pon `class="match-preloading"` en `<html>` (bloquea el scroll mientras carga).
4. Pega el contenido de `snippet-body.html` como **primer elemento del `<body>`**.
5. En el CSS, cambia `--mp-hero-bg` por el color de fondo real del Hero (por defecto `#0A0A0B`).

## Comportamiento
- Secuencia: cara vacía → azul oscuro (izquierda) → morado (arriba) → gris (derecha) → original (abajo) → aparece "match" → barrido al Hero.
- El porcentaje sigue la carga real (imágenes, fuentes y `window.load`) y nunca va más rápido que la animación.
- Si la web tarda en cargar, el logo completo se queda en pantalla hasta terminar. Tope de seguridad: 12 s.
- Respeta `prefers-reduced-motion`: muestra el logo final directo, sin giros.
- Al terminar, el preloader se elimina del DOM, `<html>` recibe la clase `match-loaded` y se dispara el evento:
  ```js
  window.addEventListener('match:preloader-done', () => { /* animar el Hero */ });
  ```

## Opciones (atributos en `[data-match-preloader]`)
- `data-speed="1"`: multiplicador de velocidad.
- `data-once="session"`: mostrarlo solo la primera vez por sesión.

## Compatibilidad
Chrome, Edge, Firefox y Safari modernos (Web Animations API). Si el navegador no la soporta, el preloader se oculta al cargar la página.
