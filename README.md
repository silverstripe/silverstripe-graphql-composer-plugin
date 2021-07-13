# Silverstripe GraphQL Composer Plugin

## Overview

Generates the schema files required by
the [silverstripe/graphql](https://github.com/silverstripe/silverstripe-graphql) module
during `composer install` and `composer update`.
Ensures that any module updates result in a consistent runtime state.



Note: Requires plugin execution within composer, which is enabled by default in `composer`.

## Installation

```
composer require silverstripe/graphql-composer-plugin
```

Note: You generally don't need to install this plugin,
it is a requirement of `silverstripe/graphql`.

## Configuration

The plugin runs `sake dev/graphql/build` by default, which builds all schemas.
Set an `SS_GRAPHQL_COMPOSER_BUILD_SCHEMAS` environment constant in order to influence this behaviour.
You can either limit this to a specific schema
([details](https://docs.silverstripe.org/en/4/developer_guides/graphql/getting_started/building_the_schema/)),
or set it to an empty string to disable execution.