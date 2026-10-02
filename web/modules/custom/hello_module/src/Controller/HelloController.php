<?php

namespace Drupal\hello_module\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns a simple greeting page.
 */
class HelloController extends ControllerBase {

  /**
   * Builds the greeting page.
   *
   * @return array
   *   A render array.
   */
  public function hello(): array {
    return [
      '#markup' => $this->t('Bonjour depuis Drupal !'),
    ];
  }

}
