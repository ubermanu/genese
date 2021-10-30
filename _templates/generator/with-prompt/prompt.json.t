---
to: _templates/{{ name }}/{{ action | default('new') }}/prompt.json
---
[
    {
        "type": "input",
        "name": "message",
        "message": "What's your message?"
    }
]
