---
to: _templates/{{ name }}/{{ action | default('new') }}/prompt.yaml
---
- type: "input"
  name: "message"
  message: "What's your message?"
