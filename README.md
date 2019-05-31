# Filter\FilterBundle

**Filter\FilterBundle** is a bundle for Symfony2 that is supposed to help with all the burden of creating **forms** that work as queries on **Doctrine** entities.

In other words, if you got *that* list of products and your user needs to be able to query on it (by name, or price, or anything at all), this bundle got you covered.

Example:

![example](http://i.imgur.com/dR5ZXxy.png)

---
# The basics

The bundle will inject a service called **filter**. You will use it in your controller like this:

```php
$filterManager = $this->get('filter');
```

Now you need a **FilterBuilder**, and this is how you get one:

```php
$filterBuilder = $this->get('filter')->createFilterBuilder('FilterCoreBundle:Customer');
```

Pay attention to the Doctrine Entity path above. You always want to create a filter over some entity.

After creating the **FilterBuilder**, you can start building your filter like this:

```php
$filter = $this->get('filter')
               ->createFilterBuilder('FilterCoreBundle:Customer')
               ->addField('firstName')
               ->addField('lastName')
               ->addField('cpf')
               ->addField('company.cnpj')
               ->build()
          ;
```

Note the **build()** method call at the end of the chain: this actually gives you a **Filter** object from the **FilterBuilder**.

Fields of the filter are specified using a simple notation that supports Doctrine Entity relations with a dot (.). You can note that in the **company.cnpj** example above.

After all that, you can just return this **$filter** object to your template, like this:

```php
    /**
     * @Route("/example", name="example")
     * @Template()
     */
    public function exampleAction()
    {
        $filter = $this->get('filter')
            ->createFilterBuilder('FilterCoreBundle:Customer')
            ->addField('firstName')
            ->addField('lastName')
            ->addField('cpf')
            ->addField('company.cnpj')
            ->build()
            ;
        return array(
            'filter' => $filter,
        );
    }
```

The **$filter** object has some useful methods. The most useful, of course, is the **getResult()** one. This will actually give you the result of the filter, like the result of a query on the specified entity.

You can use that on your template, like this:

```html
{{ filter_render(filter) }}

{% for customer in filter.result %}
    {{ customer.name }}
{% endfor %}
```

Note how we used a Twig function that is exposed by this bundle, the **filter_render**. This function takes a **Filter** object and outputs the HTML markup for it.

After that, we iterate through the filter.result, which is translated by the Twig engine to **$filter->getResult()**, to output every customer name.

---
# Pagination

Most of the time, you want to paginate things. Selecting **all** the rows of a large table will kill you, and it will kill you even more when using Doctrine as we are.

There is a shortcut for that, as we will see, that looks like this:

```php
        $filter = $this->get('filter')
            ->createFilterBuilder('FilterCoreBundle:Customer')
            ->addField('firstName')
            ->addField('lastName')
            ->addField('cpf')
            ->addField('company.cnpj')
            ->addPagination(20)
            ->build()
            ;
```

The **FilterBuilder::addPagination()** method receives the number of entities that should be returned on each row, and does the magic for you. It uses the **KNP Paginator** behind the scenes, so you can use the regular knp paginator functions in your template:

```html
{{ knp_pagination_render(filter.result, 'KnpPaginatorBundle:Pagination:twitter_bootstrap_pagination.html.twig') }}
```

This will render the pages ruler.

---
# Caching

Another issue that usually comes up is caching. Doctrine got you covered on that, and the FilterBuilder also has a shorthand for it:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Customer')
    ->addField('firstName')
    ->addField('lastName')
    ->addField('cpf')
    ->addField('company.cnpj')
    ->addPagination(20)
    ->addCache(60 * 5)
    ->build()
    ;
```

The **FilterBuilder::addCache()** method will cache every query it makes for the number of seconds you pass to it.

---
# Query Fragments

Sometimes, you need to force some constraints on your query. Maybe you want to query only the last month data, or build a filter only for things that happened *today*, not your whole table. You need control over the "WHERE" of the internal query.

This can be achieved by adding **query fragments**. Query Fragments are pieces of query that will always happen. You actually get a Doctrine's Query Builder and you have freedom to do whatever you want with it.

Example:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Transaction')
    ->addField('id')
    ->addField('status')
    ->addOrder('processedAt', 'DESC')
    ->addEagerLoad('customer')
    ->forcePartialLoad()
    ->addPagination(10)
    ->addCache(60 * 5)
    ->addQueryFragment(
        function ($qb, $alias) {
            $qb->andWhere(sprintf('%s >= :minimum_processed_at', $alias('localProcessedAt')));
            $qb->setParameter(':minimum_processed_at', date('Y-m-d', strtotime('-6 months')));
        }
    )
    ->build()
    ;
```

The Query Fragment is a closure that receives a QueryBuilder and another closure, the $alias function. The $alias function allows you to keep using the "dot notation" to refer to the fields of your entity. These functions will, at execution time, return the actual SQL alias that is being used inside the Query Builder for the correct field. We need it because you do not know how the Query Builder is calling the fields at "compile time".

---
# Eager Loading

**Doctrine** will eat you up if you let it. The default behavior of **Doctrine** when dealing with an object that has lots of relations is to query all the needed relations separately. This means that if your entity got 10 relationships and you need them all, **Doctrine** will make 1 query for your entity and then 10 more queries: one for each relationship.

It works like this because of the **lazy loading** design of Doctrine. It is very good sometimes, but consuming in others. If you want to **force** some joins so that you can condense all those queries in only one query, you can use the **FilterBuilder::addEagerLoad** method:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Customer')
    ->addField('firstName')
    ->addField('lastName')
    ->addField('cpf')
    ->addField('company.cnpj')
    ->addEagerLoad('company')
    ->addPagination(20)
    ->addCache(60 * 5)
    ->build()
    ;
```

---
# Partial Loading

If you need just a *few* columns of a table full of columns, you can use the **Partial Loading** feature from **Doctrine**. The **FilterBuilder::forcePartialLoad()** will take care of that: only . Note, however, that only the necessary keys for the filter will be selected.

Example:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Customer')
    ->addField('firstName')
    ->addField('lastName')
    ->addField('cpf')
    ->addField('company.cnpj')
    ->addEagerLoad('company')
    ->forcePartialLoad()
    ->addPagination(20)
    ->addCache(60 * 5)
    ->build()
    ;
```

---
# Widgets

Each field has a type. This is defined in the **Doctrine** entity, like **integer**, **string**, **datetime** or **entity** (in the case of a relationship, for example: customer.group).

The **Filter** already knows the default widgets for each type, when it comes to rendering the form. That means you got a text field for strings, a calendar for dates and so on.

You can, however, overwrite that by passing some information along with the field. Example:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Transaction')
    ->addField('id')
    ->addField('status')
    ->addField('amount', array('widget' => 'money'))
    ->addField('discount', array('widget' => 'money'))
    ->build()
    ;
```

If the **money** widget was not specified, only a common field for numbers would have been rendered. But now, a nice and formatted money field will be there.

Some widgets, like the multiselect, can receive auxiliar data in order to render themselves. Example:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Transaction')
    ->addField('id')
    ->addField('status', array(
        'widget' => 'multiselect',
        'data' => array('good' => 'good', 'bad' => 'bad')),
    )
    ->build()
    ;
```

---
# Default values

You can pass default values to any widget, but you need to know it's format. Here we have two examples: the default value format of a text field widget, which is just a string, and the default value format of a daterange, which is an array with "from" and "to" keys.

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:Transaction')
    ->addField('id')
    ->addField('customer.firstName', array('value' => 'kurt'))
    ->addField('customer.lastName', array('value' => 'gödel'))
    ->addField(
        'localProcessedAt',
        array(
            'value' => array(
                 'from' => date('Y-m-d', strtotime('-1 week')),
                 'to' => null,
             )
         )
     )
```

---
# Actions

Until now, we have only used the bundle in order to abstract simple queries on **Doctrine** entities. It is often the case, however, that you need to perform some complex task with the result of the filter, for example: you need a button to export every row queried, or a button to send an e-mail, or you need to actually mitigate the query before execute it.

All non standard functionality can be achieved by using the feature of Actions. Everything the Filter does is an action. To understand the concept of actions, you need to understand the internal architecture of the Filter. See below:


    @filter                       FilterBuilder
        Manager                            +------------+
       +-------+                           |            |----* fields
       |       |   createFilterBuilder()   |            |----* order
       |       |==========================>|            |----* cache
       |       |                           |            |----* pagination
       +-------+                           |            |----* actions
                                           +------------+      query builder
                                                 ||            etc...
                                                 ||
                                                 || build()
                                                 ||
                                                 ||
                                                 \/
                                              ++++++++
                                             /  which \
                                            /  action  \
                                           +    was     +
                                           +  executed? +
                                            \          /
                                             \        /
                                              ++++++++
                                                 ||
                                                 ||
                                                 || decide which action was executed
                                                 ||
                                                 \/
                           +---------------------------------------------+
                           |                                             |
                           |    +-------+               * Property tree  |
                           |    |query  |              / \               |
                           |    |builder|             /   \              |
                           |    |       |            *     \             |
                           |    +-------+           / \     \            |
                           |                       /   *     \           |
                           |    +-------+         /           *          |
                           |    |widgets|        /           / \         |
                           |    |       |       *           /   \        |
                           |    |       |                  *     \       |
                           |    +-------+                         *      |
                           |                                             |
                           +---------------------------------------------+
                                                 ||
                                                 || prepare() the executed action
                                                 || execute() the executed action
                                                 ||
                                                 \/
                                               Filter
                                              +-------+
                                              |       |---* result
                                              |       |---* fields
                                              |       |---* executed action
                                              +-------+

First, you get the Manager, that is the service registered as @filter. Then, you use the manager to create a FilterBuilder. After that, it is your job to configure the FilterBuider with all sorts of features you want on your filter: fields, sorting, query fragments, actions...

When you are ready, run the build() method on the FilterBuilder, and the magic happens:

1) Before anything, the FilterBuilder checks which action is being executed. It might be a simple query or some button pressed.

2) After choosing the executed action, the FilterBuilder **prepare()** that action. More on that later.

3) Then, the FilterBuilder creates lots of internal stuff to build the actual Filter: it has an internal QueryBuilder, an internal Property tree and all of it's widgets.

4) After having everything ready and the executed action prepared, the FilterBuilder actually **execute()** the executed action and stores its result.

5) The FilterBuilder finally builds a Filter that contains the result of all the work and returns it.

## So, what is this Action?

The **Action** is any class that implements the **Filter\FilterBundle\Service\Filter\Action** interface.
This is how the interface looks:

```php
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

interface Action
{
    public function prepare(QueryBuilder $qb, callable $alias, Property $root);
    public function execute(Query $query);
}
```

Anything that has a **prepare()** and an **execute()** methods with those signatures is a valid action.

The prepare() method is executed just after the FilterBuilder decides which action is being executed. It looks a lot like a QueryFragment and it is basically the same thing. The only difference is the presence of the $root parameter $root is the actual root of the Property tree mentioned above. You can use it to tweak stuff if you want.

The execute() method receives the actual Query already built and cached (it has the cache configured automatically). You should just getResult() to get its result.

## Cool. How do I make use of that?

You can add arbitrary actions to your filter form like this:

```php
$filter = $this->get('filter')
    ->createFilterBuilder('FilterCoreBundle:NfeNota')
    ->addCache(50)
    ->addField('id')
    ->addField('status')
    ->addField('chaveAcesso')
    ->addField('numero')
    ->addField('order.id')
    ->addField('lote.id')
    ->addField('protocolo')
    ->addField('dataHoraRecebimento')
    ->addField('sefazStatus')
    ->addField('sefazMotivo')
    ->addField('createdAt')
    ->addOrder('numero', 'DESC')
    ->addPagination(20)
    ->addAction('nfe_xmls_action', new NfeXmlExportAction())
    ->addAction('nfe_csv_action', new CsvExportAction(
        array(
            'id',
            'chaveAcesso',
        )
    ))
    ->build()
    ;
```

As you can see, two actions were added. You can also note that the actions have **names**. Every action need a unique name for that filter. This name will let you use the translation mechanism to translate it to something user friendly.

There is also another thing that actions can have besides a name: roles. Example:

```php
->addAction(
    'customer_list_csv_action',
    new CsvExportAction(
        array(
            'id',
            'status',
            'firstName',
            'lastName',
            'cpf',
            'company.cnpj',
            'type',
            'gender',
            'phone',
            'mobilePhone',
            'registrationMcc',
            'registrationEmail',
            'group.id',
            'createdAt',
        )
    ),
    array(
        'ROLE_SUPERVISOR',
        'ROLE_COMMERCIAL',
    )
```

The **Filter\FilterBundle\Action\CsvExportAction** is an example of built-in useful Action that comes with the bundle. You can have your own if you want.

## Result actions.

There is also another interface, called CountableAction. It extends the Action but adds one method. The **count()** method:

```php
interface CountableAction extends Action
{
    public function count();
}
```

This makes possible to display the result count when rendering the filter.

We need it because the action that happens when you press the "filter" button is also an implementation of the **Action**. It is called the ResultAction, and you can overwrite the default one. Example:

```php
$filter = $this
    ->get('filter')
    ->createFilterBuilder('FilterCoreBundle:OrderItemStatusLog')
    ->addField('createdAt', array(
        'value' => array(
            'from' => date('Y-m-d', strtotime('7 days ago')),
            'to' => date('Y-m-d', strtotime('tomorrow')),
        ),
    ))
    ->addResultAction(new StatusPanelAction())
    ->build()
    ;
```

Now the actual submit of the form created by the filter will trigger this custom StatusPanelAction.

---
# TODO

 - Submit the form when pressing the enter button.
 - Add a way to overwite widget templates.
 - Explain how to add custom widgets.
 - Implement the ResultAction as a regular Action with a special name ("result", maybe?).
 - Make it possible to specify the name of each column in the CsvExportAction.
 - Fix the order of the rendered fields.
 
