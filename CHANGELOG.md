# Changelog

All Notable changes to `League\Container` will be documented in this file

## 2.5.0

### Added
- Support for PHP 7.1+ and 8.0

## 2.4.1

### Fixed
- Ensures `ReflectionContainer` converts class name in array callable to object.

## 2.4.0

### Changed
- Can now wrap shared objects as `RawArgument`.
- Ability to override shared items.

### Fixed
- Booleans now recognised as accepted values.
- Various docblock fixes.
- Unused imports removed.
- Unreachable arguments no longer passed.

## 2.3.0

### Added
- Now implementation of the PSR-11.

## 2.2.0

### Changed
- Service providers can now be added multiple times by giving them a signature.

## 2.1.0

### Added
- Allow resolving of `RawArgument` objects as first class dependencies.

### Changed
- Unnecessary recursion removed from `Container::get`.

## 2.0.3

### Fixed
- Bug where delegating container was not passed to delegate when needed.
- Bug where `Container::extend` would not return a shared definition to extend.

## 2.0.2

### Fixed
- Bug introduced in 2.0.1 where shared definitions registered via a service provider would never be returned as shared.

## 2.0.1

### Fixed
- Bug where shared definitions were not stored as shared.

## 2.0.0

### Added
- Now implementation of the container-interop project.
- `BootableServiceProviderInterface` for eagerly loaded service providers.
- Delegate container functionality.
- `RawArgument` to ensure scalars are not resolved from the container but seen as an argument.

### Altered
- Refactor of definition functionality.
- `Container::share` replaces `singleton` functionality to improve understanding.
- Auto wiring is now disabled by default.
- Auto wiring abstracted to be a delegate container `ReflectionContainer` handling all reflection based functionality.
- Inflection functionality abstracted to an aggregate.
- Service provider functionality abstracted to an aggregate.
- Much bloat removed.
- `Container::call` now proxies to `ReflectionContainer::call` and handles argument resolution in a much more efficient way.

### Removed
- Ability to register invokables, this functionality added a layer of complexity too large for the problem it solved.
- Container no longer accepts a configuration array, this functionality will now be provided by an external service provider package.

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
