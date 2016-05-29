PropelMultipleAggregateColumnBehavior
=====================================

[![Build Status](https://secure.travis-ci.org/K-Phoen/PropelMultipleAggregateColumnBehavior.png?branch=master)](https://travis-ci.org/K-Phoen/PropelMultipleAggregateColumnBehavior)

This behavior is an almost exact copy of the bundled aggregate_column behavior,
with the addition of allowing multiple aggregate columns on a single table -
something not possible with the existing behavior.

This behaviors aims to be **fully compatible** with the original aggregate_column
behavior.

## Status

This project is **DEPRECATED** and should NOT be used. 

If someone magically appears and wants to maintain this project, I'll gladly give access to this repository.

## Working with several aggregates

The syntax is pretty straightforward :

```xml
<behavior name="multiple_aggregate_column">
    <parameter name="count" value="2" />

    <parameter name="name1" value="amount_total" />
    <parameter name="foreign_table1" value="invoice_item" />
    <parameter name="expression1" value="SUM(price)" />

    <parameter name="name2" value="amount_paid" />
    <parameter name="foreign_table2" value="invoice_payment" />
    <parameter name="expression2" value="SUM(amount)" />
    <parameter name="condition2" value="status = 1" />
</behavior>
```

If you want to define several aggregates, the _count_ parameter is mandatory.
You will then be able to define as many aggregates as you want.

## Working with a single aggregate

However, if you only have one aggregate, the _count_ parameter can be omitted
and the behavior recognises the same syntax as the old aggregate_column behavior:

```xml
<behavior name="multiple_aggregate_column">
    <parameter name="name" value="amount_total" />
    <parameter name="foreign_table" value="invoice_item" />
    <parameter name="expression" value="SUM(price)" />
</behavior>
```

## Advanced usage

For further information, please refer to [the official Aggregate Column Behavior
documentation](http://propelorm.org/behaviors/aggregate-column.html).


## Credits

  * [Nathan Jacobson](https://github.com/natecj/PropelMultipleAggregateColumnBehavior): original
    author of this behavior. I used his work as a base, made it fully
    functionnal and tested it.

## Licence

MIT, see the LICENCE file.
