Table of contents
-----------------

* Introduction
* Features
* Configuration
- Optional


Introduction
------------

The **Schema.org Blueprints Taxonomy module** assists with creating and mapping
taxonomy vocabularies and terms.


Features
--------

- Installs the Taxonomy Term Schema.org mapping type.
- Maps terms and vocabularies to Schema.org DefinedTerm and DefinedTermSet or
  CategoryCode and CategoryCodeSet.
- For JSON-LD, return a term's name when associated vocabulary does not have a
  Schema.org mapping.
- Adds links to JSON-LD preview to the JSON-LD Vocabulary endpoint.
- Creates corresponding taxonomy vocabulary for selected Schema.org properties
  (i.e. https://schema.org/recipeCategories).
- Creates selected 'default' taxonomy vocabulary on all Schema.org content types
  (i.e. Tags).


Optional (Recommended)
----------------------

**[Field Group](https://www.drupal.org/project/field_group)**  
Provides the ability to group your fields on both form and display.

**[Entity Reference Tree Widget](https://www.drupal.org/project/entity_reference_tree)**  
Provides an entity relationship hierarchy tree widget for an entity reference field.


Configuration
-------------

- Go to the Schema.org properties configuration page.
  (/admin/config/search/schemadotorg/settings/properties)
- Enter Schema.org properties that should be mapped to a vocabulary.
