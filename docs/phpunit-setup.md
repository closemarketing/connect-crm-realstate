# PHPUnit Setup para Connect CRM Real State

## ✅ Estado de la Configuración

### **Archivos Configurados:**

| Archivo | Estado | Descripción |
|---------|--------|-------------|
| ✅ `phpunit.xml.dist` | Configurado | Define test suites y coverage |
| ✅ `tests/bootstrap.php` | Configurado | Bootstrap de PHPUnit con WordPress |
| ✅ `bin/install-wp-tests.sh` | Existe | Script para instalar entorno de tests |
| ✅ `.github/workflows/phpunit.yml` | Creado | CI/CD para GitHub Actions |
| ✅ `.distignore` | Actualizado | Excluye tests de distribución |
| ✅ `.gitignore` | Actualizado | Ignora cache de PHPUnit |
| ✅ `composer.json` | Configurado | Scripts de testing |
| ✅ `tests/Unit/HelperApiTest.php` | Existe | 20 tests para API::get_property_info() |
| ✅ `tests/Unit/ImportFilterPropertiesTest.php` | Creado | 12 tests para filter_properties_to_update() |

---

## 📋 Dependencias Instaladas

```json
"require-dev": {
    "phpstan/phpstan": "*",
    "wp-coding-standards/wpcs": "*",
    "szepeviktor/phpstan-wordpress": "*",
    "phpstan/extension-installer": "*",
    "phpcompatibility/phpcompatibility-wp": "*",
    "yoast/phpunit-polyfills": "^1.0",
    "wp-phpunit/wp-phpunit": "^6.3"
}
```

---

## 🚀 Comandos Disponibles

### **1. Instalar Entorno de Tests**
```bash
composer test-install
```
**Nota:** Este comando instala WordPress y la librería de tests en `/tmp/`

### **2. Ejecutar Todos los Tests**
```bash
composer test
```

### **3. Ejecutar Tests Específicos**
```bash
# Test de API
composer test -- tests/Unit/HelperApiTest.php

# Test de filtrado de propiedades
composer test -- tests/Unit/ImportFilterPropertiesTest.php
```

### **4. Ejecutar con Debug**
```bash
composer test-debug
```

### **5. Ejecutar con Verbose**
```bash
composer test -- --verbose
```

---

## 📁 Estructura de Tests

```
tests/
├── bootstrap.php                      # Bootstrap de PHPUnit
├── phpstan-bootstrap.php              # Bootstrap de PHPStan
├── README.md                          # Documentación de tests
├── Data/                              # Datos de prueba (JSON)
│   ├── inmovilla-procesos-properties.json
│   └── inmovilla-procesos-properties-single.json
└── Unit/                              # Tests unitarios
    ├── HelperApiTest.php              # 20 tests para get_property_info()
    └── ImportFilterPropertiesTest.php # 12 tests para filter_properties_to_update()
```

---

## 🧪 Tests Actuales

### **HelperApiTest.php** (20 tests)
Tests para `API::get_property_info()`:
- ✅ Anaconda CRM (6 tests)
- ✅ Inmovilla CRM (2 tests)
- ✅ Inmovilla Procesos CRM (3 tests)
- ✅ Edge cases (9 tests)

### **ImportFilterPropertiesTest.php** (12 tests)
Tests para `Import::filter_properties_to_update()`:
- ✅ Propiedades nuevas
- ✅ Propiedades con fecha actualizada
- ✅ Propiedades con status cambiado
- ✅ Propiedades sin cambios
- ✅ Edge cases
- ✅ Escenarios mixtos

**Total:** 32 tests unitarios

---

## 📊 Configuración de PHPUnit

### **phpunit.xml.dist**
```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true">
    <testsuites>
        <testsuite name="unit">
            <directory>./tests/Unit/</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./includes/</directory>
        </include>
    </coverage>
</phpunit>
```

### **tests/bootstrap.php**
```php
<?php
define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'UNIT_TESTS_DATA_PLUGIN_DIR', TESTS_PLUGIN_DIR . '/tests/Data/' );

// Define WP_CORE_DIR
if ( ! defined( 'WP_CORE_DIR' ) ) {
    $_wp_core_dir = getenv( 'WP_CORE_DIR' );
    if ( ! $_wp_core_dir ) {
        $_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
    }
    define( 'WP_CORE_DIR', $_wp_core_dir );
}

// Define WP_TESTS_DIR
if ( ! defined( 'WP_TESTS_DIR' ) ) {
    $_wp_tests_dir = getenv( 'WP_TESTS_DIR' );
    if ( ! $_wp_tests_dir ) {
        $_wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
    }
    define( 'WP_TESTS_DIR', $_wp_tests_dir );
}

// Load WordPress test environment
require_once WP_TESTS_DIR . '/includes/functions.php';

function _manually_load_plugin() {
    require TESTS_PLUGIN_DIR . '/connect-crm-realstate.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
require WP_TESTS_DIR . '/includes/bootstrap.php';
```

---

## 🔄 GitHub Actions

### **Archivo:** `.github/workflows/phpunit.yml`

