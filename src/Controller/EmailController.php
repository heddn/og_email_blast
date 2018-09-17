<?php

namespace Drupal\og_email_blast\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og_email_blast\Form\EmailBlastForm;

/**
 * Class EmailController.
 */
class EmailController extends ControllerBase {

  /**
   * Email route.
   *
   * @return array
   *   Return form render array.
   */
  public function email(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_og_entity_type_id');
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($parameter_name);
    return $this->formBuilder()->getForm(EmailBlastForm::class, $group);
  }

}
