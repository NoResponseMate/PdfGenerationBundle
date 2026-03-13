# SyliusPdfBundle - Agent Guide

A Symfony bundle providing context-aware PDF generation with swappable adapter backends.

## What This Bundle Does

Converts HTML (or Twig templates) into PDF files. The caller picks a **context** (e.g. `invoice`, `coupon`), and the bundle routes the request to the correct **adapter** (e.g. KnpSnappy/wkhtmltopdf, Dompdf). Each context can use a different adapter with different options.

## Key Interfaces

```
TwigToPdfRendererInterface::render(string $template, array $parameters = [], string $context = 'default'): string
```
High-level entry point. Renders a Twig template to HTML, then delegates to HtmlToPdfRenderer.

```
HtmlToPdfRendererInterface::render(string $html, string $context = 'default'): string
```
Takes raw HTML, selects the adapter for the given context from a ServiceLocator, and returns PDF bytes.

```
PdfGenerationAdapterInterface::generate(string $html): string
```
Low-level adapter interface. Takes HTML, returns PDF bytes. Adapters are context-unaware.

```
OptionsProcessorInterface::process(object $generator, string $context = 'default'): void
```
Configures the underlying PDF generator object (e.g. `Knp\Snappy\AbstractGenerator` or `Dompdf\Dompdf`) before PDF generation. Multiple processors run in sequence via `CompositeOptionsProcessor`.

```
GeneratorProviderInterface::get(?string $context = null): object
```
Provides the underlying generator instance (e.g. a `Knp\Snappy\Pdf` or `Dompdf\Dompdf`). Used by adapters to get a fresh or shared generator per request.

```
PdfFileManagerInterface::save/remove/has/get(... string $context = 'default'): ...
```
Manages PDF file persistence. `FilesystemPdfFileManager` stores files in per-context directories.

```
PdfFileGeneratorInterface::generate(string $filename, string $content, string $context = 'default'): PdfFile
```
Wraps already-rendered PDF content into a `PdfFile` model and saves it via `PdfFileManagerInterface`. Does not perform rendering itself.

## Architecture

### Request Flow

```
TwigToPdfRenderer
  -> renders Twig template to HTML
  -> HtmlToPdfRenderer
       -> picks adapter from ServiceLocator by context name
       -> Adapter (e.g. KnpSnappyAdapter)
            -> GeneratorProviderRegistry::get(adapterType, context)
                 returns underlying generator (Knp\Snappy\Pdf or Dompdf\Dompdf)
            -> CompositeOptionsProcessor::process(generator, context)
                 runs default processors first, then context-specific ones
            -> generator produces PDF bytes
       -> returns PDF string
```

### Options Processing Chain

Each adapter type has a `CompositeOptionsProcessor` that groups processors by context:

```
CompositeOptionsProcessor (per adapter type)
  'default'           -> [ProcessorA, ProcessorB]     # always run
  'sylius_invoicing'  -> [ProcessorC]                  # run after defaults, only for this context
```

When processing context `sylius_invoicing`, execution order is: ProcessorA, ProcessorB, ProcessorC.
When processing context `default`, only: ProcessorA, ProcessorB.

### DI Wiring

**Extension phase** (`SyliusPdfExtension::load`):
- Processes config, loads `config/services.php` (core services)
- For each context (default + named contexts):
  - If adapter is built-in (`knp_snappy`, `dompdf`): loads adapter service file once, creates a `ChildDefinition` for the context, registers an options processor tagged `sylius_pdf.options_processor`
  - If adapter is unknown: stores in `.sylius_pdf.deferred_adapter_contexts` parameter (dot-prefixed = internal, auto-removed by Symfony)
- Wires the adapter references into `HtmlToPdfRenderer`'s ServiceLocator

**Compiler passes** (in registration order):
1. `RegisterKnpSnappyPrototypePass` - clones the `knp_snappy.pdf` definition as a non-shared prototype so the generator provider can create fresh instances per call
2. `RegisterGeneratorProvidersPass` - collects `sylius_pdf.generator_provider` tagged services into the `GeneratorProviderRegistry`
3. `RegisterOptionsProcessorsPass` - collects all `sylius_pdf.options_processor` tagged services, groups by adapter+context, sorts by priority, builds `CompositeOptionsProcessor` per adapter
4. `RegisterPdfGenerationAdaptersPass` - resolves deferred (custom) adapters by matching `sylius_pdf.adapter` tags to context names, merges into the ServiceLocator

