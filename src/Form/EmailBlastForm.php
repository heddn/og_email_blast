<?php

namespace Drupal\og_email_blast\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EmailBlastForm.
 */
class EmailBlastForm extends FormBase {

  /**
   * Member storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $memberStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The mail plugin manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailPluginManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * EmailController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_plugin_manager
   *   The mail plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_plugin_manager, RendererInterface $renderer) {
    $this->memberStorage = $entity_type_manager->getStorage('og_membership');
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->mailPluginManager = $mail_plugin_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_email_blast_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $group = NULL) {
    $form_state->set('group', $group);
    $options = $this->getEmailOptions($group);
    natcasesort($options);
    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Use this form to send an e-mail message to group members.') . '</p>',
    ];
    $form['subset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send only to certain users?'),
      '#description' => $this->t('Check this box if you only want to message a subset of members. Leave unchecked to send to all members.'),
      '#default_value' => FALSE,
    ];
    $form['to_subset'] = [
      '#type' => 'select',
      '#title' => $this->t('Message these members'),
      '#description' => $this->t('Select the members you want to message.'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#size' => count($options) < 8 ? count($options) : 8,
      '#states' => [
        'visible' => [
          ':input[name="subset"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 78,
      '#size' => 78,
    ];
    $form['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send e-mail'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $form_state->get('group');
    $subset = $form_state->getValue('subset');
    $to_subset = $form_state->getValue('to_subset');
    $subject = $form_state->getValue('subject');
    $message = $form_state->getValue('message');
    $to = $this->getEmailOptions($group);
    if ($subset) {
      $to = array_intersect_key($to, $to_subset);
    }
    $params = [
      'subject' => $subject,
    ];
    $params['message'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($message) {
      $build = [
        '#type' => 'processed_text',
        '#text' => $message['value'],
        '#format' => $message['format'],
      ];
      return $this->renderer->render($build);
    });
    foreach ($to as $uid => $email) {
      $langcode = $this->userStorage->load($uid)->getPreferredLangcode();
      $this->mailPluginManager->mail('og_email_blast', 'group_message', $email, $langcode, $params);
    }
    $this->messenger()->addStatus($this->t('Email message sent.'));
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $group
   *
   * @return array
   *   An array of email addresses for members, keyed by user ID.
   */
  protected function getEmailOptions(EntityInterface $group) {
    $members = $this->memberStorage->loadByProperties([
      'entity_type' => $group->getEntityTypeId(),
      'entity_id' => $group->id(),
      'state' => OgMembershipInterface::STATE_ACTIVE,
    ]);
    return array_replace(...array_map(function (OgMembershipInterface $membership) {
      return [$membership->getOwner()->id() => "{$membership->getOwner()->getAccountName()} <{$membership->getOwner()->getEmail()}>"];
    }, $members));
  }

}
