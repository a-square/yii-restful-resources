## Getting started

Use [Composer](https://getcomposer.org) to install this package, or just vendor in the files into the `components` directory so that they're loaded before Yii executes its `config.php`.

## Usage

A typical use case is well illustrated by the following snippet:

```php
$config = array(
    // ...
    'components' => array(
        // ...
        'urlManager' => array(
            'rules' => array(
                // plural REST resource describes a collection of entities
                array(
                    'class' => '\Lightsoft\REST\PluralResourceUrlRule',
                    'path' => 'api/echoes', // the pathInfo prefix to be captured
                    // 'controller' => 'api/echoes', // (optional) the full ID of the controller to handle the route, path by default
                    // 'idPattern' => '%\d+%', // (optional) the regex pattern of the entity ID, \d+ by default
                    // 'idName' => 'id', // (optional) $_GET variable to hold the entity ID
                    // 'subresources' => array(/*...*/), // (optional) subresources, PluralResourceUrlRule instances only
                ),
                
                // singular REST resource describes a single entity
                array(
                    'class' => '\Lightsoft\REST\SingularResourceUrlRule',
                    'path' => 'api/echo', // the pathInfo prefix to be captured
                    // 'controller' => 'api/echo', // (optional) the full ID of the controller to handle the route, path by default
                ),
                
                // ...
            ),
        ),
    ),
);
```

### Action mapping

Given the rules in the example, here is how they map to controller actions. Actions are given in the Yii's usual format, e.g. `foo/bar` usually means the action `bar` of the `foo` controller (which could, in turn, map to `FooController::actionBar`), and additional path components map to modules, e.g. `baz/foo/bar` means that Yii will search for `FooController` in the `baz` module. Yii supports reusable actions, in which case the action ID will be mapped differently. Below we give the *method signature* as it would be had the corresponding action been implemented as a simple method in a controller. As usual with Yii, all parameters in the method signature are optional and are simply shortcuts to `$_GET` variables. Things like `$id [= :id]` mean that the value of `$id` corresponds to the part of the URL for which `:id` is a placeholder.

All paths may optionally end with `/`, it is ignored. Query string parameters are processed as usual.

#### Plural resource

Verb   | Path            | Action ID         | Action method signature
-------|-----------------|-------------------|---------------------------------------------------
GET    | /echoes         | echoes/index      | `EchoesController::actionIndex()`
GET    | /echoes/:id     | echoes/view       | `EchoesController::actionView($id [= :id])`
POST   | /echoes         | echoes/create     | `EchoesController::actionView($id [= :id])`
PUT    | /echoes/:id     | echoes/update     | `EchoesController::actionUpdate($id [= $id])`
DELETE | /echoes/:id     | echoes/destroy    | `EchoesController::actionDestroy($id [= $id])`
GET    | /echoes/:id/foo | echoes/viewFoo    | `EchoesController::actionViewFoo($id [= $id])`
PUT    | /echoes/:id/foo | echoes/updateFoo  | `EchoesController::actionUpdateFoo($id [= $id])`
DELETE | /echoes/:id/foo | echoes/destroyFoo | `EchoesController::actionDestroyFoo($id [= $id])`

#### Singular resource

Verb   | Path            | Action ID         | Action method signature
-------|-----------------|-------------------|---------------------------------------------------
GET    | /echo           | echo/view         | `EchoController::actionView()`
POST   | /echo           | echo/create       | `EchoController::actionCreate()`
PUT    | /echo           | echo/update       | `EchoController::actionUpdate()`
DELETE | /echo           | echo/destroy      | `EchoController::actionDestroy()`
GET    | /echo/foo       | echo/viewFoo      | `EchoController::actionViewFoo()`

## TODO

- [ ] Documentation
  - [ ] Basic usage
  - [ ] Complete parameters description and what they do
  - [ ] Limitations
    - [ ] Nesting limitations
    - [ ] Verb table limitations
    - [ ] Custom methods limitations
    - [ ] Impossibility of URL generation
    - [ ] What standard URL rule options and URL Manager options are ignored
  - [ ] Error handling
    - [ ] HTTP Exceptions during rule parsing
    - [ ] Usage of the `Allowed` HTTP header
  - [ ] Performance
- [ ] Unit testing **(priority!)**
- [ ] Refactoring
  - [ ] Factor out the common code of the two classes
- [ ] Features
  - [ ] Remove nesting limitations
  - [ ] Remove the verb table limitations
  - [ ] Remove custom method limitations
    - [ ] Make verb support uniform
    - [ ] Add PATCH and OPTIONS

## History

The library was originally developed for [Lightsoft Research](http://lightsoft.ru) as a part of larger REST toolkit, and then opensourced with the company's permission.

The namespace `Lightsoft\REST` was left in so that an improved version of the library could be seamlessly integrated with the company's existing projects.