## Configuration

```yaml
sylius_pdf:
    pdf_files_directory: '%kernel.project_dir%/private/pdf'   # root fallback
    default:
        adapter: knp_snappy          # knp_snappy | dompdf | <custom_key>
        pdf_files_directory: ~       # null = inherit root
    contexts:
        invoice:
            adapter: dompdf
            pdf_files_directory: '%kernel.project_dir%/private/invoices'
        coupon:
            adapter: my_custom       # resolved via service tag
```

- `default` is always present; name `default` is forbidden under `contexts`
- Each entry has two fields: `adapter` (string) and `pdf_files_directory` (string|null)
- `pdf_files_directory` cascades: root -> default/context override
- Built-in adapters (`knp_snappy`, `dompdf`) require their packages to be installed; a `LogicException` is thrown otherwise
- Unknown adapter names are deferred to the compiler pass
- Adapter-specific options are not configured via YAML; use custom `OptionsProcessorInterface` implementations instead (see Extension Points)

## Directory Structure

```
src/
  Core/
    Adapter/PdfGenerationAdapterInterface.php
    Attribute/
      AsPdfGenerationAdapter.php      # marks custom adapter classes
      AsPdfOptionsProcessor.php       # marks custom options processors
      AsPdfGeneratorProvider.php      # marks custom generator providers
    Generator/
      PdfFileGenerator.php            # wraps content into PdfFile + saves via manager
      PdfFileGeneratorInterface.php
    Manager/
      FilesystemPdfFileManager.php    # stores PDFs on disk per context
      PdfFileManagerInterface.php
    Model/PdfFile.php                 # simple model (filename + content + mutable fullPath)
    Processor/
      CompositeOptionsProcessor.php   # chains processors by context
      OptionsProcessorInterface.php
    Provider/GeneratorProviderInterface.php
    Registry/
      GeneratorProviderRegistry.php   # keyed by adapter type + context
      GeneratorProviderRegistryInterface.php
    Renderer/
      HtmlToPdfRenderer.php           # selects adapter by context
      HtmlToPdfRendererInterface.php
      TwigToPdfRenderer.php           # Twig -> HTML -> HtmlToPdfRenderer
      TwigToPdfRendererInterface.php
  Bridge/
    KnpSnappy/
      KnpSnappyAdapter.php           # wraps knplabs/knp-snappy-bundle
      KnpSnappyGeneratorProvider.php  # creates fresh Knp\Snappy\Pdf instances via prototype factory
      KnpSnappyOptionsProcessor.php   # sets snappy options + allowed_files
    Dompdf/
      DompdfAdapter.php               # wraps dompdf/dompdf
      DompdfGeneratorProvider.php     # creates fresh Dompdf\Dompdf per call
      DompdfOptionsProcessor.php      # passes options to Dompdf\Options
  DependencyInjection/
    Compiler/
      RegisterKnpSnappyPrototypePass.php       # clones knp_snappy.pdf as non-shared prototype
      RegisterGeneratorProvidersPass.php       # populates provider registry
      RegisterOptionsProcessorsPass.php       # builds composite processors
      RegisterPdfGenerationAdaptersPass.php   # resolves custom adapters
    Configuration.php
    SyliusPdfExtension.php
  SyliusPdfBundle.php
config/
  services.php                # core services (HtmlToPdfRenderer, manager, registry)
  services_twig.php           # TwigToPdfRenderer (conditionally loaded when Twig is installed)
  adapter/knp_snappy.php      # KnpSnappy adapter + processor + provider (abstract)
  adapter/dompdf.php          # Dompdf adapter + processor + provider (abstract)
```

## Extension Points

### 1. Custom Adapter

For integrating a PDF library not bundled with this package.

