# FrameVAS - Advanced Import for EspoCRM

![EspoCRM 9.0+](https://img.shields.io/badge/EspoCRM-9.0%2B-2f6bb3)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777bb4)
![Languages](https://img.shields.io/badge/languages-English%20%7C%20Ukrainian-4c9a2a)

**English** | [Українська](README.uk.md)

The EspoCRM extension that lets you relate imported records through **Foreign fields**. Standard import can relate records only by a related record's ID or Name. With this extension you can also match by any other field of the related record, such as a code from CRM, ERP or another external ID.

## Why use it

It is can be useful as migration tool when you get data from another system (CRM, ERP) to another and it is important to save dependencies with internal ID keys. So you can export data as Clients > Deals with ID and connections from another system and import them to EspoCRM with using ID from exported system. 

## Usage

1. In **Administration › Entity Manager › (your entity) › Fields**, create a field of type **Foreign**. Choose the link and the field of the related entity to match by. For example, choose link *Client* and field *Client Code*.
2. Start an import. On **Step 2**, the field appears in the mapping list as **Link (Field)**, for example *Client (Client Code)*. It is placed right after *Client (ID)* and *Client (Name)*.
3. Run the import. Each row is related to the record whose field equals the value in the file.

## Features

- Columns are mapped automatically if their header matches the Foreign field name or the option label. The core *Relate on Import* checkbox is not needed. The extension works with custom entities as well.
- English and Ukrainian translations.

## Requirements

- EspoCRM 9.0 or newer (tested on EspoCRM 10)
- PHP 8.3 or newer

## Compatibility note

The extension replaces the core import factory and the Import page controller. It may conflict with another extension that overrides the same parts!

## Installation

1. Download the latest extension package (`.zip`) from the [Releases](../../releases) page.
2. In EspoCRM, go to **Administration → Extensions**.
3. Click **Upload**, choose the downloaded file and click **Install**.
4. Go to **Administration → Clear Cache**.
5. Reload the page in your browser.

## Matching rules

| Situation | Result |
|---|---|
| One related record matches | The record is related. Spaces around the value are ignored. |
| The cell is empty | The row is imported without the relation. |
| No record matches | The row is reported in **Import Errors** (configurable). |
| Several records match | The row is reported in **Import Errors** (configurable). |
| The row also has a *Link (ID)* column | The ID is used. |
| The link has a default value | The value from the file is used. The default applies only when the cell is empty. |
| Updating existing records | The relation changes only when the value in the file differs from the current one. |

Supported field types of the related entity are Varchar, Int, Float, Number, Enum, Enum Int, Enum Float, Url, Email and Phone.

## Settings

Go to **Administration › Data › Advanced Import**.

- **If no related record matches:** report the row as an error (default), or import the row without the relation.
- **If several related records match:** report the row as an error (default), or relate the oldest matching record.

## Permissions

A non-admin user can relate records this way only if they can edit the link and read the related entity type. Otherwise the column is ignored, just like any field the user can't edit.

## Uninstalling

Go to **Administration → Extensions**, find **FrameVAS - Advanced Import** and click **Uninstall**. Or from the command line.

The extension's settings are removed and the standard import behaviour is restored.

## Translations

English and Ukrainian are included. To add a language, copy `/files/custom/Espo/Modules/FrameVasAdvancedImport/Resources/i18n/en_US` to a folder with the EspoCRM language code (e.g. `de_DE`), translate the values (keep keys and placeholders like `{user}` unchanged) and clear the cache.
