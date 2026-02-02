---
name: localization-development
description: >-
  Activate when working with the laravelplus/localization package — managing
  languages, translations, locale resolvers, content translations, translation
  loaders, cache, or building admin pages under admin/localizations.
---

# Localization Development

Package: `laravelplus/localization` — Location: `packages/laravelplus/localization/`
Namespace: `LaravelPlus\Localization` — Facade: `Localization` — Config: `localization`

## When to Apply

- Using `Localization::` facade or `HasTranslations` trait methods
- Creating or modifying translation loaders, resolvers, controllers, models, or migrations in the package
- Working with languages, translations, or content translations
- Configuring locale detection strategies (session, URL prefix, user preference, browser, chain)
- Building admin UI pages under `admin/localizations`
- Writing tests for localization functionality
- Running localization Artisan commands
- Using the `useTranslation` Vue composable

## Facade

The `Localization` facade proxies to `TranslationService`:

<code-snippet name="Localization Facade Usage" lang="php">
use LaravelPlus\Localization\Facades\Localization;

// Get all translations for a locale (keyed by group)
Localization::getForLocale('en');

// Get available groups for a locale
Localization::getGroupsForLocale('en');

// Import translations from an array
Localization::importFromArray('en', 'messages', ['welcome' => 'Welcome!']);

// Export all translations for a locale
Localization::exportToArray('en');
</code-snippet>

## Translation Loaders

Three drivers controlled by `localization.static.driver`:

| Driver | Class | Behavior |
|--------|-------|----------|
| `file` | `FileLoader` | Reads Laravel `lang/{locale}/{group}.php` and `lang/{locale}.json` |
| `database` | `DatabaseLoader` | Queries the translations table via Language model |
| `hybrid` | `HybridLoader` | Merges file + database (database overrides file) |

All implement `TranslationLoaderInterface`:
- `load(string $locale, string $group, ?string $namespace): array`
- `allForLocale(string $locale): array`

## Cache Layer

`TranslationCache` decorates any `TranslationLoaderInterface`. Config keys:

| Key | Default | Description |
|-----|---------|-------------|
| `cache.enabled` | `true` | Enable/disable caching |
| `cache.ttl` | `3600` | Cache TTL in seconds |
| `cache.prefix` | `localization` | Cache key prefix |
| `cache.store` | `null` | Cache store (null = default) |

Cache is automatically flushed on write operations (create, update, delete).

## Locale Resolvers

Controlled by `localization.detection.strategy`:

| Strategy | Class | Behavior |
|----------|-------|----------|
| `session` | `SessionResolver` | Reads `session('locale')` |
| `url_prefix` | `UrlPrefixResolver` | Reads first URL segment, validates against active languages |
| `user_preference` | `UserPreferenceResolver` | Reads configurable column from `$request->user()` |
| `browser` | `BrowserResolver` | Parses `Accept-Language` header with quality weights |
| `chain` | `ChainResolver` | Iterates resolvers from `detection.chain` config, returns first match |

All implement `LocaleResolverInterface`:
- `resolve(Request $request): ?string`

## HasTranslations Trait

Add to models with translatable fields. Supports `json_column` and `polymorphic_table` strategies.

<code-snippet name="HasTranslations Trait Usage" lang="php">
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

// Get without fallback (returns null if not found)
$post->getTranslation('title', 'de');

// MorphMany relationship (polymorphic strategy)
$post->contentTranslations();
</code-snippet>

## Models

| Model | Table | Key Relationships | Key Fields |
|-------|-------|-------------------|------------|
| `Language` | `languages` | `translations()` HasMany, `contentTranslations()` HasMany | `code` (unique), `name`, `native_name`, `direction` (ltr/rtl), `is_default`, `is_active`, `sort_order` |
| `Translation` | `translations` | `language()` BelongsTo | `language_id`, `group`, `key`, `value` — unique on (language_id, group, key) |
| `ContentTranslation` | `content_translations` | `translatable()` MorphTo, `language()` BelongsTo | `translatable_type`, `translatable_id`, `language_id`, `field`, `value` |

All models are `final class` with `HasFactory` and config-driven table names via `getTable()`.

## Services & Repositories

| Layer | Class | Contract |
|-------|-------|----------|
| Service | `LanguageService` | `LanguageServiceInterface` |
| Service | `TranslationService` | `TranslationServiceInterface` |
| Service | `LocaleService` | (no interface, used for Inertia shared props) |
| Repository | `LanguageRepository` | — |
| Repository | `TranslationRepository` | — |

`LanguageService` methods: `paginate()`, `getActive()`, `getDefault()`, `setDefault()`, `create()`, `update()`, `delete()`
`TranslationService` methods: `paginate()`, `getForLocale()`, `getGroupsForLocale()`, `getAllGroups()`, `create()`, `update()`, `delete()`, `importFromArray()`, `exportToArray()`

## Admin Routes

Prefix: `admin/localizations` — Middleware: `['web', 'auth', 'role:super-admin,admin']`

### Languages
- `GET /languages` — `languages.index` — List with search/pagination
- `GET /languages/create` — `languages.create` — Create form
- `POST /languages` — `languages.store` — Store new language
- `GET /languages/{language}/edit` — `languages.edit` — Edit form
- `PATCH /languages/{language}` — `languages.update` — Update language
- `DELETE /languages/{language}` — `languages.destroy` — Delete language
- `POST /languages/{language}/set-default` — `languages.set-default` — Set as default

