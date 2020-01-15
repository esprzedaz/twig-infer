
# Twig-Infer

Infer twig template variables.

```php
$template = <<<TPL
{{ a_variable }}
{{ foo.bar }}
{{ attribute(foo, 'data-foo') }}

{{ name|striptags|title }}

{% if post.status is not constant('Post::PUBLISHED') %}
    {{ list|join(', ') }}
{% endif %}

{% for item in navigation %}
    {{ item.href }} - {{ item.caption }}
{% endfor %}

{% for user in users %}
    {{ user.username|e }}

    {% for role in user.roles %}
        {{ role.name }} - {{ role.slug }}

        {% for perm in role.permissions %}
            {{ perm.method }} - {{ perm.path }}
        {% endfor %}
    {% endfor %}

    {% for action in actions %}
        {{ action.url }} - {{ action.name }}
    {% endfor %}
{% endfor %}
TPL;

$twig  = new Environment(new ArrayLoader(['template' => $template]));
$infer = new Infer($twig);

$vars = $infer->variables('template');

// dump $vars
[
    "a_variable" => [],
    "foo"        => [
        "bar"      => [],
        "data-foo" => [],
    ],
    "name"       => [],
    "post"       => [
        "status" => [],
    ],
    "list"       => [],
    "navigation" => [
        [
            "href"    => [],
            "caption" => [],
        ],
    ],
    "users"      => [
        [
            "username" => [],
            "roles"    => [
                [
                    "name"        => [],
                    "slug"        => [],
                    "permissions" => [
                        [
                            "method" => [],
                            "path"   => [],
                        ],
                    ],
                ],
            ],
        ],
    ],
    "actions"    => [
        [
            "url"  => [],
            "name" => [],
        ],
    ],
]
```

The output array keys are the template variables, and the value show the variable structure and usage:  

- Empty array meaning simple variable just to display
- Associative array meaning array or object to display the keys or properties
- Numeric array meaning an array to loop


## Installation

`composer require rookie0/twig-infer`


## License

[MIT](./LICENSE)
