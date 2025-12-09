# Drupal 11 Headless CMS with Canvas & Next.js Integration

This is a **Drupal 11** headless CMS project using **Canvas** (visual page builder), **Next.js** integration, and custom Tailwind CSS themes. The architecture is decoupled with JSON:API for frontend communication.

## Project Architecture

### Core Technologies
- **Drupal 11.2+** with PHP 8.3
- **Canvas module** (`drupal/canvas`) - Visual page builder for content editing
- **Next.js integration** (`drupal/next`) - Headless frontend framework connector
- **JSON:API** with extras (`drupal/jsonapi_extras`, `drupal/jsonapi_include`) - API layer
- **Decoupled Router** (`drupal/decoupled_router`) - Path resolution for headless apps (patched for multilingual support)
- **Gin** admin theme (`drupal/gin`) - Modern admin UI
- **DDEV** - Local development environment (MariaDB 10.11, nginx-fpm)

### Theme Architecture
Custom themes use a **base/child pattern** with Single Directory Components (SDC):

- **`nxg_base`** (base theme) - Component library in `components/00-layout/`
  - Located: `web/themes/custom/nxg_base/`
  - Defines reusable SDC components (e.g., `container.twig`, `container.component.yml`)
  - No direct styling - provides structure only

- **`hcp_hub`** (child theme) - Production theme extending `nxg_base`
  - Located: `web/themes/custom/hcp_hub/`
  - Uses **Tailwind CSS 4** (CDN-based via `cdn.jsdelivr.net`)
  - Vite build setup in `templates-sample/hcp_hub/` (development reference)
  - Hook classes in `src/Hook/` (Drupal 11.3+ attribute-based hooks)
  - Current preprocessors use procedural code in `hcp_hub.theme` (legacy until 11.3 upgrade)

### Content Management
- **Canvas Pages** - Visual builder entity type for page composition
- Standard content types: Article, Page, Products (with fields: body, image, tags)
- Configuration stored in `config/sync/` (exported/imported via `drush cim/cex`)

### JSON:API Endpoints
Configured for headless consumption with resource overrides:
- `jsonapi_extras.jsonapi_resource_config.canvas_page--canvas_page.yml`
- `jsonapi_extras.jsonapi_resource_config.node--products.yml`
- Preview URL generator: `simple_oauth` with 30s secret expiration

## Developer Workflows

### Environment Setup
```bash
# Install dependencies
composer install

# Start DDEV environment
ddev start

# Fresh site installation (resets database)
composer site:install
# This runs: drush si, resets UUID, imports config from config/sync/

# Import configuration changes
ddev drush cim -y

# Export configuration changes
ddev drush cex -y

# Clear cache
ddev drush cr
```

### Working with Themes
```bash
# Theme locations
web/themes/custom/nxg_base/   # Base component library
web/themes/custom/hcp_hub/    # Active Tailwind theme

# Tailwind development (reference in templates-sample/)
cd templates-sample/hcp_hub/
npm install  # or pnpm install
npm run dev  # Vite dev server on :5173
npm run build  # Build to dist/
```

### Configuration Management
- **Always use config sync** (`drush cim/cex`) - never commit database changes directly
- Configuration lives in `config/sync/` (tracked in git)
- Site UUID is fixed: `66cd78de-ade8-4be2-8380-b041847d3561` (see `composer site:install`)
- DDEV database credentials: `root:root@db/drupal`

### Component Development (SDC)
Single Directory Components pattern (Drupal 11 standard):
```
components/00-layout/container/
  ├── container.twig             # Template
  └── container.component.yml    # Schema (JSON Schema v1)
```

Component schema defines props with types, enums, defaults:
```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Container
props:
  type: object
  properties:
    background:
      type: string
      enum: [none, red, green, blue, yellow, purple]
      default: none
```

### Common Drush Commands
```bash
ddev drush cr              # Clear cache (most common)
ddev drush cim -y          # Import config
ddev drush cex -y          # Export config
ddev drush updb            # Run database updates
ddev drush uli             # One-time login link
ddev drush entity:delete shortcut_set -y  # Remove shortcuts (part of site:install)
```

