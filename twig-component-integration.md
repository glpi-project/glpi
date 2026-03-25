# Symfony UX TwigComponent — GLPI Integration Analysis

## TL;DR

The current integration has a **structural flaw**: two separate Twig environments coexist.
All other problems stem from this. The proper fix is to unify them, but it requires a staged
refactor. This document explains why and proposes a migration path.

---

## Current State

### How the bundle is wired right now

```
TemplateRenderer
  └── own TwigEnvironment (manually built)
        ├── GLPI extensions (ConfigExtension, I18nExtension, …)
        ├── Twig globals (_post, _get, _request)
        ├── ComponentExtension  ← @internal
        └── FactoryRuntimeLoader
              └── ComponentRuntime ← pulled from DI container via alias
                    └── ComponentRenderer
                          └── DI container "twig" service ← different env!

DI container "twig" (TwigBundle-managed)
  └── TwigEnvironment (configured by TwigBundle)
        ├── NO GLPI extensions
        ├── NO GLPI globals
        ├── ComponentExtension  (via twig.extension tag)
        ├── ComponentRuntime    (via twig.runtime tag)
        └── ComponentLexer      (via TwigEnvironmentConfigurator)
```

When `{{ component('Alert', {...}) }}` is called inside a GLPI template:

1. TemplateRenderer's env resolves `component()` → `ComponentRuntime::render()`
2. `ComponentRuntime` calls `ComponentRenderer::createAndRender()`
3. `ComponentRenderer` renders the component template using the **DI `twig` service**

The component template is rendered in a **different** Twig environment.

---

## Problems

### 1. Component templates cannot use GLPI's Twig functions

`ComponentRenderer` injects `new Reference('twig')` — the DI container's Twig env.
GLPI's custom extensions (`config()`, `session()`, i18n, illustrations, routing helpers…) are
registered **only** in `TemplateRenderer`'s env, never as DI-tagged services.

Any component template that calls a GLPI Twig helper will throw a `Twig\Error\RuntimeError`.

### 2. `<twig:Alert>` HTML syntax is broken

`TwigEnvironmentConfigurator` (part of the bundle) installs `ComponentLexer` on the DI `twig`
service. It is **not** installed on `TemplateRenderer`'s env.

The HTML component syntax (`<twig:Alert type="info">…</twig:Alert>`) will silently be treated
as plain HTML and never rendered as a component in GLPI templates.

### 3. `ComponentAttributes` is not HTML-safe in TemplateRenderer

`TwigEnvironmentConfigurator` also registers `ComponentAttributes` as an HTML-safe class.
Without this, passing `ComponentAttributes` through Twig's auto-escaping will double-encode
attribute values, breaking components that spread attributes onto HTML elements.

### 4. Both `ComponentExtension` and `ComponentRuntime` are `@internal`

```php
// vendor/symfony/ux-twig-component/src/Twig/ComponentExtension.php
/** @internal */
final class ComponentExtension extends AbstractExtension { … }

// vendor/symfony/ux-twig-component/src/Twig/ComponentRuntime.php
/** @internal */
final class ComponentRuntime { … }
```

Using internal classes directly means any minor Symfony UX update can silently break the
integration. The internal service ID `ux.twig_component.twig.component_runtime` carries the
same risk.

The public contract of the bundle is `ComponentRendererInterface`. Everything else is an
implementation detail.

---

## Root Cause

GLPI has **two** Twig environments. The bundle was designed for one.

GLPI's `TemplateRenderer` builds its env in isolation (no DI). The DI `twig` service exists
primarily because TwigBundle requires it, but GLPI renders all its templates via
`TemplateRenderer::getInstance()`. Neither env is aware of the other.

The `twig_component.php` alias trick is a bridge to borrow the bundle's runtime from one env
into the other. It works for the happy path but misses lexer setup, global variables, and
GLPI's own extension set.

---

## Option A — Stop-gap (minimal, no refactor)

**Accept `@internal` usage** (documented tech debt) and fix the three missing pieces:

```php
// In TemplateRenderer::registerTwigComponentsRuntime()

// 1. Install ComponentLexer so <twig:Alert> HTML syntax works
$this->environment->setLexer(new ComponentLexer($this->environment));

// 2. Register ComponentAttributes as HTML-safe
if (class_exists(EscaperRuntime::class)) {
    $this->environment->getRuntime(EscaperRuntime::class)
        ->addSafeClass(ComponentAttributes::class, ['html']);
}

// 3. (existing) register the runtime loader
```

This still uses `@internal` classes and still renders component templates in the wrong env.
It is a patch, not a fix.

**When to choose this**: you want zero structural change right now, just correctness for
the `{{ component() }}` call path and HTML syntax.

---

## Option B — The Right Fix (staged, no flag day)

**Unify the two environments.** Make the DI `twig` service BE the env that
`TemplateRenderer` uses, by migrating GLPI's extensions into the DI system.

### Stage 1 — Register GLPI extensions as DI services

In `dependency_injection/services.php`, load the extension namespace:

```php
$services->load('Glpi\Application\View\Extension\\', $projectDir . '/src/Glpi/Application/View/Extension');
```

TwigBundle auto-tags any service that extends `AbstractExtension` with `twig.extension`
(because `autoconfigure` is on). The DI `twig` service now has GLPI's extensions too.

### Stage 2 — Move Twig globals to TwigBundle config

In `dependency_injection/framework.php`, add to the `twig` extension config:

```php
$container->extension('twig', [
    // existing config …
    'globals' => [
        '_post'    => '%env(json:POST_DATA)%',  // or a service factory approach
        '_get'     => …,
        '_request' => …,
    ],
]);
```

Or better: register a `Twig\Extension\GlobalsExtension`-based service that populates these
at render time, so they stay dynamic (same as `$_POST` being live per request).

### Stage 3 — Make TemplateRenderer use the DI env when available

```php
public function __construct(string $rootdir = GLPI_ROOT, ?string $cachedir = null)
{
    $container = $this->getKernelContainer();

    if ($container !== null && $container->has('twig')) {
        // Kernel is available: use the fully-configured DI twig service.
        // All extensions, globals, ComponentLexer, etc. are already set up.
        $this->environment = $container->get('twig');
        return;
    }

    // Fallback: legacy/CLI/bootstrap path — build env manually as before.
    $this->buildStandaloneEnvironment($rootdir, $cachedir);
}
```

After this stage, `registerTwigComponentsRuntime()` disappears entirely.
`ComponentRenderer` and `TemplateRenderer` share the same env.
`<twig:Alert>` works. GLPI helpers work inside component templates. No `@internal` usage.

### Stage 4 — Long-term: remove `static getInstance()`

Once all callers of `TemplateRenderer::getInstance()` are migrated to DI injection,
the static singleton and the fallback standalone env builder can be removed.

---

## Recommendation

**Do Stage 1 + 2 first** (pure additions, zero risk). This gives the DI `twig` service
GLPI's extensions and globals, which is useful regardless.

**Then Stage 3** to unify envs. The complexity lives in Stage 3 because:
- `getKernelContainer()` must be called from `__construct()`, which means `TemplateRenderer`
  keeps a kernel check at boot time.
- The fallback standalone path must remain for CLI tools, install/upgrade scripts,
  and any code path where the kernel is not yet booted.

**Stage 4 is a separate, larger task** (grep for `TemplateRenderer::getInstance()` — many
callers in legacy code).

---

## Files That Would Change

| File | Change |
|------|--------|
| `dependency_injection/services.php` | Add `Glpi\Application\View\Extension\` load |
| `dependency_injection/twig_component.php` | Remove alias hack entirely |
| `src/Glpi/Application/View/TemplateRenderer.php` | Stage 3 constructor change, remove `registerTwigComponentsRuntime()` |
| `src/Glpi/Application/View/Extension/*.php` | No change needed — autoconfigure handles tagging |

---

## What NOT to do

- Do not create a custom class to wrap `ComponentRendererInterface` to avoid `@internal` —
  the public interface only exposes `createAndRender()`, which is not enough for embedded
  components (`startEmbedComponent` / `finishEmbedComponent`). You would end up
  reimplementing `ComponentRuntime` anyway.

- Do not override `ux.twig_component.component_renderer` to inject a different `twig` env —
  this is fighting the framework. Unifying the envs is cleaner.