**Via attribute (recommended):**
```php
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Attribute\AsPdfGenerationAdapter;

#[AsPdfGenerationAdapter('my_adapter')]
final class MyAdapter implements PdfGenerationAdapterInterface
{
    public function generate(string $html): string { /* ... */ }
}
```

**Via service tag:**
```yaml
App\Pdf\MyAdapter:
    tags:
        - { name: 'sylius_pdf.adapter', key: 'my_adapter' }
```

Then reference in config:
```yaml
sylius_pdf:
    default:
        adapter: my_adapter
```

Custom adapters are user-managed services. The bundle does not create instances or pass options to them.

### 2. Custom Options Processor

For modifying the underlying generator's configuration before PDF generation. Useful for adding allowed files, setting paper size, or injecting security options.

**Via attribute:**
```php
use Sylius\PdfBundle\Core\Attribute\AsPdfOptionsProcessor;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;

#[AsPdfOptionsProcessor(adapter: 'knp_snappy', context: 'invoice', priority: 10)]
final class InvoiceOptionsProcessor implements OptionsProcessorInterface
{
    public function process(object $generator, string $context = 'default'): void
    {
        // $generator is Knp\Snappy\AbstractGenerator for knp_snappy
        $generator->setOption('page-size', 'A4');
    }
}
```

**Via service tag:**
```yaml
App\Pdf\InvoiceOptionsProcessor:
    tags:
        - { name: 'sylius_pdf.options_processor', adapter: 'knp_snappy', context: 'invoice', priority: 10 }
```

Tag attributes:
- `adapter` (required): which adapter type this processor applies to
- `context` (optional, default: `'default'`): which context; omit to apply to all contexts using this adapter
- `priority` (optional, default: `0`): higher = runs first

### 3. Custom Generator Provider

For controlling how the underlying generator object is created or reused per context.

**Via attribute:**
```php
use Sylius\PdfBundle\Core\Attribute\AsPdfGeneratorProvider;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

#[AsPdfGeneratorProvider(adapter: 'knp_snappy', context: 'invoice')]
final class InvoiceGeneratorProvider implements GeneratorProviderInterface
{
    public function get(?string $context = null): object
    {
        // Return a custom-configured Knp\Snappy\Pdf instance
    }
}
```

**Via service tag:**
```yaml
App\Pdf\InvoiceGeneratorProvider:
    tags:
        - { name: 'sylius_pdf.generator_provider', adapter: 'knp_snappy', context: 'invoice' }
```

### 4. Replacing Core Services

All core services are aliased by interface. Override via Symfony's service decoration:

```yaml
Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface:     # alias -> sylius_pdf.renderer.html
Sylius\PdfBundle\Core\Renderer\TwigToPdfRendererInterface:     # alias -> sylius_pdf.renderer.twig
Sylius\PdfBundle\Core\Manager\PdfFileManagerInterface:         # alias -> sylius_pdf.manager
Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface:   # alias -> sylius_pdf.adapter.default
Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface:  # alias -> sylius_pdf.registry.generator_provider
```

## Built-in Adapters

### KnpSnappy (`knp_snappy`)
- Requires: `knplabs/knp-snappy-bundle` ^1.10
- Uses wkhtmltopdf binary under the hood
- `KnpSnappyOptionsProcessor` sets options on `AbstractGenerator` and resolves `allowed_files` via `FileLocator`
- `KnpSnappyGeneratorProvider` creates a fresh `Knp\Snappy\Pdf` instance per call via a prototype factory closure (backed by `RegisterKnpSnappyPrototypePass`)

### Dompdf (`dompdf`)
- Requires: `dompdf/dompdf` ^3.1
- Pure PHP, no external binary
- `DompdfOptionsProcessor` passes config options to `Dompdf\Options`
- `DompdfGeneratorProvider` creates fresh `Dompdf\Dompdf` instances

Both adapters are optional dependencies. Configuring an adapter whose package is not installed throws a `LogicException` with a `composer require` hint.

## Development

```bash
vendor/bin/phpunit                              # tests
vendor/bin/phpstan analyse -c phpstan.dist.neon  # static analysis
vendor/bin/ecs check                            # coding standards
```

Tests skip gracefully when optional dependencies are missing via `setUpBeforeClass()` + `markTestSkipped()`.
