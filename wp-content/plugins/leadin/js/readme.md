# leadin-js

This is a publicly released project. It is not part of the HubSpot infrastructure, we can't use bender-dependencies.

When adding new dependencies via, make sure to add the `--no-default-rc` flag to ignore the global `.npmrc` HubSpot configuration. We want to pull our dependencies from the default yarn registry. For example, `yarn add <dependency> --no-default-rc`.
