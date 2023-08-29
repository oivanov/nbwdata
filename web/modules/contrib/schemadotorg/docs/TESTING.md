Schema.org Blueprints: Testing
------------------------------

# Manual UI/UX tests 

(Requires the schemadotorg_demo.module)

```
# Install standard profile.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install;

# Install minimal profile.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install minimal;

# Install Schema.org Blueprint base modules.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install_base;

# Install Schema.org Blueprint base + extras modules.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install_extras;

# Install Schema.org Blueprint standard demo.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard;

# Install Schema.org Blueprint standard demo + translations.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard_translation;

# Install Schema.org Blueprint standard demo + Next.js.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard_next;

# Install Schema.org Blueprint standard demo + translations + Umami.
modules/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard_translation_umami;
```

# Manual JavaScript tests

_The below manual JavaScript tests should be moved to automated tests._

**schemadotorg.autocomplete.js**

@see /admin/reports/schemadotorg

- Check that selected type in form redirects to the type.

@see /admin/structure/types/schemadotorg?type=Person

- Check that opened dialog redirects to the dialog.

**schemadotorg.details.js**

@see /node/add/person

- Check on node via the Schema.org details widget's hide/close state is saved.

**schemadotorg.dialog.js**

@see /admin/structure/types/schemadotorg?type=Person

- Check that links to Schema.org open a modal dialog.

**schemadotorg.form.js**

@see /admin/config/search/schemadotorg/sets/common/setup

- Check that the form is only be submitted once with progress throbber.

**schemadotorg.jstree.js**

@see /admin/reports/schemadotorg/docs/things

- Check that Schema.org type hierarchical tree works as expected.

- Check that Schema.org types link to the Schema.org type details page.

**schemadotorg_ui.js**

@see /admin/structure/types/schemadotorg?type=Person

- Check that the 'Filter by Schema.org property' filters the displayed properties.

- Check that the 'Filter by Schema.org property' can be reset.

- Check that the 'Hide/Show unmapped' link toggles the displayed properties.

- Check that the 'Add new field' summary is updated as the new field is configured.

- Check that adding new field changes to row's status color to warning.

**schemadotorg_ui.field_prefix.js**

@see /admin/structure/types/manage/page/fields/add-field

- Allow the Schema.org field prefix to be selected via the field UI.
  @see /admin/config/search/schemadotorg/settings/properties

- Check that the machine name is updated for field_ and schema_ field prefixes.

**schemadotorg_jsonld_preview.js**

@see /node/add/person

- Check that Schema.org JSON-LD can be copied-n-pasted into the Schema Markup Validator.

**schemadotorg_next_components.js**

@see /node/add/person

- Check that Next.js** component can be downloaded and copied-n-pasted
