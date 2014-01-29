NSNC Web Framework
=====================

Framework MVC basado en Symfony 2.x para la realización de sitios webs


Tecnologías
-----------
* PHP
* MySQL (Doctrine)
* CSS (LESS)
* HTML (TWIG)
* JavaScript (jQuery)

Frameworks
----------
* LessPHP (LESS for Symfony2) http://leafo.net/lessphp/
* CKEditor http://ckeditor.com
* jQuery 1.10.2 http://jquery.com/
* Gregwar Image https://github.com/Gregwar/Image
* Twig
* Doctrine

Configuración Inicial
---------------------
* Instalar/actualizar Vendors mediante `composer` utilizando `php path/to/composer.phar install/update` (instalar composer si hace falta)

Descripción
--------------------
Framework MVC basado en Symfony 2.x para la realización de sitios webs.
Uso de Doctrine para optimizar y facilitar la interacción con la base de datos.
LESS para optimizar los estilos del sitio (css).
TWIG para aplicar técnicas de templating, esto es separar las vistas de los controladores de manera efectiva y limpia, y además aprovechar (e implementar) el uso de caché.
jQuery para potenciar el motor de JavaScript.
GIT como sistema de repositorios para versionar el trabajo y generar backups.
Arquitectura del desarrollo basada en MVC (modelo-vista-controlador)
Resolver de requests (user) pensado para el uso de nice urls (buscadores)

Console scripts
--------------------
* php bin/deploy.php
* php bin/clearcache.php
* php vendor/bin/doctrine orm:convert-mapping --from-database yml orm
* php vendor/bin/doctrine orm:generate:entities src