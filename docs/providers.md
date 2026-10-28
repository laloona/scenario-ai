# AI Providers

Scenario AI separates the code generation workflow from the AI provider.

This architecture allows different AI providers to be integrated through adapters.

---

## OpenAI

The initial implementation includes an OpenAI adapter.

The adapter communicates with the OpenAI API and processes structured responses.

Example configuration:

```xml
<adapter type="openai">
    <var name="apiKey">YOUR_OPENAI_API_KEY</var>
</adapter>
```

## Claude

The initial implementation includes a Claude adapter.

The adapter communicates with the Claude API and processes structured responses.

Example configuration:

```xml
<adapter type="claude">
    <var name="apiKey">YOUR_OPENAI_API_KEY</var>
</adapter>
```

### Custom Adapters

Scenario AI supports custom adapters.

Custom adapters allow alternative AI providers or implementations to be integrated without changing the core generation workflow.

The adapter system separates provider-specific communication from:

- Instruction generation
- Conversation management
- Response processing
- Code validation
- File generation

Custom adapter implementations must follow the contracts defined by Scenario AI.
