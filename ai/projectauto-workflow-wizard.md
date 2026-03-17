# Projectauto Workflow Wizard

## Predefined-Only Model

Projectauto workflows in this checkout are built from one shared catalog:

- One trigger node
- Zero or one `logic.if_else` node
- One or more action nodes

The wizard payload uses:

```json
{
  "name": "Create invoice after payment",
  "description": "Optional",
  "trigger_type": "payment_status_updated",
  "trigger_config": {},
  "condition": {
    "field": "payment_status",
    "operator": "equals",
    "value": "paid"
  },
  "actions": [
    {
      "type": "create_invoice",
      "config": {
        "location_id": 1,
        "contact_id": 10,
        "products": [
          {
            "product_id": 1,
            "variation_id": 2,
            "quantity": 1,
            "unit_price_inc_tax": 100
          }
        ]
      }
    }
  ]
}
```

## Graph Contract

The builder and wizard both save a graph with:

- `nodes[]`: trigger, optional `logic.if_else`, action nodes
- `edges[]`: trigger `next` to condition or actions; condition `true` / `false` to actions

Condition nodes store:

- `config.condition`: compiled human-readable runtime expression string
- `config.condition_spec`: `{ field, operator, value }`
- Condition fields are filtered by the selected trigger via `supported_triggers`

## Runtime Mapping

Published workflows compile into `projectauto_rules` rows so the existing Projectauto trigger-to-task execution path stays active.

- Direct actions compile without conditions
- `true` branch actions compile with the condition spec
- `false` branch actions compile with the same condition spec plus `negate: true`

## Builder Guardrails

- Only cataloged trigger, logic, and action node types are allowed
- Trigger and action config fields must come from schema metadata (`options` / `enum` when finite)
- Only one trigger is allowed
- Only one condition node is allowed
- Triggers can only connect to the condition node or action nodes
- If a condition node exists, direct trigger-to-action links are invalid
- Condition branches can only connect to action nodes
- Action nodes cannot link outward

## Current Checkout Notes

- The live workflow builder in this checkout is the server-rendered Blade page at `Modules/Projectauto/Resources/views/workflows/build.blade.php`.
- `Modules/Projectauto/Resources/assets/workflow-builder/src/main.js` is a reserved Vite entry, not a separate Vue workflow application.
- Registered triggers currently in scope are:
  - `payment_status_updated`
  - `sales_order_status_updated`
- Those triggers are wired through:
  - `Modules/Projectauto/Workflow/NodeRegistry.php`
  - `Modules/Projectauto/Entities/ProjectautoRule.php`
  - `Modules/Projectauto/Http/Controllers/DataController.php`
- Bounded action fields that now expose finite choices include:
  - `add_product.barcode_type`
  - `create_invoice.status`
  - `create_invoice.discount_type`
  - `create_invoice.payments[].method`
  - `adjust_stock.adjustment_type`
