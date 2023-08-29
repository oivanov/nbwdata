Table of contents
-----------------

* Introduction
* Features
* Requirements
* Configuration
* FAQ


Introduction
------------

The **Schema.org Blueprints JSON:API module** builds on top of the JSON:API
and JSON:API extras modules to apply Schema.org type and property mappings
to JSON:API resources.


Features
--------

- Automatically create JSON:API endpoints for Schema.org type mappings.
- Automatically enable Schema.org properties for JSON:API endpoints.
- Automatically rename JSON:API entity and field names to use corresponding
  Schema.org types and properties.
- Add JSON:API column with links to the Schema.org mappings admin page.
  (/admin/config/search/schemadotorg)


Requirements
------------

**[JSON:API Extras](https://www.drupal.org/project/jsonapi_extras)**    
Provides a means to override and provide limited configurations to the default
zero-configuration implementation provided by the JSON:API in Core.


Configuration
-------------

- Go to the Schema.org JSON:API configuration page.
  (/admin/config/search/schemadotorg/settings/jsonapi)
- Enter base fields that should default be enabled.
- Check/uncheck use Schema.org types as the JSON:API resource's type
  and path names.
- Check/uncheck use Schema.org properties as the JSON:API resource's field
  names/aliases.