### Translations
- `GET /translations` — `translations.index` — List with language/group filters + search
- `GET /translations/create` — `translations.create` — Create form
- `POST /translations` — `translations.store` — Store translation
- `GET /translations/{translation}/edit` — `translations.edit` — Edit form
- `PATCH /translations/{translation}` — `translations.update` — Update translation
- `DELETE /translations/{translation}` — `translations.destroy` — Delete translation

## Vue Composable — useTranslation

Reads `page.props.localization` shared Inertia prop.

<code-snippet name="useTranslation Composable" lang="typescript">
import { useTranslation } from '@/composables/useTranslation';

const { t, te, locale, availableLocales, direction } = useTranslation();

// Dot notation (group.key)
t('messages.welcome');           // looks up translations['messages']['welcome']

// JSON translations (group "*")
t('Welcome!');                   // looks up translations['*']['Welcome!']

// Replacements with :placeholder syntax
t('messages.greeting', { name: 'John' });  // "Hello, :name!" → "Hello, John!"

// Check existence
te('messages.welcome');          // true/false

// Locale info
locale.value;                    // 'en'
direction.value;                 // 'ltr' or 'rtl'
availableLocales.value;          // LocaleData[]
</code-snippet>

## Artisan Commands

- `localization:import {path?} --locale= --group= --overwrite` — Import PHP/JSON translation files into the database
- `localization:export {path?} --locale= --group= --format=json|php` — Export database translations to files

## Configuration

Key config paths in `localization.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `static.driver` | `hybrid` (env: `LOCALIZATION_DRIVER`) | Translation loading driver |
| `content.enabled` | `true` | Enable model content translations |
| `content.strategy` | `polymorphic_table` (env: `LOCALIZATION_CONTENT_STRATEGY`) | Content translation storage strategy |
| `detection.strategy` | `chain` (env: `LOCALIZATION_DETECTION`) | Locale detection strategy |
| `detection.chain` | `['session', 'user_preference', 'browser']` | Resolver chain order |
| `detection.user_column` | `locale` | Column on users table for user_preference resolver |
| `cache.enabled` | `true` (env: `LOCALIZATION_CACHE_ENABLED`) | Enable translation caching |
| `cache.ttl` | `3600` (env: `LOCALIZATION_CACHE_TTL`) | Cache TTL in seconds |
| `cache.store` | `null` (env: `LOCALIZATION_CACHE_STORE`) | Cache store driver |
| `admin.enabled` | `true` | Enable admin panel routes |
| `admin.prefix` | `admin/localizations` | Admin route prefix |
| `admin.middleware` | `['web', 'auth', 'role:super-admin,admin']` | Admin middleware |
| `tables.*` | — | Database table name overrides |
| `models.*` | — | Custom model class overrides |

## Conventions

- `declare(strict_types=1)` and `final class` on all concrete classes
- Form Request classes for validation (never inline)
- Config-driven admin route prefix and middleware
- `AdminNavigation` registry for sidebar items (priority 30)
- Factory classes for all models with custom states (`default()`, `inactive()`, `rtl()`, `forLanguage()`)
- Cache automatically flushed on any write operation
- Language `code` uses ISO 639-1 format (e.g. `en`, `de`, `fr`)

## Common Pitfalls

- The `hybrid` driver merges database on top of file — database translations **override** file translations with the same group/key
- `setDefault()` uses a DB transaction to clear the previous default before setting the new one
- The `user_preference` resolver requires a `locale` column (or configured column) on the users table
- The default language cannot be deleted — attempting to delete it will fail validation
- Translation uniqueness is enforced on the composite `(language_id, group, key)` — not just the key alone
- Content translations with `json_column` strategy require a `{field}_translations` JSON column on the model table
- The `useTranslation` composable requires the `localization` shared prop in `HandleInertiaRequests`

## File Structure

```
packages/laravelplus/localization/
├── config/localization.php
├── database/{migrations,factories,seeders}
├── routes/admin.php
├── skills/localization-development/
└── src/
    ├── Cache/TranslationCache.php
    ├── Console/Commands/{ImportTranslationsCommand,ExportTranslationsCommand}
    ├── Contracts/{TranslationLoaderInterface,LocaleResolverInterface,LanguageServiceInterface,TranslationServiceInterface}
    ├── Facades/Localization.php
    ├── Http/Controllers/Admin/{LanguageController,TranslationController}
    ├── Http/Requests/{StoreLanguageRequest,UpdateLanguageRequest,StoreTranslationRequest,UpdateTranslationRequest}
    ├── Loaders/{FileLoader,DatabaseLoader,HybridLoader}
    ├── Middleware/SetLocale.php
    ├── Models/{Language,Translation,ContentTranslation}
    ├── Repositories/{LanguageRepository,TranslationRepository}
    ├── Resolvers/{SessionResolver,UrlPrefixResolver,UserPreferenceResolver,BrowserResolver,ChainResolver}
    ├── Services/{LanguageService,TranslationService,LocaleService}
    ├── Traits/HasTranslations.php
    └── LocalizationServiceProvider.php
```