## Project-Specific Conventions

### File Organization
- **Custom themes**: `web/themes/custom/{theme_name}/`
- **Custom modules**: `web/modules/custom/{module_name}/` (currently none)
- **Configuration**: `config/sync/` (NEVER edit directly - use UI + `drush cex`)
- **Composer packages**: Installed to `web/modules/contrib/`, `web/themes/contrib/`
- **Templates reference**: `templates-sample/` (Vite build examples, not active codebase)

### Ignored Files (Important)
Per `.gitignore`:
- `web/sites/*/settings*.php` - Never commit local settings
- `web/sites/*/files` - User-uploaded content
- `vendor/`, `web/core/`, `web/modules/contrib/`, `web/themes/contrib/`
- `.ddev/config.local.yaml` - Local DDEV overrides

### Code Style
- **Drupal Coding Standards** apply (use `.editorconfig`)
- PHP 8.3+ with strict types: `declare(strict_types=1);`
- Modern Drupal 11 patterns:
  - Attribute-based hooks (when on 11.3+): `#[Hook('preprocess_html')]`
  - Use procedural hooks in `.theme` files until upgrade
  - Drupal's Dependency Injection for services

### Patching
Composer patches enabled (see `patches.lock.json`):
- **decoupled_router** patched for multilingual path resolution
- Patch level for Drupal core: `-p2`
- Use `cweagans/composer-patches` - patches in `composer.json` `extra.patches`

## Integration Points

### Next.js Frontend
- **Module**: `drupal/next` (v2.0)
- **Site Previewer**: iframe mode (100% width, route sync disabled)
- **Preview URL**: Generated via `simple_oauth` (30s expiration)
- Configuration: `config/sync/next.settings.yml`

### JSON:API Customization
- **Resource overrides**: See `jsonapi_extras.jsonapi_resource_config.*` files
- **Include relationships**: Use `jsonapi_include` module for nested data
- **Authentication**: OAuth2 tokens via `simple_oauth` module

### Canvas Page Builder
- Entity type: `canvas_page`
- Visual editing in admin: `/admin/content/canvas`
- View config: `views.view.canvas_pages.yml`
- Component library integration for block placement
- Folders organized by UUID in config

## Common Pitfalls

1. **Config imports failing**: Ensure site UUID matches (`drush cget system.site uuid`)
2. **Cache issues**: Always `drush cr` after code/config changes
3. **Composer patches**: Run `composer install` after modifying patches in `composer.json`
4. **Twig debugging**: Enable `twig.config.debug: true` in `settings.local.php` (not committed)
5. **Headless previews**: Check OAuth tokens and Next.js site configuration
6. **Component changes**: Clear cache after modifying `.component.yml` or `.twig` files

## Key Files Reference
- **Composer setup**: `composer.json` (includes `site:install` script)
- **DDEV config**: `.ddev/config.yaml` (drupal11, PHP 8.3, MariaDB 10.11)
- **Active theme info**: `web/themes/custom/hcp_hub/hcp_hub.info.yml`
- **Theme libraries**: `web/themes/custom/hcp_hub/hcp_hub.libraries.yml`
- **Theme hooks**: `web/themes/custom/hcp_hub/hcp_hub.theme`
- **Canvas config**: `config/sync/canvas.*.yml` (components, folders, asset libraries)
- **JSON:API settings**: `config/sync/jsonapi*.yml`, `config/sync/next.settings.yml`

## When Making Changes

1. **Backend changes** (modules, config): Export with `drush cex`, commit config files
2. **Theme changes**: Edit in `web/themes/custom/`, clear cache, test
3. **Database updates**: Run `drush updb`, export config if schema changes
4. **New dependencies**: Use `composer require`, never edit vendor directly
5. **Testing locally**: Use `ddev describe` for URLs and `ddev logs` for debugging
