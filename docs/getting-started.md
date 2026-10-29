# Getting Started
This guide complements the README and focuses on main usage.

---

## 1. Install Stateforge Scenario AI

> This package is intended for test and development use only.

Install the Scenario AI package in a project that already uses Stateforge Scenario via Composer as a development dependency.
```bash
composer require --dev stateforge/scenario-ai
```

## 2. Register the Extension

Open your `scenario.xml` configuration file and register the Scenario AI extension:

```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Ai\Extension" />
</extensions>
```

A complete configuration might look like this:

```xml
<scenario xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/stateforge/scenario-core/xsd/scenario.xsd"
    cacheDirectory=".scenario.cache"
    parameterDirectory="scenario/parameter"
>
    <database>
        <connection>migration.php</connection>
    </database>

    <suites>
        <suite name="main" description="Main Scenario Suite">
            <directory>scenario/main</directory>
        </suite>
    </suites>

    <extensions>
        <bootstrap class="Stateforge\Scenario\Ai\Extension" />
    </extensions>
</scenario>
```

The extension integrates Scenario AI into the existing Scenario application.

## 3. Initialize Scenario AI

Run the config command:

```bash
php vendor/bin/scenario ai-config
```

This command initializes the Scenario AI installation and drops the `scenario-ai.xml` into project root.

## 4. Configure the AI Provider

Scenario AI uses a separate XML configuration file named `scenario-ai.xml`.

For example:

```xml
<scenario-ai xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="xsd/scenario-ai.xsd"
    maxQuestions="3"
    projectDoc="mydocs"
>
    <adapter type="openai">
        <var name="apiKey">YOUR_OPENAI_API_KEY</var>
    </adapter>
</scenario-ai>
```

Replace `YOUR_OPENAI_API_KEY` with your own API key.

**Important:** Never commit real API keys or other credentials to a public repository. Keep configuration files containing credentials outside version control.

## Usage

Start the interactive AI code generator:

```bash
php vendor/bin/scenario ai-make
```

Scenario AI displays an introduction and asks you to describe the Scenario or Parameter Type you want to generate.

For example:

```text
Please describe the scenario or parameter type.
```

Enter your requirements in natural language.

Scenario AI analyzes the request and attempts to generate a corresponding PHP implementation.

If additional information is required, the AI may ask follow-up questions or request access to existing application classes.

---

## Next Steps

- [Configuration](configuration.md)
- [Interactive Conversation](conversation.md)
- [Code Generation Process](generation.md)
- [AI Providers](providers.md)****