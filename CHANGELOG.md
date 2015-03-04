# Changelog

All Notable changes to `League\Container` will be documented in this file

## 1.4.0

### Added
- Added `isRegisteredCallable` method to public API.
- Invoking `call` now accepts named arguments at runtime.

### Fixed
- Container now stores instantiated Service Providers after first instantiation.
- Extending a definition now looks in Service Providers as well as just Definitions.

## 1.3.1 - 2015-02-21

### Fixed
- Fixed bug where arbitrary values were attempted to be resolved as classes.

## 1.3.0 - 2015-02-09

### Added
- Added `ServiceProvider` functionality to allow cleaner resolving of complex dependencies.
- Added `Inflector` functionality to allow for manipulation of resolved objects of a specific type.
- Improvements to DRY throughout the package.

### Fixed
- Setter in `ContainerAwareTrait` now returns self (`$this`).

## 1.2.1 - 2015-01-29

### Fixed
- Allow arbitrary values to be registered via container config.

## 1.2.0 - 2015-01-13

### Added
- Improvements to `Container::call` functionality.

### Fixed
- General code tidy.
- Improvements to test suite.

## 1.1.1 - 2015-01-13

### Fixed
- Allow singleton to be passed as method argument.

## 1.1.0 - 2015-01-12

### Added
- Addition of `ContainerAwareTrait` to provide functionality from `ContainerAwareInterface`.

## 1.0.0 - 2015-01-12

### Added
- Migrated from [Orno\Di](https://github.com/orno/di).
