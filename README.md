# WordPress Update Server Setup Guide
<img width="1001" height="890" alt="image" src="https://github.com/user-attachments/assets/2056c295-f472-4e53-881e-842213e2ec9d" />

## Обзор

Этот плагин для SW JProjects позволяет использовать Joomla-сайт в качестве сервера обновлений для WordPress плагинов. Он преобразует данные из SW JProjects в формат, совместимый с WordPress Plugin Update API.

## Установка и активация

### 1. Установка плагина

1. Скачайте ZIP-архив плагина
2. Перейдите в **Extensions → Manage → Install**
3. Загрузите ZIP-файл
4. Плагин автоматически активируется после установки

### 2. Настройка проекта в SW JProjects

Для каждого WordPress-плагина, который вы хотите распространять через этот сервер обновлений:

1. Перейдите в **Components → SW JProjects → Projects**
2. Откройте нужный проект или создайте новый
3. На вкладке **General** найдите поле **Server Scheme**
4. Выберите **WordPress Plugin Update API**
5. в SW JProjects версии 2.6 и ниже на вкладке Joomla обязательно выберите любой тип расширения и включите сервер обновлений - иначе ничего работать не будет!
6. Сохраните проект

**ВАЖНО:** в SW JProjects версии 2.6 и ниже для работы схемы WordPress она должна стоять как схема по умолчанию в настройках компонента, а для Joomla расширений в свойствах можно включить свою схему!

## Маппинг полей: SW JProjects → WordPress

### Основные поля проекта

| Поле в SW JProjects                       | Поле в WordPress API          | Описание                         |
| ----------------------------------------- | ----------------------------- | -------------------------------- |
| **Project Element**                       | `slug`                        | Уникальный идентификатор плагина |
| **Project Title** (из translate_projects) | `name`                        | Название плагина                 |
| **Full Text** (из translate_projects)     | `sections.description`        | Полное описание плагина (HTML)   |
| **Icon** (images)                         | `icons.1x`, `icons.2x`        | Иконка плагина (256x256px)       |
| **Cover** (images)                        | `banners.low`, `banners.high` | Баннер плагина                   |

### Поля версии

| Поле в SW JProjects                    | Поле в WordPress API | Описание                                         |
| -------------------------------------- | -------------------- | ------------------------------------------------ |
| **Version** (major.minor.patch.hotfix) | `version`            | Номер версии                                     |
| **Date**                               | `last_updated`       | Дата последнего обновления                       |
| **Joomla Version**                     | `tested`             | Версия WordPress, с которой протестирован плагин |
| **PHP Min**                            | `requires_php`       | Минимальная версия PHP                           |
| **Changelog** (из translate_versions)  | `sections.changelog` | История изменений (HTML)                         |

### Автоматическая генерация changelog

Плагин автоматически собирает **полную историю изменений** из всех опубликованных версий проекта:

- Данные берутся из таблицы `#__swjprojects_translate_versions`
- Поле `changelog` должно содержать JSON в формате:
  ```json
  {
    "changelog0": {
      "title": "Название изменения",
      "type": "addition|fix|note",
      "description": "Описание изменения"
    },
    "changelog1": { ... }
  }
  ```
- Версии отображаются от новой к старой
- Поддерживается мультиязычность (текущий язык → язык по умолчанию)

<img width="813" height="628" alt="image" src="https://github.com/user-attachments/assets/d24e2263-d11a-450c-bcad-42934071add9" />


## Настройка метаданных через плагин

<img width="1352" height="732" alt="image" src="https://github.com/user-attachments/assets/24ae2800-3cae-467c-9f4e-7726ead99cfa" />


Для переопределения метаданных конкретного проекта:

### 1. Открыть настройки плагина

1. Перейдите в **Extensions → Plugins**
2. Найдите **SW JProjects - WordPress Server Scheme**
3. Нажмите на название плагина

### 2. Добавить переопределение для проекта

В разделе **Project Metadata Overrides**:

1. Нажмите кнопку **+** (Add)
2. Заполните поля:

#### Project

Выберите проект из выпадающего списка (отображается `element` проекта)

#### Author Name

**Назначение:** Имя автора плагина  
**Пример:** `Alex Revo` или `Your Company Name`  
**Попадает в:** `author`, `author_profile`  
**Примечание:** Если не заполнено, поля автора не будут отображаться в WordPress

#### Author URL

**Назначение:** Ссылка на сайт автора  
**Пример:** `https://alexrevo.pw`  
**Попадает в:** `author_profile` и в HTML-ссылку в поле `author`  
**Примечание:** Работает только если заполнено **Author Name**

#### Requires PHP

**Назначение:** Минимальная версия PHP  
**Пример:** `7.4` или `8.0`  
**Попадает в:** `requires_php`  
**По умолчанию:** `7.4` (если не заполнено)

