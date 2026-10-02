<?php

namespace Drupal\hello_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelloController extends ControllerBase {

  public function hello(): array {
    return [
      '#markup' => $this->t('Bonjour depuis Drupal !'),
    ];
  }

}