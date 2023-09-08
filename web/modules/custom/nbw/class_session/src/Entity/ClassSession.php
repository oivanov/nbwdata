<?php

namespace Drupal\class_session\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\class_session\ClassSessionInterface;
use Drupal\Core\Render\Markup;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the class session entity class.
 *
 * @ContentEntityType(
 *   id = "class_session",
 *   label = @Translation("Class Session"),
 *   label_collection = @Translation("Class Sessions"),
 *   label_singular = @Translation("class session"),
 *   label_plural = @Translation("class sessions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count class sessions",
 *     plural = "@count class sessions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\class_session\ClassSessionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\class_session\Form\ClassSessionForm",
 *       "edit" = "Drupal\class_session\Form\ClassSessionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "class_session",
 *   admin_permission = "administer class session",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/class-session",
 *     "add-form" = "/class-session/add",
 *     "canonical" = "/class-session/{class_session}",
 *     "edit-form" = "/class-session/{class_session}/edit",
 *     "delete-form" = "/class-session/{class_session}/delete",
 *   },
 *   field_ui_base_route = "entity.class_session.settings",
 * )
 */
class ClassSession extends ContentEntityBase implements ClassSessionInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Class Session Notes'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the class session was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the class session was last edited.'));

    return $fields;
  }

  /**
   * Returns a rendered table of the students registered for the class
   * @return array drupal table render array
   */
  public function getClassSessionStudentsTable()
  {
  // \Drupal::messenger()->addMessage(' inside getClassSessionStudentsTable function.');
    $sessionID = $this->id();
    $classSession = \Drupal::entityTypeManager()->getStorage('class_session')->load($sessionID);
    $classEntityRef = $classSession->get('field_class_name')->first();

    $classID = $classEntityRef->get('entity')->getTargetIdentifier();
    //dump($classID);
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'class_roster')
      ->condition('field_class_name', $classID)//field_class_name
      ->condition('status', 1)
      ->sort('title', 'ASC');
    $nid = $query->execute();
    \Drupal::messenger()->addMessage($nid);

    //dump($nid);
    //kint($nid);
    if(!empty($nid)){
      $class_roster = \Drupal::entityTypeManager()
        ->getStorage('node')->loadMultiple($nid);
      //$items = self::parseNodes($class_roster,$classID);
      //dump($class_roster);
      kint($class_roster);
      //->getStorage('node')->load($nid);
    }else{
      dump('No Roster for this class!');
      \Drupal::messenger()->addMessage(' No Roster for this class!');
    }
/*    $class_roster = \Drupal::entityTypeManager()
      ->getStorage('node')->loadMultiple($nids);
    dump($class_roster);
      //->getStorage('node')->load($nid);*/

    $row = [
/*      Markup::create($ownerName . ' - ' . $time . ' ago'),
      Markup::create($price . '$' . $updates),
      Markup::create($link)*/
      Markup::create('Registered Youth First Name'),
      Markup::create('Last Name'),
      Markup::create('Email')
    ];
    $rows[] = $row;

    $build['table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#empty' => t('No students to show. Enroll some!')
    ];

    return [
      '#type' => '#markup',
      '#markup' => \Drupal::service('renderer')->render($build)
    ];

  }

  private static function parseNodes(array $nodes, $class_id): array
  {
    $data = [];

    foreach ($nodes as $key => $node) {

      //field_class_name will have an ID of the "Class" Event node, field_students will have IDs of Youth that are registered for the class

      $title = trim(str_ireplace('Test ', '', $node->getTitle()));

      // Get the Administrative Area code.
      $classEntityRef = $node->get('field_class_name')->first();
      $classEventID = $classEntityRef->get('entity')->getTargetIdentifier();
      if ($classEventID != $class_id){
        continue;
      }
      //#entityTypeId: "node_type" #type: "event"
      $classEvent =  \Drupal::entityTypeManager()->getStorage('node')->load($classEventID);
      $classTitle = $classEvent->getTitle();
      $studentRefs = $node->get('field_students')->getValue();
      $students = [];
      foreach ($studentRefs as $student_reference) {
        //$id = $student_reference["target_id"];
        $account = \Drupal::entityTypeManager()->getStorage('user')->load($student_reference["target_id"]); // pass youth uid
        $address =  $account->get('field_address')->getValue();
        $email = $account->getEmail();
        $firstName = $address[0]['given_name'];
        $lastName = $address[0]['family_name'];
        $students[$student_reference["target_id"]] = $firstName . " " . $lastName . " " . $email;;
      }

      $data = [
        'class_name' => $classTitle,
        'students' => $students,
      ];

    }
    return $data;
  }

}
