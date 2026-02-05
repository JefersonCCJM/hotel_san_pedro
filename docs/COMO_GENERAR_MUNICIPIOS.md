# Cómo Generar Municipios para DIAN

## 🎯 Objetivo

Este documento explica cómo poblar la tabla `dian_municipalities` con los datos obtenidos desde la API de Factus.

## 📋 Requisitos Previos

1. **Configurar variables de entorno** en tu archivo `.env`:
   ```env
   FACTUS_SANDBOX=true
   FACTUS_CLIENT_ID=a083a079-7c0f-4e17-ae57-5e13dfe72b80
   FACTUS_CLIENT_SECRET=ZlV71PurxCrYGKky5wXsnA5I5OR5Ipg2bMpZxhF2
   ```

2. **Verificar que la migración esté ejecutada**:
   ```bash
   php artisan migrate
   ```

## 🚀 Métodos para Generar Municipios

### Método 1: Usar el Seeder (Recomendado)

Ejecuta el seeder específico para municipios:

```bash
php artisan db:seed --class=DianMunicipalitySeeder
```

O ejecuta todos los seeders (incluyendo el de municipios):

```bash
php artisan db:seed
```

### Método 2: Usar Tinker (Para pruebas)

```php
// En Tinker
php artisan tinker

// Ejecutar el seeder manualmente
Artisan::call('db:seed', ['--class' => 'DianMunicipalitySeeder']);
```

### Método 3: Crear un Comando Personalizado

```bash
php artisan make:command ImportMunicipalitiesCommand
```

Luego edita el archivo generado para incluir la lógica de importación.

## 🌍 Configuración por Ambiente

### Para Desarrollo (Sandbox)
```env
FACTUS_SANDBOX=true
FACTUS_API_URL=https://api-sandbox.factus.com.co
FACTUS_CLIENT_ID=a083a079-7c0f-4e17-ae57-5e13dfe72b80
FACTUS_CLIENT_SECRET=ZlV71PurxCrYGKky5wXsnA5I5OR5Ipg2bMpZxhF2
```

### Para Producción
```env
FACTUS_SANDBOX=false
FACTUS_API_URL=https://api.factus.com.co
FACTUS_CLIENT_ID=tu_client_id_real
FACTUS_CLIENT_SECRET=tu_client_secret_real
```

**Importante**: Reemplaza `tu_client_id_real` y `tu_client_secret_real` con las credenciales reales proporcionadas por Factus.

## 📊 Estructura de Datos

El seeder poblará la tabla `dian_municipalities` con:

- **factus_id**: ID único de Factus
- **code**: Código del municipio (opcional)
- **name**: Nombre del municipio
- **department**: Nombre del departamento

## 🔍 Verificación

Para verificar que los municipios se importaron correctamente:

```bash
# Contar municipios importados
php artisan tinker
>>> DianMunicipality::count();

# Ver primeros municipios
>>> DianMunicipality::limit(5)->get();

# Buscar municipio específico
>>> DianMunicipality::where('name', 'like', '%Bogotá%')->first();
```

## ⚠️ Notas Importantes

1. **Token de Acceso**: El seeder obtiene automáticamente el token de acceso usando las credenciales configuradas.
2. **Sandbox vs Producción**: Configura `FACTUS_SANDBOX=false` para usar el entorno de producción.
3. **Limpieza**: El seeder limpia la tabla antes de insertar nuevos datos.
4. **Logs**: Revisa `storage/logs/laravel.log` si hay errores durante la importación.

## 🐛 Solución de Problemas

### Error: "No se pudo obtener el token de acceso"
- Verifica que `FACTUS_CLIENT_ID` y `FACTUS_CLIENT_SECRET` estén correctos.
- Confirma que las credenciales sean válidas para el entorno (sandbox/producción).

### Error: "Error al obtener municipios de Factus"
- Revisa la conexión a internet.
- Verifica que la API de Factus esté disponible.
- Revisa los logs para más detalles del error.

### Error: "Faltan credenciales de Factus"
- Asegúrate de tener las variables de entorno configuradas.
- Reinicia el servidor después de modificar el `.env`.

## 🔄 Actualización de Municipios

Si necesitas actualizar la lista de municipios en el futuro:

```bash
# Volver a ejecutar el seeder (limpiará y volverá a importar)
php artisan db:seed --class=DianMunicipalitySeeder
```

## 📝 Referencias

- [Documentación API Factus - Municipios](https://docs.factus.com.co/v1/municipalities)
- [Modelo DianMunicipality](../app/Models/DianMunicipality.php)
- [Migración de la tabla](../database/migrations/2025_12_14_045227_create_dian_municipalities_table.php)
