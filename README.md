<div align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=Generated+Content&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Generated Content"></a>
</div>

<h1 align="center">Programmatically generate content for Drupal.</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/drevops/generated_content.svg)](https://github.com/drevops/generated_content/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/drevops/generated_content.svg)](https://github.com/drevops/generated_content/pulls)
[![Test](https://github.com/drevops/generated_content/actions/workflows/test.yml/badge.svg)](https://github.com/drevops/generated_content/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/drevops/generated_content/graph/badge.svg)](https://codecov.io/gh/drevops/generated_content)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/generated_content)
![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-orange.svg)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4.svg)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg)
![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4.svg)
![Drupal 11](https://img.shields.io/badge/Drupal-^11.2-006AA9.svg)
![Drupal 12](https://img.shields.io/badge/Drupal-12-004B7A.svg)

</div>

---

Drupal.org module page: https://www.drupal.org/project/generated_content

## User stories

    As a site owner
    I want to see generated content before I have content
    So that I can see how my site looks

    As a Drupal developer
    I want to control what is put into generated content
    So that I have control over what is being generated

    As a Drupal developer
    I want to have a list of pre-generated pages with URLs
    So that I can use them for Visual Regression testing during site releases

## Installation

    composer require drupal/generated_content

## How it works

1. The module uses Drupal's plugin system to discover content generators.
   Each generator is a PHP class with a `#[GeneratedContent]` attribute in
   your module's `src/Plugin/GeneratedContent/` directory.
2. The module provides a helper (singleton) class to generate random and static
   content. It also supports extending this class in your custom module to
   enhance with your site-specific generation helpers.
3. Generated content entities are tracked in the Repository so that they could
   be referenced from other generated entities (e.g., generated Articles
   using generated Tags).
4. Content can be generated from UI `/admin/config/development/generated-content`
   or through a Drush command `drush generated-content:create-content {entity_type} {bundle}`.
5. Content can also be generated on module install if `GENERATED_CONTENT_CREATE`
   environment variable is set to `1`.
   Generation can be further filtered by specified types in `GENERATED_CONTENT_ITEMS`
   environment variable as a comma-separated list of `{entity_type}-{bundle}`
   values:

       # Generate all items in my_module module when it is enabled.
       GENERATED_CONTENT_CREATE=1 drush pm-enable my_module

       # Generate only selected items in my_module module when it is enabled.
       GENERATED_CONTENT_CREATE=1 GENERATED_CONTENT_ITEMS=media-image,taxonomy_term-tags,node-page drush pm-enable my_module

See test [example module 1](modules/generated_content_example1) and [test example module 2](modules/generated_content_example2) for extensive examples.

The module supports `hook_generated_content_plugin_alter()` to alter plugin
definitions at runtime.

## Difference with Devel Generate

Devel Generate and Generated Content are two different tools for creating
content in Drupal. Devel Generate is mainly used for generating random dummy
content, users, and taxonomy terms for testing and development. It allows you to
specify how many and what types of entities to create, but the content is
random.

On the other hand, Generated Content is for creating specific sets of content
based on predefined settings. It is useful for ensuring the same content is
produced each time, which is helpful for tasks like Visual Regression testing
where consistency is key. Unlike Devel Generate, which is more about quick,
random content, Generated Content is about having control and reproducibility
for structured content setups.

Generated Content does not provide any generators itself, but it allows you to
create your own generators and provides a harness to run them.

## Example to generate Tags

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Plugin\GeneratedContent;

use Drupal\Core\Link;
use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\taxonomy\Entity\Term;

#[GeneratedContent(id: 'my_module_taxonomy_term_tags', entity_type: 'taxonomy_term', bundle: 'tags', weight: 12)]
class TaxonomyTermTags extends GeneratedContentPluginBase {

  public function generate(): array {
    $total_terms_count = 10;

    $terms = [];

    for ($i = 0; $i < $total_terms_count; $i++) {
      $term = Term::create([
        'vid' => 'tags',
        'name' => 'Generated term ' . ($i + 1),
      ]);

      $term->save();

      $terms[] = $term;

      $this->helper::log(
        'Created "%s" term "%s" [ID: %s] %s',
        $term->bundle(),
        $term->toLink()->toString(),
        $term->id(),
        Link::createFromRoute('Edit', 'entity.taxonomy_term.edit_form', ['taxonomy_term' => $term->id()])->toString()
      );
    }

    return $terms;
  }

}
```

## Generation helper

Generation helper class `GeneratedContentHelper` is a Singleton class which
provides:

1. Random non-Drupal scalar values generation.
2. Static non-Drupal scalar values generation.
3. Random asset generator (files of different types).
4. Static asset generator (files from pre-defined assets).
5. Random Drupal entity values generation.
6. Static Drupal entity values generation.

### Extending generation helper

See example of class extension: [`modules/generated_content_example2/src/GeneratedContentExample2Helper.php`](modules/generated_content_example2/src/GeneratedContentExample2Helper.php)

See example of class usage: [`modules/generated_content_example2/src/Plugin/GeneratedContent/NodeArticle.php`](modules/generated_content_example2/src/Plugin/GeneratedContent/NodeArticle.php)

## Random vs Static content

Sometimes, it is sufficient to simply populate entities with random content
to make the site look "not empty". Depending on your deployment strategy (if
you are enabling content generation modules on every deployment on top of the
fresh database), this may change the content on every deployment.

However, there are times when all generated content can still be a "placeholder"
content, but it should be "static" between deployments, so that all content and
it's aliases would not change. This is specifically important for Visual
Regression testing during a release: the tool can compare generated pages with
known aliases in 2 environments and report differences, if any.

## Roadmap

1. Add more random and static generators.
2. Add tests for existing random and static generators.
3. [Suggest yours](https://www.drupal.org/project/issues/generated_content).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for local development, building the site, checking coding standards, and running the tests.

---
_This repository was created using the [Eddy](https://github.com/drevops/eddy) project template_
