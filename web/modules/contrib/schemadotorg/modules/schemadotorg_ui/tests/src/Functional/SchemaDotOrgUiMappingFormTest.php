<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org mapping form.
 *
 * @covers \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm
 * @group schemadotorg
 */
class SchemaDotOrgUiMappingFormTest extends SchemaDotOrgBrowserTestBase {
  use MediaTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'media',
    'paragraphs',
    'field',
    'field_ui',
    'file',
    'datetime',
    'image',
    'telephone',
    'link',
    'text',
    'options',
    'schemadotorg_media',
    'schemadotorg_paragraphs',
    'schemadotorg_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->appendSchemaTypeDefaultProperties('Person', [
      'address',
      'affiliation',
      'alumniOf',
      'award',
      'birthDate',
      'contactPoint',
      'jobTitle',
      'nationality',
      'worksFor',
    ]);

    $account = $this->drupalCreateUser([
      'administer user fields',
      'administer content types',
      'administer node fields',
      'administer media types',
      'administer media fields',
      'administer paragraphs types',
      'administer paragraph fields',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org mapping form.
   */
  public function testMappingForm(): void {
    global $base_path;

    $assert_session = $this->assertSession();

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    /* ********************************************************************** */
    // Validation.
    /* ********************************************************************** */

    // Check validating the schema type before continuing.
    $this->drupalGet('/admin/structure/paragraphs_type/schemadotorg', ['query' => ['type' => 'NotThing']]);
    $assert_session->responseContains('The Schema.org type <em class="placeholder">NotThing</em> is not valid.');
    $assert_session->buttonExists('Find');
    $assert_session->buttonNotExists('Save');

    // Check displaying Schema.org type property to field mapping form.
    $this->drupalGet('/admin/structure/paragraphs_type/schemadotorg');
    $this->submitForm(['type' => 'ContactPoint'], 'Find');
    $assert_session->addressEquals('/admin/structure/paragraphs_type/schemadotorg?type=ContactPoint');
    $assert_session->buttonNotExists('Find');
    $assert_session->buttonExists('Save');

    /* ********************************************************************** */
    // ImageObject.
    /* ********************************************************************** */

    // Create 'Image' media type and mapping.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
    $this->drupalGet('/admin/structure/media/manage/image/schemadotorg');
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Created <em class="placeholder">Image</em> mapping.');

    // Check the 'ImageObject' mapping.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $contact_point_mapping */
    $image_object_mapping = SchemaDotOrgMapping::load('media.image');
    $this->assertEquals('media', $image_object_mapping->getTargetEntityTypeId());
    $this->assertEquals('image', $image_object_mapping->getTargetBundle());
    $expected_schema_properties = [
      'created' => 'dateCreated',
      'changed' => 'dateModified',
      'name' => 'name',
      'thumbnail' => 'thumbnail',
      'field_media_image' => 'image',
      'langcode' => 'inLanguage',
    ];
    $actual_schema_properties = $image_object_mapping->getSchemaProperties();
    $this->assertEquals($expected_schema_properties, $actual_schema_properties);

    /* ********************************************************************** */
    // Contact Point.
    /* ********************************************************************** */

    // Create 'Contact Point' paragraph mapping.
    $this->drupalGet('/admin/structure/paragraphs_type/schemadotorg', ['query' => ['type' => 'ContactPoint']]);
    $this->submitForm([], 'Save');
    $assert_session->responseContains('The Paragraphs type <em class="placeholder">Contact Point</em> has been added.');
    $assert_session->responseContains('Added <em class="placeholder">Contact type; Email; Telephone</em> fields.');
    $assert_session->responseContains('Created <em class="placeholder">Contact Point</em> mapping.');

    // Check display warning that new Schema.org type is mapped.
    $this->drupalGet('/admin/structure/paragraphs_type/schemadotorg', ['query' => ['type' => 'ContactPoint']]);
    $assert_session->responseContains('<em class="placeholder">ContactPoint</em> is currently mapped to <a href="' . $base_path . 'admin/structure/paragraphs_type/contact_point">Contact Point</a> (contact_point).');

    // Check validating the bundle entity before it is created.
    $this->submitForm([], 'Save');
    $assert_session->responseContains('A <em class="placeholder">contact_point</em> Paragraphs type already exists. Please enter a different name.');

    // Check validating the new field names before they are created.
    $edit = [
      'mapping[properties][alternateName][field][name]' => '_add_',
      'mapping[properties][alternateName][field][_add_][machine_name]' => '',
      'mapping[properties][contactType][field][name]' => '_add_',
      'mapping[properties][contactType][field][_add_][machine_name]' => 'contact_type',
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('Machine-readable name field is required for the alternateName property mapping.');
    $assert_session->responseContains('A <em class="placeholder">schema_contact_type</em> field already exists. Please enter a different name or select the existing field.');

    // Check the 'Contact Point' paragraph id, title, and description.
    /** @var \Drupal\paragraphs\ParagraphsTypeInterface $contact_point */
    $contact_point = ParagraphsType::load('contact_point');
    $this->assertEquals('contact_point', $contact_point->id());
    $this->assertEquals('Contact Point', $contact_point->label());
    $this->assertEquals('A contact point&#x2014;for example, a Customer Complaints department.', $contact_point->get('description'));

    // Check the 'Contact Point' paragraph form display.
    $contact_point_form_display = $display_repository->getFormDisplay('paragraph', 'contact_point');
    $expected_form_components = [
      'schema_contact_type' => ['type' => 'string_textfield'],
      'schema_email' => ['type' => 'email_default'],
      'schema_telephone' => ['type' => 'telephone_default'],
    ];
    $actual_form_components = $contact_point_form_display->getComponents();
    $this->assertEntityArraySubset($expected_form_components, $actual_form_components);

    // Check the 'Contact Point' mapping.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $contact_point_mapping */
    $contact_point_mapping = SchemaDotOrgMapping::load('paragraph.contact_point');
    $this->assertEquals('paragraph', $contact_point_mapping->getTargetEntityTypeId());
    $this->assertEquals('contact_point', $contact_point_mapping->getTargetBundle());
    $expected_schema_properties = [
      'schema_contact_type' => 'contactType',
      'schema_email' => 'email',
      'schema_telephone' => 'telephone',
    ];
    $actual_schema_properties = $contact_point_mapping->getSchemaProperties();
    $this->assertEquals($expected_schema_properties, $actual_schema_properties);

    /* ********************************************************************** */
    // Person.
    /* ********************************************************************** */

    // Create 'Person' user mapping with gender enumeration for
    // testing purposes.
    $this->drupalGet('/admin/config/people/accounts/schemadotorg');
    $this->submitForm(['mapping[properties][gender][field][name]' => '_add_'], 'Save');
    $assert_session->responseContains('Added <em class="placeholder">Middle name; Address; Affiliations; Alumni of; Awards; Birth date; Contact points; Description; Last name; Gender; First name; Honorific prefix; Honorific suffix; Image; Job title; Knows languages; Nationality; Same as; Telephone; Works for</em> fields.</li>');

    $assert_session->responseContains('Created <em class="placeholder">User</em> mapping.');

    // Check the 'Person' field settings.
    $person_field_definitions = $entity_field_manager->getFieldDefinitions('user', 'user');
    $expected_field_storage_settings = [
      'schema_address' => ['cardinality' => 1],
      'schema_affiliation' => ['cardinality' => -1],
      'schema_alumni_of' => ['cardinality' => -1],
      'schema_award' => ['cardinality' => -1],
      'schema_birth_date' => ['cardinality' => 1],
      'schema_contact_point' => ['cardinality' => -1],
      'schema_description' => ['cardinality' => 1],
      'schema_family_name' => ['cardinality' => 1],
      'schema_gender' => ['cardinality' => 1],
      'schema_given_name' => ['cardinality' => 1],
      'schema_honorific_prefix' => ['cardinality' => 1],
      'schema_honorific_suffix' => ['cardinality' => 1],
      'schema_job_title' => ['cardinality' => 1],
      'schema_knows_language' => ['cardinality' => -1],
      'schema_nationality' => ['cardinality' => 1],
      'schema_same_as' => ['cardinality' => -1],
      'schema_telephone' => ['cardinality' => 1],
      'schema_works_for' => ['cardinality' => -1],
    ];
    $expected_field_settings = [
      'schema_address' => [],
      'schema_contact_point' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'target_bundles' => [
            'contact_point' => 'contact_point',
          ],
        ],
        'target_type' => 'paragraph',
      ],
      'schema_gender' => [
        'allowed_values' => [
          'Female' => 'Female',
          'Male' => 'Male',
          'Unspecified' => 'Unspecified',
        ],
      ],
      'schema_knows_language' => [
        'allowed_values_function' => 'schemadotorg_allowed_values_language',
      ],
      'schema_nationality' => [
        'allowed_values_function' => 'schemadotorg_allowed_values_country',
      ],
      'schema_works_for' => [
        'max_length' => 255,
        'case_sensitive' => FALSE,
        'is_ascii' => FALSE,
      ],
    ];
    $actual_field_storage_settings = [];
    $actual_field_settings = [];
    foreach ($person_field_definitions as $field_name => $person_field_definition) {
      $field_storage_definition = $person_field_definition->getFieldStorageDefinition();
      $actual_field_storage_settings[$field_name] = [
        'cardinality' => $field_storage_definition->getCardinality(),
      ];

      $actual_field_settings[$field_name] = $person_field_definition->getSettings();
    }
    $this->convertMarkupToStrings($actual_field_storage_settings);
    $this->convertMarkupToStrings($actual_field_settings);
    $this->assertEntityArraySubset($expected_field_storage_settings, $actual_field_storage_settings);
    $actual_field_settings = array_intersect_key($actual_field_settings, $expected_field_settings);
    $this->assertEntityArraySubset($expected_field_settings, $actual_field_settings);

    // Check the 'Person' form display.
    $person_form_display = $display_repository->getFormDisplay('user', 'user');
    $expected_form_components = [
      'schema_additional_name' => ['type' => 'string_textfield'],
      'schema_address' => ['type' => 'string_textarea'],
      'schema_affiliation' => ['type' => 'string_textfield'],
      'schema_alumni_of' => ['type' => 'string_textfield'],
      'schema_award' => ['type' => 'string_textfield'],
      'schema_birth_date' => ['type' => 'datetime_default'],
      'schema_contact_point' => ['type' => 'paragraphs'],
      'schema_description' => ['type' => 'text_textarea'],
      'schema_family_name' => ['type' => 'string_textfield'],
      'schema_gender' => ['type' => 'options_select'],
      'schema_given_name' => ['type' => 'string_textfield'],
      'schema_honorific_prefix' => ['type' => 'string_textfield'],
      'schema_honorific_suffix' => ['type' => 'string_textfield'],
      'schema_job_title' => ['type' => 'string_textfield'],
      'schema_knows_language' => ['type' => 'options_select'],
      'schema_nationality' => ['type' => 'options_select'],
      'schema_telephone' => ['type' => 'telephone_default'],
      'schema_same_as' => ['type' => 'link_default'],
      'schema_works_for' => ['type' => 'string_textfield'],
    ];
    $actual_form_components = $person_form_display->getComponents();
    $actual_form_components = array_intersect_key($actual_form_components, $expected_form_components);
    $this->assertEntityArraySubset($expected_form_components, $actual_form_components);

    // Check the 'Person' mapping.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $contact_point_mapping */
    $person_mapping = SchemaDotOrgMapping::load('user.user');
    $this->assertEquals('user', $person_mapping->getTargetEntityTypeId());
    $this->assertEquals('user', $person_mapping->getTargetBundle());
    $expected_schema_properties = [
      'schema_additional_name' => 'additionalName',
      'schema_address' => 'address',
      'schema_affiliation' => 'affiliation',
      'schema_alumni_of' => 'alumniOf',
      'schema_award' => 'award',
      'schema_birth_date' => 'birthDate',
      'schema_contact_point' => 'contactPoint',
      'schema_description' => 'description',
      'mail' => 'email',
      'name' => 'name',
      'schema_family_name' => 'familyName',
      'schema_gender' => 'gender',
      'schema_given_name' => 'givenName',
      'schema_honorific_prefix' => 'honorificPrefix',
      'schema_honorific_suffix' => 'honorificSuffix',
      'schema_job_title' => 'jobTitle',
      'schema_knows_language' => 'knowsLanguage',
      'schema_nationality' => 'nationality',
      'schema_telephone' => 'telephone',
      'schema_same_as' => 'sameAs',
      'schema_works_for' => 'worksFor',
      'schema_image' => 'image',
    ];
    $actual_schema_properties = $person_mapping->getSchemaProperties();
    $this->assertEquals($expected_schema_properties, $actual_schema_properties);

    /* ********************************************************************** */
    // Place.
    /* ********************************************************************** */

    // Create 'Place' node mapping.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Place']]);
    $this->submitForm([], 'Save');
    $assert_session->responseContains('The content type <em class="placeholder">Place</em> has been added.');
    $assert_session->responseContains('Added <em class="placeholder">Address; Image; Telephone</em> fields.');
    $assert_session->responseContains('Created <em class="placeholder">Place</em> mapping.');
  }

  /**
   * Recursively asserts that the expected items are set in the tested entity.
   *
   * A response may include more properties, we only need to ensure that all
   * items in the request exist in the response.
   *
   * @param array $expected
   *   An array of expected values, may contain further nested arrays.
   * @param array $actual
   *   The object to test.
   */
  protected function assertEntityArraySubset(array $expected, array $actual): void {
    foreach ($expected as $key => $value) {
      if (is_array($value)) {
        $this->assertEntityArraySubset($value, $actual[$key]);
      }
      else {
        $this->assertSame($value, $actual[$key]);
      }
    }
  }

}
