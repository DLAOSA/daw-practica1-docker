# Práctica 1 · Entorno servidor con Docker

0613 · Desarrollo Web en Entorno Servidor · 2.º DAW A

Entorno de desarrollo con tres servicios separados: nginx recibe HTTP en el puerto 8080, PHP 8.3 ejecuta el código mediante PHP-FPM y MySQL almacena los datos. La aplicación comprueba la conexión mediante PDO.

## Puesta en marcha

Requiere Docker Engine con Docker Compose v2 o Docker Desktop en modo de contenedores Linux.

Desde la raíz del repositorio:

```sh
docker compose up -d
```

La primera ejecución descarga las imágenes y construye PHP. Después, abre <http://localhost:8080>.

## Desarrollo

El historial recoge la preparación del repositorio, la configuración de los servicios, la página PHP y la validación del conjunto.