#### Installation Instructions

**Назначение:** Инструкция по установке плагина  
**Формат:** HTML (можно использовать теги)  
**Пример:**

```html
<ol>
  <li>
    Upload the plugin files to the <code>/wp-content/plugins/</code> directory
  </li>
  <li>Activate the plugin through the 'Plugins' screen in WordPress</li>
  <li>Go to Settings → Plugin Name to configure</li>
</ol>
```

**Попадает в:** `sections.installation`  
**По умолчанию:** Стандартная инструкция по загрузке через админку WordPress

### 3. Сохранить настройки

Нажмите **Save & Close**

## Пример настройки проекта

### В SW JProjects (проект):

- **Element:** `my-awesome-plugin`
- **Title (en-GB):** `My Awesome Plugin`
- **Full Text (en-GB):** `<p>This plugin does amazing things...</p>`
- **Server Scheme:** `WordPress Plugin Update API`
- **Icon:** Загружен файл `icon.png` (256x256)
- **Cover:** Загружен файл `cover.jpg` (772x250)

### В SW JProjects (версия 1.0.5):

- **Version:** `1.0.5`
- **Joomla Version:** `6.7` (будет использовано как WordPress tested version)
- **PHP Min:** `7.4`
- **Changelog (en-GB):**
  ```json
  {
    "changelog0": {
      "title": "New Feature",
      "type": "addition",
      "description": "Added support for custom post types"
    },
    "changelog1": {
      "title": "Bug Fix",
      "type": "fix",
      "description": "Fixed issue with settings page"
    }
  }
  ```

### В настройках плагина:

- **Project:** `my-awesome-plugin`
- **Author Name:** `Alex Revo`
- **Author URL:** `https://alexrevo.pw`
- **Requires PHP:** `7.4`
- **Installation Instructions:**
  ```html
  <ol>
    <li>Download the plugin ZIP file</li>
    <li>Upload via WordPress admin or FTP</li>
    <li>Activate and enjoy!</li>
  </ol>
  ```

## Результат в WordPress API

URL запроса: `https://yoursite.com/getupdates.html?element=my-awesome-plugin&download_key=YOUR_KEY`
здесь `getupdates.html` - это алиас пункта меню с типом SW JProjects » Projects update server
Ответ (JSON):

```json
{
  "name": "My Awesome Plugin",
  "slug": "my-awesome-plugin",
  "author": "<a href=\"https://alexrevo.pw\">Alex Revo</a>",
  "author_profile": "https://alexrevo.pw",
  "version": "1.0.5",
  "download_url": "https://yoursite.com/download.html?element=my-awesome-plugin",
  "requires": "5.0",
  "tested": "6.7",
  "requires_php": "7.4",
  "last_updated": "2025-11-28 06:00:00",
  "sections": {
    "description": "<p>This plugin does amazing things...</p>",
    "installation": "<ol><li>Download the plugin ZIP file</li>...</ol>",
    "changelog": "<h4>1.0.5</h4><ul><li><strong>New Feature</strong> - Added support for custom post types</li><li><strong>Bug Fix</strong> - Fixed issue with settings page</li></ul><h4>1.0.4</h4>..."
  },
  "icons": {
    "1x": "https://yoursite.com/images/swjprojects/projects/3/en-GB/icon.png",
    "2x": "https://yoursite.com/images/swjprojects/projects/3/en-GB/icon.png"
  },
  "banners": {
    "low": "https://yoursite.com/images/swjprojects/projects/3/en-GB/cover.jpg",
    "high": "https://yoursite.com/images/swjprojects/projects/3/en-GB/cover.jpg"
  }
}
```

здесь и далее в плагине WordPress `download.html` - это алиас пункта меню с типом SW JProjects » Download

## Интеграция с WordPress

### Создание клиентского плагина

На стороне WordPress создайте плагин, который будет проверять обновления:

