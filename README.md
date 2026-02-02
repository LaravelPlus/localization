# LaravelPlus Localization

Multi-language localization package for Laravel 12+. Provides database-driven translations, multiple locale detection strategies, content translation traits, caching, and a full admin UI.

## Requirements

- PHP 8.4+
- Laravel 12+

## Installation

```bash
composer require laravelplus/localization
```

Publish configuration and run migrations:

```bash
php artisan vendor:publish --tag=localization-config
php artisan migrate
```

Seed the default languages:

```bash
php artisan vendor:publish --tag=localization-seeders
php artisan db:seed --class=LanguageSeeder
```

## Setup

Register the middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \LaravelPlus\Localization\Middleware\SetLocale::class,
    ]);
})
```

## Configuration

Publish the config file to customize behavior:

```php
// config/localization.php
return [
    'static' => [
        'driver' => 'hybrid',        // 'file', 'database', or 'hybrid'
    ],
    'content' => [
        'enabled'  => true,
        'strategy' => 'polymorphic_table', // 'json_column' or 'polymorphic_table'
    ],
    'detection' => [
        'strategy'    => 'chain',     // 'session', 'url_prefix', 'user_preference', 'browser', 'chain'
        'chain'       => ['session', 'user_preference', 'browser'],
        'user_column' => 'locale',
    ],
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'localization',
        'store'   => null,
    ],
    'admin' => [
        'enabled'    => true,
        'prefix'     => 'admin/localizations',
        'middleware'  => ['web', 'auth', 'role:super-admin,admin'],
    ],
    'tables' => [
        'languages'            => 'languages',
        'translations'         => 'translations',
        'content_translations' => 'content_translations',
    ],
    'models' => [
        'language'            => Language::class,
        'translation'         => Translation::class,
        'content_translation' => ContentTranslation::class,
    ],
];
```

## Usage

### Translation Drivers

The package supports three translation loading strategies:

- **file** — Uses Laravel's default `lang/` files
- **database** — Loads translations from the database
- **hybrid** — Merges file and database translations (database overrides file)

### Locale Detection

The `SetLocale` middleware resolves the current locale from the request. Strategies:

| Strategy | Description |
|----------|-------------|
| `session` | Reads `session('locale')` |
| `url_prefix` | Reads the first URL segment (e.g. `/de/about`) |
| `user_preference` | Reads a configurable column on the User model |
| `browser` | Parses the `Accept-Language` header |
| `chain` | Tries each resolver in order, uses the first match |

### Facade

The `Localization` facade proxies to `TranslationService`:

```php
use LaravelPlus\Localization\Facades\Localization;

Localization::getForLocale('en');
Localization::getGroupsForLocale('en');
Localization::importFromArray('en', 'messages', ['welcome' => 'Welcome!']);
Localization::exportToArray('en');
```

### Content Translations (HasTranslations Trait)

Add `HasTranslations` to models with translatable fields:

```php
use LaravelPlus\Localization\Traits\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'body'];
}

// Get translation with fallback to original attribute
$post->translate('title', 'de');

// Set a translation
$post->setTranslation('title', 'de', 'Mein Titel');

// Get without fallback
$post->getTranslation('title', 'de');
```

Supports two strategies:

- **polymorphic_table** — Stores translations in the `content_translations` table via a morphMany relationship
- **json_column** — Stores translations as JSON directly on the model (requires a `{field}_translations` JSON column)

### Vue Composable

The `useTranslation` composable reads shared Inertia props:

```typescript
import { useTranslation } from '@/composables/useTranslation';

const { t, te, locale, availableLocales, direction } = useTranslation();

// Translate with dot notation
t('messages.welcome');

// Translate with replacements
t('messages.greeting', { name: 'John' }); // "Hello, :name!" → "Hello, John!"

// Check if translation exists
te('messages.welcome'); // true/false
```

## Admin Panel

When `localization.admin.enabled` is `true`, the package registers admin routes at `/admin/localizations` with:

- **Languages** — CRUD for available languages, set default, toggle active/inactive, RTL support
- **Translations** — CRUD for translation strings, filter by language and group, search by key or value

## Artisan Commands

```bash
# Import translations from files into the database
php artisan localization:import {path?} --locale= --group= --overwrite

# Export translations from the database to files
php artisan localization:export {path?} --locale= --group= --format=json|php
```

## AI Skills

Publish AI development skills for Claude Code or GitHub Copilot:

```bash
php artisan vendor:publish --tag=localization-skills          # .claude/skills/
php artisan vendor:publish --tag=localization-skills-github    # .github/skills/
```

## File Structure

```
├── config/localization.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/admin.php
├── skills/localization-development/
└── src/
    ├── Cache/TranslationCache.php
    ├── Console/Commands/
    ├── Contracts/
    ├── Facades/Localization.php
    ├── Http/
    │   ├── Controllers/Admin/
    │   └── Requests/
    ├── Loaders/
    ├── Middleware/SetLocale.php
    ├── Models/
    ├── Repositories/
    ├── Resolvers/
    ├── Services/
    ├── Traits/HasTranslations.php
    └── LocalizationServiceProvider.php
```

## License

MIT