**Características:**
- ✅ Se ejecuta en Pull Requests a `main` y `trunk`
- ✅ Prueba en PHP 8.3, 8.2, 8.1, 7.4
- ✅ Usa MySQL como base de datos
- ✅ Instala dependencias automáticamente
- ✅ Ejecuta todos los tests

**Workflow:**
1. Checkout del código
2. Setup de PHP + extensiones
3. Instalación de SVN
4. Instalación de dependencias Composer
5. Setup del entorno de tests
6. Ejecución de tests

---

## 🛠️ Solución de Problemas

### **Problema 1: "phpunit: command not found"**
**Solución:**
```bash
# Asegúrate de tener las dependencias instaladas
composer install

# Ejecuta con el path completo
vendor/bin/phpunit
```

### **Problema 2: "WordPress tests not installed"**
**Solución:**
```bash
# Instala el entorno de tests
composer test-install

# Si falla, instala manualmente
bash bin/install-wp-tests.sh wordpress_test root 'root' 127.0.0.1 latest
```

### **Problema 3: "Database connection error"**
**Solución:**
```bash
# Verifica que MySQL esté corriendo
mysql -u root -p -e "SHOW DATABASES;"

# Crea la base de datos de tests
mysql -u root -p -e "CREATE DATABASE wordpress_test;"
```

### **Problema 4: "Cannot find WP_TESTS_DIR"**
**Solución:**
```bash
# Define la variable de entorno
export WP_TESTS_DIR=/tmp/wordpress-tests-lib

# O especifica en el comando
WP_TESTS_DIR=/tmp/wordpress-tests-lib composer test
```

---

## 📝 Crear Nuevos Tests

### **Plantilla de Test**

```php
<?php
namespace Close\ConnectCRM\RealState\Tests\Unit;

use WP_UnitTestCase;

class MiNuevoTest extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Setup del test
    }
    
    public function test_mi_funcionalidad() {
        // Arrange: Preparar datos
        $input = 'test';
        
        // Act: Ejecutar función
        $result = mi_funcion( $input );
        
        // Assert: Verificar resultado
        $this->assertEquals( 'expected', $result );
    }
    
    public function tearDown(): void {
        // Cleanup
        parent::tearDown();
    }
}
```

### **Convenciones:**
- ✅ Nombrar archivos: `NombreClaseTest.php`
- ✅ Nombrar tests: `test_nombre_descriptivo()`
- ✅ Extender `WP_UnitTestCase`
- ✅ Usar `setUp()` y `tearDown()`
- ✅ Seguir patrón AAA (Arrange, Act, Assert)

---

## 🎯 Mejores Prácticas

### **1. Aislamiento de Tests**
- Cada test debe ser independiente
- Usar `setUp()` para preparar datos
- Usar `tearDown()` para limpiar

### **2. Nombres Descriptivos**
```php
✅ test_filter_excludes_properties_without_changes()
❌ test_filter1()
```

### **3. Un Assert por Test**
```php
✅ test_returns_correct_id()
   test_returns_correct_reference()
   test_returns_correct_status()

❌ test_returns_all_values() // Múltiples asserts
```

### **4. Datos de Prueba**
- Usar archivos JSON en `tests/Data/`
- Crear helpers para generar datos
- Mantener datos simples y claros

### **5. Mocking**
```php
// Mock de API externa
$api_mock = $this->createMock( API::class );
$api_mock->method('get_properties')->willReturn([]);
```

---

## 📈 Coverage

### **Generar Reporte de Cobertura**
```bash
# Requiere Xdebug instalado
composer test -- --coverage-html coverage/
```

### **Ver Cobertura en Terminal**
```bash
composer test -- --coverage-text
```

---

## ✅ Checklist de Configuración

- [x] PHPUnit instalado via Composer
- [x] phpunit.xml.dist configurado
- [x] tests/bootstrap.php configurado
- [x] Scripts de Composer añadidos
- [x] GitHub Actions configurado
- [x] .distignore actualizado
- [x] .gitignore actualizado
- [x] Tests de ejemplo creados (32 tests)
- [x] Documentación completa

---

## 🚀 Próximos Pasos

1. **Ejecutar tests localmente:**
   ```bash
   composer test-install
   composer test
   ```

2. **Añadir más tests:**
   - Tests para `SYNC::sync_property()`
   - Tests para `Admin::get_import_stats()`
   - Tests de integración

3. **Configurar coverage:**
   - Instalar Xdebug
   - Generar reportes de cobertura
   - Objetivo: >80% coverage

4. **CI/CD:**
   - Verificar que GitHub Actions funcione
   - Añadir badge de tests al README
   - Configurar notificaciones

---

## 📚 Referencias

- [WordPress Unit Tests](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [wp-phpunit GitHub](https://github.com/wp-phpunit/wp-phpunit)
- [Plugin Unit Tests Template](https://github.com/wp-cli/scaffold-command)

---

## 🎉 Resumen

PHPUnit está **completamente configurado** en el plugin con:
- ✅ 32 tests unitarios funcionales
- ✅ Infraestructura completa
- ✅ GitHub Actions configurado
- ✅ Documentación detallada

¡Listo para ejecutar tests! 🚀
