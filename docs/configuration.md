# Configuration
This document explains the shared Scenario AI configuration file used by Scenario AI and all framework adapters.

The configuration defines how Scenario AI uses project data and which AI is used.

---

Scenario AI uses two configuration files:

- `scenario.xml` – Defines the Scenario framework configuration, including suites, directories, and extensions.
- `scenario-ai.xml` – Defines the AI adapter and AI-specific settings.

## `maxQuestions`

Controls the configured limit for AI follow-up questions.

```xml
maxQuestions="3"
```

If the value is missing, invalid, or negative, the default value is `3`.

## `projectDoc`

Specifies the path to optional project-specific documentation.

```xml
projectDoc="mydocs"
```

The path is resolved relative to the project root directory.

Project documentation helps the AI understand application-specific conventions, architecture, and requirements.

Only configure documentation that is appropriate to share with the selected AI provider.

## `adapter`

Defines the AI provider used for code generation. You can use `claude` or `openai` or implement use a `custom` adapter.

```xml
<adapter type="openai">
    <var name="apiKey">YOUR_OPENAI_API_KEY</var>
</adapter>
```

The `type` attribute selects the adapter.

Nested `var` elements provide adapter-specific configuration values.

Each variable uses a `name` attribute and a text value.

---

## Next Steps

- [Interactive Conversation](docs/conversation.md)
- [Code Generation Process](docs/generation.md)
- [AI Providers](docs/providers.md)