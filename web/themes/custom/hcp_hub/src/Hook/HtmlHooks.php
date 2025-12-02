<?php

declare(strict_types=1);

namespace Drupal\hcp_hub\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks for HTML preprocessing.
 */
class HtmlHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_html')]
  public static function preprocessHtml(array &$variables): void {
    $classes = [
      'bg-white',
      'text-gray-900',
    ];
    // @todo Implement hook and remove procedural code from hcp_hub.theme.
    // when upgraded to 11.3.
    // $variables['attributes']->addClass($classes);
  }

}
