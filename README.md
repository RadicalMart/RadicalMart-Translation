# RadicalMart Translation

RadicalMart Translation adds language-specific fields to RadicalMart content forms. When RadicalMart loads content, the plugin applies translations for the active Joomla language. Empty translation fields leave the original values in place.

## Requirements

- RadicalMart
- Joomla 5.4 or later
- PHP 8.2 or later
- Configured Joomla content languages

The Joomla and PHP minimum versions are checked by the plugin installer.

## Installation

Install the plugin package through Joomla's extension installer. A new installation enables the plugin automatically. You can check its status under **Plugins** as **RadicalMart - Translation**.

## Usage

1. Configure the site's content languages and default site language in Joomla.
2. Open a category, product, meta item, fieldset, or field in RadicalMart.
3. Enter content for each language on the **Translation** tab and save.
4. For standard `list` and `checkboxes` fields, translate option text in the field's options.

The original content is used for the default site language. For other languages, only non-empty translated values replace the original values. Translations are entered manually; the plugin does not connect to a machine translation service.

## Supported fields

| Content | Translation fields |
| --- | --- |
| Categories and products | Title, intro text, full text, and SEO parameters |
| Meta items | Title, intro text, full text, and SEO parameters |
| Fieldsets and fields | Title and description |
| Standard `list` and `checkboxes` options | Option text |

## For developers

The plugin stores translations in RadicalMart's `plugins.translation` data and applies them through RadicalMart events. Other plugins can extend its translation forms through `onRadicalMartPrepareTranslateForm`.