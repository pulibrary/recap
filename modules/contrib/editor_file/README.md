# Editor File upload

## Contents of this file

- Introduction
- Requirements
- Recommended Modules
- Installation
- Configuration
- Maintainers

## Introduction

The Editor File upload module allows users to add a button in the Drupal
8 rich text editor active toolbar to directly upload and link files into
the content. Without this module, the writer would have to upload the
files on the webserver via a file field or a FTP connection then
manually create the link.

- For a full description of the module visit:
  (https://www.drupal.org/project/editor_file)

- To submit bug reports and feature suggestions, or to track changes
  visit: (https://www.drupal.org/project/issues/editor_file)

## Requirements

This module requires no modules outside of Drupal core for versions 8 and 9.

Since Drupal 10, CKEditor 4 has been replaced by CKEditor 5 in Drupal Core so
you need to use the [CKEditor 4 - WYSIWYG HTML editor](https://www.drupal.org/project/ckeditor)
module until CKEditor 5 integration is ready (see 2.x branch).

## Recommended modules

- [Editor Advanced link](https://www.drupal.org/project/editor_advanced_link) to
be able to add more attributes (title, id, class, etc.) on the link.

## Installation

Install the Editor File upload module as you would normally install a
contributed Drupal module. Visit [Drupal documentation](https://www.drupal.org/node/1897420) for
further information.

## Configuration

1. Navigate to `Administration > Extend` and enable the module.
2. Navigate to `Administration > Content Authoring > Text formats and editors`
   and choose which which format to edit.
3. Drag the paperclip icon into the "Active toolbar".
4. In the "CKEditor plugin settings" vertical tabs configure the "File upload"
   settings then save the configuration.

Warning: in the text format configuration, if the "Limit allowed HTML
tags and correct faulty HTML filter" is enabled, you should ensure that
dragging the button in the toolbar successfully added the
`data-entity-type` and `data-entity-uuid` attributes to your `<a>` tag.

## Maintainers

- [Edouard Cunibil (DuaelFr)](https://www.drupal.org/u/duaelfr)

### Supporting organization

- [Happyculture](https://www.drupal.org/happyculture)
