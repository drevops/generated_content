<?php

namespace Drupal\generated_content;

/**
 * Class GeneratedContentBatch.
 *
 * Batch processing for generated content items.
 *
 * @package Drupal\generated_content
 */
class GeneratedContentBatch {

  /**
   * Process creation of all entities.
   */
  public static function set($op, $info_items, $total) {
    $batch = [
      'title' => t('Processing generated content'),
      'operations' => [],
      'finished' => '\Drupal\generated_content\GeneratedContentBatch::finished',
    ];

    foreach ($info_items as $info_item) {
      $batch['operations'][] = [
        $op == 'create' ? '\Drupal\generated_content\GeneratedContentBatch::createSingle' : '\Drupal\generated_content\GeneratedContentBatch::removeSingle',
        [$info_item, $total],
      ];
    }

    batch_set($batch);
  }

  /**
   * Create single item batch callback.
   */
  public static function createSingle($info_item, $total, &$context) {
    if (!isset($context['sandbox']['count'])) {
      $context['sandbox']['count'] = 0;
      $context['results']['count'] = 0;
    }

    $repository = GeneratedContentRepository::getInstance();
    $repository->createSingle($info_item);

    $context['sandbox']['count'] += 1;
    $context['results']['count'] += 1;
    $context['finished'] = $context['sandbox']['count'] / $total;

    $context['message'] = t('Creating @entity_type @bundle items', [
      '@entity_type' => $info_item['entity_type'],
      '@bundle' => $info_item['bundle'],
    ]);
  }

  /**
   * Remove single item batch callback.
   */
  public static function removeSingle($info_item, $total, &$context) {
    if (!isset($context['sandbox']['count'])) {
      $context['sandbox']['count'] = 0;
      $context['results']['count'] = 0;
    }

    $repository = GeneratedContentRepository::getInstance();
    $repository->removeSingle($info_item);

    $context['sandbox']['count'] += 1;
    $context['results']['count'] += 1;
    $context['finished'] = $context['sandbox']['count'] / $total;

    $context['message'] = t('Deleting @entity_type @bundle items', [
      '@entity_type' => $info_item['entity_type'],
      '@bundle' => $info_item['bundle'],
    ]);
  }

  /**
   * Finish batch callback.
   */
  public static function finished($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $repository = GeneratedContentRepository::getInstance();
      $repository->clearCaches();

      $message = \Drupal::translation()->formatPlural(
        $results['count'],
        'One item processed.', '@count items processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}
