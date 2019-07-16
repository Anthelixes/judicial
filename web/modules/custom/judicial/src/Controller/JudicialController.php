<?php

namespace Drupal\judicial\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class JudicialController extends ControllerBase {

  public function glossary() {
    return [];
  }

  public function elearning() {


    return [
        '#type' => 'markup',
        '#markup' => '<div class="elearning-slogan">' . new TranslatableMarkup('Get free unlimited access to well-crafted environment-related courses. Start with a foundation course or deepen your knowledge with our advanced courses.') . '</div>'
    ];
  }

  public function home() {
    return [];
  }

}
