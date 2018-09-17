<?php

namespace Drupal\og_email_blast\EventSubscriber;

use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OgEmailBlastAdminRoutesSubscriber.
 */
class OgEmailBlastAdminRoutesSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideOgPermissions']],
      OgAdminRoutesEventInterface::EVENT_NAME => [['provideAdminRoutes']],
    ];
  }

  /**
   * Provides default OG permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideOgPermissions(PermissionEventInterface $event) {
    $event->setPermissions([
      new GroupPermission([
        'name' => 'email group members',
        'title' => t('Email group members'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
    ]);
  }

  /**
   * Provide OG admin routes.
   *
   * @param \Drupal\og\Event\OgAdminRoutesEventInterface $event
   *   The OG admin routes event object.
   */
  public function provideAdminRoutes(OgAdminRoutesEventInterface $event) {
    $routes_info = $event->getRoutesInfo();
    $routes_info['email_blast'] = [
      'controller' => '\Drupal\og_email_blast\Controller\EmailController::email',
      'title' => 'E-mail',
      'description' => 'Message group members',
      'path' => 'email',
      'requirements' => [
        '_og_user_access_group' => 'email group members',
      ],
    ];
    $event->setRoutesInfo($routes_info);
  }

}
