# Changelog

## [1.2.3] - 2026-11-06

### Fixed
- Fixed minor documentation issues.

## [1.2.2] - 2026-11-05

### Fixed
- Fixed minor documentation issues.

## [1.2.1] - 2026-11-03

### Fixed
- Fixed minor documentation issues.

## [1.2.0] - 2026-10-30

### Breaking Changes
- Removed the root-level `bootstrap` attribute from the XML configuration.
- Introduced extension registration through the new `<extensions>` configuration element.
- Existing projects must migrate their bootstrap configuration to the new extension system.

### Added
- Introduced descriptions for suites.
- Added support for loading extensions through the XML configuration.
- Added interface `ApplicationExtension` to extend the framework.
- Added suite filtering to the `apply` and `debug` commands, allowing operations to be restricted to scenarios belonging to a specific suite.

### Migration

Replace the previous root-level `bootstrap` attribute with an extension registration.

Example:
```xml
<extensions>
    <bootstrap class="YourProject\Extension"/>
</extensions>
```

## [1.1.0] - 2026-04-19

### Added
- Introduced extensible parameter type system for validating and converting scenario input values.
- Added support for custom parameter types via `#[AsParameterType]`.
- Added automatic discovery of parameter types from configured parameter directories.
- Added optional conditional parameter type loading via `#[ParameterTypeCondition]`.
- Added `parameter` CLI command to list built-in and registered parameter types.
- Added support for generating custom parameter types via the `make` command.

### Changed
- Moved `ParameterType` from `Stateforge\Scenario\Core\Metadata\Parameter\ParameterType`
  to `Stateforge\Scenario\Core\ParameterType`.

## [1.0.2] - 2026-04-18

### Changed
- Updated PHPUnit version constraints to exclude releases affected by published security advisories.
- Raised minimum supported PHPUnit patch versions for safer dependency resolution.

### Security
- Prevented installation of PHPUnit versions flagged by Packagist security advisories.
- Improved Composer compatibility with secure PHPUnit release lines.

## [1.0.1] - 2026-04-12

### Added
- added logo and badges and minor corrections in docs

## [1.0.0] - 2026-04-06

### Added
- Initial release of the Scenario Core framework.
- Scenario-based system state orchestration.
- CLI support and blueprint-based generation.

### Stability
- Cross-platform support (Linux, Windows).
- Normalized path handling.

### Notes
- First stable release.
- API considered production-ready.
