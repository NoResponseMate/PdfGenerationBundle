<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png" width="300">
        </picture>
    </a>
</p>

<h1 align="center">PdfGenerationBundle</h1>

<p align="center">A Symfony bundle providing a swappable PDF generation abstraction with context-based adapter selection.</p>

## Overview

This bundle decouples PDF rendering from application-specific logic by providing a clean adapter-based architecture. Choose between built-in adapters or register your own - each rendering context can use a different adapter with its own configuration.

**Built-in adapters:**

| Adapter      | Library                                                                 | Requires                    |
|--------------|-------------------------------------------------------------------------|-----------------------------|
| `knp_snappy` | [knplabs/knp-snappy-bundle](https://github.com/KnpLabs/KnpSnappyBundle) | `wkhtmltopdf` binary        |
| `dompdf`     | [dompdf/dompdf](https://github.com/dompdf/dompdf)                       | Nothing (pure PHP)          |
| `gotenberg`  | [gotenberg/gotenberg-php](https://github.com/gotenberg/gotenberg-php)   | Running Gotenberg container |

## Installation

```bash
composer require sylius/pdf-generation-bundle
```

Install an adapter library depending on your needs:

```bash
# For wkhtmltopdf-based rendering
composer require knplabs/knp-snappy-bundle

# For pure PHP rendering (no external binary)
composer require dompdf/dompdf

# For Gotenberg (Docker-based headless Chromium)
composer require gotenberg/gotenberg-php
```

Register the bundle if your application doesn't use Symfony Flex:

```php
// config/bundles.php
return [
    // ...
    Sylius\PdfGenerationBundle\SyliusPdfGenerationBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/sylius_pdf_generation.yaml
sylius_pdf_generation:
    gotenberg:
        base_url: 'http://localhost:3000'
    default:
        adapter: dompdf
        storage:
            type: filesystem
            directory: '%kernel.project_dir%/var/pdf'
    contexts:
        invoice:
            adapter: knp_snappy
            storage:
                type: flysystem
                filesystem: 'default.storage'
                prefix: 'invoices'
                local_cache_directory: '%kernel.project_dir%/var/pdf_cache'
```

| Key                             | Description                                                                                                  |
|---------------------------------|--------------------------------------------------------------------------------------------------------------|
| `default`                       | Configuration for the default context (used when no context is specified).                                   |
| `contexts`                      | Named contexts, each with its own adapter and optional storage override.                                     |
| `adapter`                       | Adapter name: `knp_snappy`, `dompdf`, `gotenberg`, or a custom adapter key.                                  |
| `gotenberg.base_url`            | URL of the Gotenberg server (default: `http://localhost:3000`). Required when using the `gotenberg` adapter. |
| `storage.type`                  | Storage backend: `filesystem` (default), `flysystem`, or `gaufrette`.                                        |
| `storage.filesystem`            | Flysystem/Gaufrette filesystem service ID (e.g. `default.storage`).                                          |
| `storage.prefix`                | Path prefix for Flysystem/Gaufrette storage.                                                                 |
| `storage.directory`             | Local directory path (required for `filesystem` type only).                                                  |
| `storage.local_cache_directory` | Local cache path for `resolveLocalPath()` (Flysystem/Gaufrette only).                                        |

Each context (including `default`) can override `storage`. When omitted, the default storage configuration is inherited. The context name `default` is reserved and cannot be used inside `contexts`.

## Usage

### Rendering HTML to PDF

Inject `HtmlToPdfRendererInterface` and call `render()`:

```php
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRendererInterface;

final class InvoiceController
{
    public function __construct(
        private readonly HtmlToPdfRendererInterface $renderer,
    ) {}

    public function download(): Response
    {
        $pdfContent = $this->renderer->render('<html><body>Invoice #001</body></html>');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
```

### Rendering Twig templates to PDF

Inject `TwigToPdfRendererInterface` to render a Twig template directly:

```php
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;

$pdfContent = $this->twigRenderer->render(
    'invoice/template.html.twig',
    ['invoiceNumber' => '001'],
);
```

### Using contexts

Pass a context name to route rendering to a specific adapter:

```php
// Uses the 'invoice' context adapter
$pdfContent = $this->renderer->render($html, 'invoice');
```

### Managing PDF files

Use `PdfFileGeneratorInterface` to generate and persist PDF files:

```php
use Sylius\PdfGenerationBundle\Core\Generator\PdfFileGeneratorInterface;

$pdfFile = $this->generator->generate('invoice_001.pdf', $pdfContent, 'invoice');

$pdfFile->filename();  // 'invoice_001.pdf'
$pdfFile->storagePath();  // storage-relative path (e.g. '/path/to/private/invoices/invoice_001.pdf')
```

Or use `PdfFileManagerInterface` directly for fine-grained control:

```php
use Sylius\PdfGenerationBundle\Core\Filesystem\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Core\Model\PdfFile;

// Save
$this->manager->save(new PdfFile('report.pdf', $content), 'invoice');

// Check existence
$this->manager->has('report.pdf', 'invoice'); // true

// Retrieve
$file = $this->manager->get('report.pdf', 'invoice');

// Remove
$this->manager->remove('report.pdf', 'invoice');

// Resolve absolute local path (e.g. for email attachments)
$localPath = $this->manager->resolveLocalPath('report.pdf', 'invoice');
```

## Extending the bundle

### Custom adapters

#### Via PHP attribute (recommended)

```php
use Sylius\PdfGenerationBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfGenerationAdapter;

#[AsPdfGenerationAdapter('my_adapter')]
final class MyCustomAdapter implements PdfGenerationAdapterInterface
{
    public function generate(string $html): string
    {
        // Your implementation...
    }
}
```

#### Via service tag

```yaml
services:
    App\Pdf\MyCustomAdapter:
        tags:
            - { name: 'sylius_pdf_generation.adapter', key: 'my_adapter' }
```

Then reference it by key in configuration:

```yaml
sylius_pdf_generation:
    default:
        adapter: my_adapter
```

Custom adapters are user-managed services - the bundle does not pass configuration options to them.

### Custom generator providers

Generator providers create the underlying PDF library instance (e.g. a `Dompdf` or `Knp\Snappy\GeneratorInterface` object). Register a custom provider to control how the generator is instantiated:

#### Via PHP attribute

```php
use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfGeneratorProvider;
use Sylius\PdfGenerationBundle\Core\Provider\GeneratorProviderInterface;

#[AsPdfGeneratorProvider(adapter: 'dompdf', context: 'invoice')]
final class InvoiceDompdfProvider implements GeneratorProviderInterface
{
    public function get(string $context = 'default'): \Dompdf\Dompdf
    {
        $dompdf = new \Dompdf\Dompdf();
        // Custom setup for the invoice context...
        return $dompdf;
    }
}
```

#### Via service tag

```yaml
services:
    App\Pdf\InvoiceDompdfProvider:
        tags:
            - { name: 'sylius_pdf_generation.generator_provider', adapter: 'dompdf', context: 'invoice' }
```

The `key` must match the adapter name. The optional `context` scopes the provider to a specific context; without it, the provider is used as the default for that adapter.

### Custom options processors

Options processors configure the generator instance before PDF generation. They receive the generator object and modify it directly:

#### Via PHP attribute

```php
use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfOptionsProcessor;
use Sylius\PdfGenerationBundle\Core\Processor\OptionsProcessorInterface;

#[AsPdfOptionsProcessor(adapter: 'dompdf', context: 'invoice', priority: 10)]
final class InvoicePaperSizeProcessor implements OptionsProcessorInterface
{
    public function process(object $generator, string $context = 'default'): void
    {
        /** @var \Dompdf\Dompdf $generator */
        $generator->setOptions(new \Dompdf\Options(['defaultPaperSize' => 'a4']));
    }
}
```

#### Via service tag

```yaml
services:
    App\Pdf\InvoicePaperSizeProcessor:
        tags:
            - { name: 'sylius_pdf_generation.options_processor', adapter: 'dompdf', context: 'invoice', priority: 10 }
```

Tag attributes:

| Attribute  | Required | Description                                                                |
|------------|----------|----------------------------------------------------------------------------|
| `adapter`  | Yes      | The adapter type this processor applies to (e.g. `dompdf`, `knp_snappy`).  |
| `context`  | No       | Limits the processor to a specific context. Omit to apply to all contexts. |
| `priority` | No       | Higher values run first. Defaults to `0`.                                  |

Processors without a `context` attribute run for every context of their adapter type. Context-specific processors run after the default ones.

## Service reference

| Service ID                                          | Interface                            | Description                      |
|-----------------------------------------------------|--------------------------------------|----------------------------------|
| `sylius_pdf_generation.renderer.html`               | `HtmlToPdfRendererInterface`         | Renders HTML string to PDF       |
| `sylius_pdf_generation.renderer.twig`               | `TwigToPdfRendererInterface`         | Renders Twig template to PDF     |
| `sylius_pdf_generation.manager`                     | `PdfFileManagerInterface`            | Stores and retrieves PDF files   |
| `sylius_pdf_generation.generator`                   | `PdfFileGeneratorInterface`          | Generates and persists PDF files |
| `sylius_pdf_generation.registry.generator_provider` | `GeneratorProviderRegistryInterface` | Resolves generator providers     |

All interfaces (except internal composites) are aliased for autowiring.

## License

This bundle is released under the [MIT License](LICENSE).