```php
<?php
/*
Plugin Name: My Awesome Plugin
Version: 1.0.4
*/
define('UPDATE_SERVER', 'https://yoursite.com/getupdates.html?element=my-awesome-plugin');
define('PLUGIN_SLUG', 'my-awesome-plugin');
define('LICENSE_KEY_OPTION', 'my_plugin_license_key');

// --- Страница настроек для ввода ключа лицензии ---

add_action('admin_menu', 'my_plugin_add_settings_page');
function my_plugin_add_settings_page() {
    add_options_page(
        'My Plugin License',
        'My Plugin License',
        'manage_options',
        'my-plugin-license',
        'my_plugin_settings_page'
    );
}

add_action('admin_init', 'my_plugin_register_settings');
function my_plugin_register_settings() {
    register_setting('my_plugin_license_group', LICENSE_KEY_OPTION);
    
    add_settings_section(
        'my_plugin_license_section',
        'License Key Settings',
        'my_plugin_license_section_callback',
        'my-plugin-license'
    );
    
    add_settings_field(
        LICENSE_KEY_OPTION,
        'Download Key',
        'my_plugin_license_field_render',
        'my-plugin-license',
        'my_plugin_license_section'
    );
}

function my_plugin_license_section_callback() {
    echo 'Enter your license key to enable automatic updates from the update server.';
}

function my_plugin_license_field_render() {
    $value = get_option(LICENSE_KEY_OPTION);
    ?>
    <input type='text' name='<?php echo LICENSE_KEY_OPTION; ?>' value='<?php echo esc_attr($value); ?>' class="regular-text code">
    <p class="description">Your download key from the update server.</p>
    <?php
}

function my_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>My Plugin License Settings</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('my_plugin_license_group');
            do_settings_sections('my-plugin-license');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// --- Проверка обновлений ---

add_filter('pre_set_site_transient_update_plugins', function($transient) {
    if (empty($transient->checked)) return $transient;
    $current_version = '1.0.4';
    $license_key = get_option(LICENSE_KEY_OPTION);
    $response = wp_remote_get(UPDATE_SERVER . '&download_key=' . urlencode($license_key));
    if (is_wp_error($response)) return $transient;
    $data = json_decode(wp_remote_retrieve_body($response));
    if (version_compare($current_version, $data->version, '<')) {
        $obj = new stdClass();
        $obj->slug = PLUGIN_SLUG;
        $obj->new_version = $data->version;
        $obj->url = $data->author_profile;
        $obj->package = $data->download_url;
        $obj->tested = $data->tested;
        $obj->requires = $data->requires;
        $obj->requires_php = $data->requires_php;
        $obj->icons = (array) $data->icons;
        $obj->banners = (array) $data->banners;
        $transient->response[plugin_basename(__FILE__)] = $obj;
    }
    return $transient;
});

// --- Информация о плагине (для окна "View Details") ---

add_filter('plugins_api', function($res, $action, $args) {
    if ($action !== 'plugin_information' || $args->slug !== PLUGIN_SLUG) {
        return $res;
    }
    $license_key = get_option(LICENSE_KEY_OPTION);
    $response = wp_remote_get(UPDATE_SERVER . '&download_key=' . urlencode($license_key));
    if (is_wp_error($response)) return $res;
    $data = json_decode(wp_remote_retrieve_body($response));
    $res = new stdClass();
    $res->name = $data->name;
    $res->slug = $data->slug;
    $res->version = $data->version;
    $res->author = $data->author;
    $res->author_profile = $data->author_profile;
    $res->requires = $data->requires;
    $res->tested = $data->tested;
    $res->requires_php = $data->requires_php;
    $res->last_updated = $data->last_updated;
    $res->sections = (array) $data->sections;
    $res->download_link = $data->download_url;
    $res->icons = (array) $data->icons;
    $res->banners = (array) $data->banners;
    return $res;
}, 20, 3);
```

**Что добавлено:**

1. **Страница настроек** в WordPress админке (`Settings → My Plugin License`)
2. **Поле для ввода ключа лицензии** с сохранением в базу данных WordPress
3. **Использование ключа** при запросах к серверу обновлений
4. **URL-кодирование ключа** для безопасной передачи

После установки плагина пользователь может:
- Перейти в `Settings → My Plugin License`
- Ввести свой `download_key` полученный от вас
- Сохранить настройки
- Плагин автоматически будет использовать этот ключ при проверке обновлений
```

## Рекомендации по заполнению данных

### Иконка плагина - вывод не работает, возможно требуется наличие плагина в каталоге расширений WordPress

- **Размер:** 256x256px (минимум 128x128px)
- **Формат:** PNG с прозрачностью или JPG
- **Расположение:** Загрузить через SW JProjects → Projects → Images → Icon

### Баннер (Cover)

- **Размер:** 772x250px (low) или 1544x500px (high)
- **Формат:** JPG или PNG
- **Расположение:** Загрузить через SW JProjects → Projects → Images → Cover

### Changelog

- Заполняйте для каждой версии
- Используйте понятные заголовки
- Типы изменений: `addition`, `fix`, `note`
- Описания могут быть многострочными

### Описание проекта (Full Text)

- Используйте HTML для форматирования
- Добавляйте скриншоты через `<img>` теги с полным путем
- Структурируйте текст заголовками `<h3>`, `<h4>`
- Используйте списки `<ul>`, `<ol>` для перечислений

## Поддержка

Для вопросов и поддержки обращайтесь:

- Email: help@alexrevo.pw
- Website: https://alexrevo.pw

---

**Версия документа:** 1.1.0  
**Дата обновления:** 28.11.2025
