# Práctica 1 — Arranque del entorno con Docker

0613 · Desarrollo Web en Entorno Servidor · 2.º DAW A

## Objetivo y alcance

Construir un entorno local con tres contenedores definidos en un único `docker-compose.yml`: nginx, PHP-FPM 8.3 y MySQL. La página `src/index.php` integra HTML y PHP, conecta a MySQL con PDO y muestra el resultado de una consulta real.

La solución se basa en los requisitos y la rúbrica facilitados. La plantilla de Google Docs aporta el formato de la memoria; no contiene el documento «Práctica 1 — Enunciado del alumno», por lo que no se ha podido contrastar con ese documento.

## Arquitectura y recorrido de una petición

1. El navegador solicita `http://localhost:8080`.
2. Docker redirige el puerto 8080 del equipo al puerto 80 de `web`.
3. nginx resuelve `index.php` y envía la petición mediante FastCGI a `php:9000`. `SCRIPT_FILENAME` identifica `/var/www/html/index.php`.
4. PHP-FPM ejecuta el script. PDO utiliza el controlador `pdo_mysql` y conecta a `db:3306`, con el usuario `alumno` y la base de datos `practica1`.
5. MySQL devuelve el nombre de la base, su versión y su fecha y hora. PHP inserta los valores en una tabla HTML, y nginx devuelve la respuesta al navegador.

Los nombres `web`, `php` y `db` corresponden a servicios de Compose. La red que Compose crea permite resolverlos por nombre sin fijar direcciones IP. Usar `localhost` como servidor de base de datos dentro de PHP sería incorrecto: se referiría al propio contenedor PHP.

## Responsabilidad de cada archivo

`docker-compose.yml`: define los tres servicios, el puerto público, los montajes y las variables de conexión. El volumen `db_data` conserva los datos cuando se eliminan y recrean los contenedores. El código se monta en modo de solo lectura tanto en nginx como en PHP, en la misma ruta.

`php/Dockerfile`: parte de `php:8.3-fpm-alpine` e instala únicamente `pdo_mysql`. La imagen ya incluye PHP-FPM y el mecanismo necesario para arrancarlo; no se añaden Apache, herramientas de depuración ni extensiones ajenas a la práctica.

`nginx/default.conf`: configura el documento raíz, `index.php` y el envío de peticiones PHP a `php:9000`. `try_files` devuelve 404 si el archivo no existe. nginx recibe HTTP; PHP-FPM recibe FastCGI. Son protocolos distintos.

`src/index.php`: configura PDO para lanzar excepciones, ejecuta una consulta y separa la obtención de datos de su representación HTML. `htmlspecialchars` escapa los valores al insertarlos en HTML.

## Arranque, errores y persistencia

Requisito: Docker con Compose v2 y soporte para contenedores Linux. Desde la raíz del repositorio, ejecutar `docker compose up -d` y abrir `http://localhost:8080`. El primer arranque necesita descargar imágenes y compilar la extensión PHP.

El `healthcheck` de MySQL comprueba una consulta autenticada contra la base configurada. PHP espera a que esa comprobación pase y nginx espera a que PHP-FPM escuche en el puerto 9000. La existencia de un contenedor en ejecución, por sí sola, no asegura que su aplicación esté preparada.

Si PDO falla, `catch (PDOException ...)` establece HTTP 503, escribe el detalle en los registros de PHP y muestra un mensaje útil en HTML. No se muestran las contraseñas ni el error interno completo al navegador. La página vuelve a intentar la conexión en cada petición.

`docker compose down` detiene y elimina los contenedores y la red del proyecto, pero conserva el volumen de MySQL. `docker compose down -v` también elimina sus datos. Las variables `MYSQL_*` inicializan una base nueva: cambiar sus valores no modifica automáticamente los usuarios de un volumen ya inicializado.

Solo nginx publica un puerto, vinculado a `127.0.0.1`. PHP y MySQL se comunican por la red de Compose. Las credenciales incluidas son ejemplos locales y no deben reutilizarse en servicios reales.

## Comprobaciones reproducibles

- Estado de los servicios: `docker compose ps`.
- Sintaxis de nginx: `docker compose exec web nginx -t`.
- Sintaxis PHP: `docker compose exec php php -l /var/www/html/index.php`.
- Caso correcto: acceder a la página y comprobar el mensaje de conexión, PHP 8.3 y los datos procedentes de MySQL.
- Caso de error: ejecutar `docker compose stop db` y recargar; debe responder HTTP 503 con un mensaje controlado.
- Recuperación: ejecutar `docker compose up -d`, esperar al arranque de MySQL y recargar.
- Diagnóstico: `docker compose logs web php db`.

El flujo `.github/workflows/validate.yml` automatiza el arranque, las comprobaciones de sintaxis, la navegación real con Chromium, una vista móvil, la respuesta 404 para un PHP inexistente, el fallo de conexión y la recuperación. Las capturas se obtienen del servicio real, sin sustituir las respuestas de PHP o MySQL.

Estado de las evidencias: ejecución de los contenedores y captura pendientes de publicar y ejecutar el flujo. Este equipo no tiene Docker ni WSL instalados. La presencia del flujo de pruebas no equivale a una ejecución superada.

## Justificación frente a XAMPP

Separar nginx, PHP y MySQL da a cada contenedor una responsabilidad. Se puede reconstruir la imagen PHP para añadir una extensión sin reinstalar MySQL, y conservar la base de datos mediante un volumen. La definición del entorno queda versionada junto con el código y puede compartirse con otro equipo.

XAMPP simplifica la instalación inicial mediante un paquete integrado y un panel de control. Para una prueba pequeña puede requerir menos configuración. Su configuración local, las versiones instaladas y los cambios manuales no se reproducen únicamente al clonar el código del proyecto.

Docker tiene límites: requiere aprender puertos, redes, imágenes y volúmenes; ocupa espacio con las imágenes y consume recursos del motor. En Windows, los contenedores Linux requieren una máquina virtual, normalmente mediante WSL 2. Los montajes de archivos pueden rendir de forma diferente según el sistema. Las etiquetas de imagen también pueden recibir actualizaciones; para reproducir exactamente sus bytes sería necesario fijar sus resúmenes de contenido (digests).

Esta arquitectura resulta adecuada para aprender la separación de responsabilidades y reproducir un entorno de desarrollo. No garantiza por sí sola seguridad ni escalabilidad de producción: serían necesarios, entre otros ajustes, credenciales gestionadas fuera del repositorio, copias de seguridad y HTTPS.

## Correspondencia con la rúbrica

- RA1.f.1: comunicación nginx → PHP-FPM mediante FastCGI y explicación del recorrido.
- RA1.f.2: conexión mediante PDO, consulta real y tratamiento explícito de excepciones.
- RA1.f.3: HTML estructurado con datos PHP escapados y estados de éxito y error.
- RA1.g.1: Dockerfile de dos instrucciones y configuración nginx limitada a lo necesario.
- RA1.g.2: comparación argumentada con XAMPP, incluyendo ventajas y límites.
- RA1.g.3: README con instrucciones e historial de commits por etapas; publicación y captura pendientes de verificación.

## Fuentes

- Plantilla facilitada: https://docs.google.com/document/d/1eij0BaR4Luvbiac06be9OmfjJdXck6zLkOIGiAfYI80/edit
- Docker Compose, orden de arranque: https://docs.docker.com/compose/how-tos/startup-order/
- Imagen oficial de PHP: https://hub.docker.com/_/php
- nginx, módulo FastCGI: https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html
- Imagen oficial de MySQL: https://hub.docker.com/_/mysql
