@echo off
rem Arranque estable del worker de colas para importaciones (Laravel).
rem Ejecutar este .bat al iniciar sesion, o manualmente para levantar el worker.
cd /d "%~dp0"
php artisan queue:work --sleep=2 --tries=3 --timeout=300