<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png" width="300">
        </picture>
    </a>
</p>

<h1 align="center">SyliusPdfBundle</h1>

<p align="center">A Symfony bundle providing a swappable PDF generation abstraction with context-based adapter selection.</p>

## Overview

SyliusPdfBundle decouples PDF rendering from application-specific logic by providing a clean adapter-based architecture. Choose between built-in adapters or register your own - each rendering context can use a different adapter with its own configuration.

**Built-in adapters:**

| Adapter      | Library                                                                 | Requires             |
|--------------|-------------------------------------------------------------------------|----------------------|
| `knp_snappy` | [knplabs/knp-snappy-bundle](https://github.com/KnpLabs/KnpSnappyBundle) | `wkhtmltopdf` binary |
| `dompdf`     | [dompdf/dompdf](https://github.com/dompdf/dompdf)                       | Nothing (pure PHP)   |

## Installation

```bash
composer require sylius/pdf-bundle
```

Install one or both adapter libraries depending on your needs:

```bash
# For wkhtmltopdf-based rendering
composer require knplabs/knp-snappy-bundle

# For pure PHP rendering (no external binary)
composer require dompdf/dompdf
```

Register the bundle if your application doesn't use Symfony Flex:

```php
// config/bundles.php
return [
    // ...
    Sylius\PdfBundle\SyliusPdfBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/sylius_pdf.yaml
sylius_pdf:
    pdf_files_directory: '%kernel.project_dir%/private/pdf'
    default:
        adapter: dompdf
        options:
            defaultPaperSize: a4
    contexts:
        invoice:
            adapter: knp_snappy
            pdf_files_directory: '%kernel.project_dir%/private/invoices'
            options:
                allowed_files:
                    - '%kernel.project_dir%/public/images/logo.png'
```

| Key                   | Description                                                                          |
|-----------------------|--------------------------------------------------------------------------------------|
| `pdf_files_directory` | Root-level fallback directory for storing generated PDF files.                       |
| `default`             | Configuration for the default context (used when no context is specified).           |
| `contexts`            | Named contexts, each with its own adapter, options, and optional directory override. |
| `adapter`             | Adapter name: `knp_snappy`, `dompdf`, or a custom adapter key.                       |
| `options`             | Adapter-specific options passed to the underlying library.                           |

Each context (including `default`) can override `pdf_files_directory`. When omitted, the root-level value is inherited.

## Usage

### Rendering HTML to PDF

Inject `HtmlToPdfRendererInterface` and call `render()`:

```php
use Sylius\PdfBundle\Renderer\HtmlToPdfRendererInterface;

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
use Sylius\PdfBundle\Renderer\TwigToPdfRendererInterface;

$pdfContent = $this->twigRenderer->render(
    'invoice/template.html.twig',
    ['invoiceNumber' => '001'],
);
```

### Using contexts

Pass a context name to route rendering to a specific adapter:

```php
// Uses the 'invoice' context adapter and options
$pdfContent = $this->renderer->render($html, 'invoice');
```

### Managing PDF files

Use `PdfFileGeneratorInterface` to generate and persist PDF files:

```php
use Sylius\PdfBundle\Generator\PdfFileGeneratorInterface;

$pdfFile = $this->generator->generate('invoice_001.pdf', $pdfContent, 'invoice');

$pdfFile->filename();  // 'invoice_001.pdf'
$pdfFile->fullPath();  // '/path/to/private/invoices/invoice_001.pdf'
```

Or use `PdfFileManagerInterface` directly for fine-grained control:

```php
use Sylius\PdfBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Model\PdfFile;

// Save
$this->manager->save(new PdfFile('report.pdf', $content), 'invoice');

// Check existence
$this->manager->has('report.pdf', 'invoice'); // true

// Retrieve
$file = $this->manager->get('report.pdf', 'invoice');

// Remove
$this->manager->remove('report.pdf', 'invoice');
```

## Custom adapters

### Via PHP attribute (recommended)

```php
use Sylius\PdfBundle\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Attribute\AsPdfGenerationAdapter;

#[AsPdfGenerationAdapter('gotenberg')]
final class GotenbergAdapter implements PdfGenerationAdapterInterface
{
    public function generate(string $html): string
    {
        // Your implementation...
    }
}
```

### Via service tag

```yaml
services:
    App\Pdf\GotenbergAdapter:
        tags:
            - { name: 'sylius_pdf.adapter', key: 'gotenberg' }
```

Then reference it by key in configuration:

```yaml
sylius_pdf:
    default:
        adapter: gotenberg
```

## Service reference

| Service ID | Interface | Description |
|------------|-----------|-------------|
| `sylius_pdf.renderer.html` | `HtmlToPdfRendererInterface` | Renders HTML string to PDF |
| `sylius_pdf.renderer.twig` | `TwigToPdfRendererInterface` | Renders Twig template to PDF |
| `sylius_pdf.manager` | `PdfFileManagerInterface` | Stores and retrieves PDF files |
| `sylius_pdf.generator` | `PdfFileGeneratorInterface` | Generates and persists PDF files |

All interfaces are aliased for autowiring.

## Requirements

- PHP >= 8.2
- Symfony ^6.4 or ^7.4

## License

This bundle is released under the [MIT License](LICENSE).
