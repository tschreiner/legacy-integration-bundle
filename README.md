WebfactoryLegacyIntegrationBundle
===================================

This bundle provides an approach to integrate existing legacy applications with
Symfony2.

In brief, the approach is to run a particular event listener on the kernel.controller
event. This event listener will decide whether and how to run the legacy application.
The response generated by the legacy application will then be captured.

Later, after the Symfony2 Controller has been executed and a Twig template is being
rendered, fragments of the "legacy response" can be retrieved and be used as part
of the output.

That way, you can start with your legacy applcation and Symfony2 in coexistence
next to each other. You can then incrementally start to shift functionality over
to the Symfony2 stack while maintaining a coherent user experience.

Because Symfony2 is driving this process and Twig templates have the control
authority of which parts of the legacy response are being used, this approach
will naturally "grow" away from the legacy application towards the Symfony2 stack.

*Take care:* This bundle currently uses webfactory/dom, a small set of utilities
  on top of PHP's DOM extension, to process the legacy application's repsonse and
  provide XPath-based access to it. It will only work if your old application
  returns well-formed XHTML or Polyglot HTML5.

To learn more about this concept, its benefits and caveats, use your time-machine
and visit the Symfony2 User Group Colone meeting on 2013-12-18.

Installation
---

Just like any other Symfony2 bundle, left as an exercise to the reader.

Configuration
---

```
webfactory_legacy_integration:
    # Whether your legacy application returns text/html as XHTML 1.0 or Polyglot HTML5.
    parsingMode: xhtml10|html5
    # Bootstrap file for the legacy application (see next section)
    legacyApplicationBootstrapFile: www/index-legacy.php
```

The legacy "bootstrap" file
---

You must provide a single file that can be `include()`ed in order to run your
legacy applcation. Typically you will already have this - it should be your
legacy application's front controller.

This file should _return_ the HTTP status code sent by the legacy application.
This is because the `http_response_code()` function is not available until PHP 5.4.

Also, as this bundle will try to capture the response including headers using
output buffering, you must not flush the response body or headers
to the client.

Using it
---

Once you're set up, use the @Dispatch annotation on your controller to run the
legacy application before your controller - like so:

```php
<?php
namespace Acme\My\Bundle\Controller;

use Webfactory\Bundle\LegacyIntegrationBundle\Integration\Annotation as Legacy;
use ...

class MyController ...
{
    /**
     * @Legacy\Dispatch
     */
    public function myAction()
    {
        return ...
    }
}
```

There are two ways of mixing your legacy world with your new world: Either you create a new layout and embed parts of
the legacy application, or you retain your old layout and embed new parts in it.

### Using XPath to embed parts of the legacy response in your new layout

This is the way we recommend in general. If you're doing your legacy integration for a frontend redesign or if it's not
too hard to rewrite your layout in the Symfony world, you should do that.

Only with this approach you can implement new functionality on the Symfony 2 stack 
that can be executed without running the legacy application at all.

For the use cases that need to be run (partially) in legacy, you can use the Twig global `legacyApplication`
provided by the bundle to access parts of the response and place it in your views.

```twig
{# you_template.html.twig #}
<html>
    ...
    <body>
        ...
        <div id="content">
            {{ legacyApplication.xpath('//*[@id="content"]/*') | raw }}
        </div>
        ...
    </body>
</html>
```

### Replacing arbitrary placeholders in the legacy response with markup generated by Twig

Sometimes you will have to focus on delivering new functionality on the Symfony 2 stack and you will be
stuck with a page layout generated in the legacy application.

This has the disadvantage of having to run the legacy application on every request just to get this layout, 
which is a penalty performance-wise. Even completely new functionality will need to run the legacy application every time.

The two Twig functions `webfactoy_legacy_integration_embed()` and `webfactory_legacy_integration_embed_result()`
help with this style of integration.

* `webfactory_legacy_integration_embed(placeholder, replacement)` will search for arbitrary `placeholder` strings 
  in the legacy application's response. It will then replace those with `replacement`. This function cann be used 
  several times for different replacements.

* `webfactory_legacy_integration_embed_result()` will return the final result after one or more substitutions have
  been performed.

These replacements are best kept in a base layout template in Twig and mapped to Twig blocks, like so:

```twig
{# baseLayout.html.twig #}
{{ webfactory_legacy_integration_embed('<!-- MAIN_CONTENT -->', block('main_content')) }}
{{ webfactory_legacy_integration_embed_result() }}
```

This layout template performs no other output than the one provided by `webfactory_legacy_integration_embed_result()`. 
It can then be extended like this:

```twig
{# new-functionality.html.twig #}
{% extends 'baseLayout.html.twig' %}
{% block main_content %}
    your new content here
{% end block %}
```

This has the benefit of isolating the `new-functionality.html.twig` template from the way it is integrated with the legacy
application. Once you shift the page layout over to the Symfony 2 stack, you can update the `baseLayout.html.twig` template
(of course it should output the `main_content` block as well!) and stop dispatching the legacy application for the new
functionality.

Filters
---

The `LegacyApplicationDispatchingEventListener` can take a set of _Filters_ which
must implement the Webfactory\Bundle\LegacyIntegrationBundle\Integration\Filter interface.

Once the legacy application has been executed, all registered filters are passed
the `FilterControllerEvent` which triggered the event listener as well as the
`Response` object that was created for the legacy application.

The primary use case for this is to be able to examine the response and choose
to send it to the client as-is, bypassing execution of the Symfony2 controller.
That way, you can have routes/controllers in Symfony2 to trigger (or allow) the
execution of particular use-cases in your legacy application while still returning
its response unmodified initially.

You can use the `webfactory_legacy_integration.filter` tag to add more filters.
A more convenient way is the use of additional annotations as follows.

### Filter annotations

The `Webfactory\Bundle\LegacyIntegrationBundle\Integration\Annotation` namespace
contains a few annotations you can use in addition to the `@Legacy\Dispatch` annotation
described earlier.

In particular,

- @Legacy\Passthru will send the legacy application's response as-is, so the controller itself will never be run
- @Legacy\IgnoreRedirect will bypass the controller if the legacy application sent a Location: redirect header.
- @Legacy\IgnoreHeader("some-name") will bypass the controller if the legacy application sent "Some-Name:" header. This can be used to make the legacy application control execution of the Symfony2 controller (use with caution).


Bugs
---

We've used this bundle successfully to slowly migrate a couple of applications
and projects to the Symfony2 stack. Yet it deserves a little more love, fine-polishing,
and documentation. Feel free to open PRs or open issues for it to express your
interest in it.

Oh, and unit tests would be fine :)


