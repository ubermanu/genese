---
to: _templates/{{ name }}/{{ action | default('new') }}/hello.php.t
---
---
to: hello.php
---
<?php

var_dump("Hello!
This is your first prompt based Genèse template.
Learn what it can do here:
https://github.com/ubermanu/genese");

var_dump("{{ '{{' }} message | default('') {{ '}}' }}");
