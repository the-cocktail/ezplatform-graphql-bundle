# RepositoryForms mutation generation

Using a ContentType object, we can get the Content creation form for it.

Based on this form, we must generate:

A mutation configuration:

```yaml
createFolderContent:
    builder: Mutation
    builderConfig:
        inputType: createFolderContentInput
        payloadType: Payload
        mutateAndGetPayload: "@=mutation('CreateContent', [value])"

createFolderContentInput:
    type: relay-mutation-input
    config:
        fields:
            name:
                type: "String"
            short_name:
                type: "String"
            description:
                type: "String"
            short_description:
                type: "String"
```

- `createFolderContent`, `createFolderContentInput` are based on the
  ContentType's name
- The fields from the Input are generated from the Type's field definitions

## Tools

### LiForm
The liform bundle, used in the jsonschema prototype, provides serialization
of a Form into an array structure. It has Transformers for the various "basic" FormTypes (Text,
Number, Compound...).

We could save development time by either taking inspiration from this library or by hooking into it.
If we are to maintain jsonschema, web forms and other rendering formats, doing so would allow us to
develop custom transformers only once. This also applies to validation.
