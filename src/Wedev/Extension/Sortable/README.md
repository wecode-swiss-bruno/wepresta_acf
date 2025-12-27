# WEDEV Sortable Extension

Generic sortable list functionality for drag-and-drop reordering.

## Features

- Uses SortableJS if available, native HTML5 fallback otherwise
- Order tracking via hidden inputs
- Customizable drag handles
- Callback on sort completion

## JavaScript API

### Basic Usage

```javascript
// Initialize a sortable container
WedevSortable.init(container, {
    itemSelector: '[data-id]',      // Items to sort
    handleSelector: '.drag-handle', // Optional drag handle
    orderInput: '.order-input',     // Hidden input for order
    onSort: (order) => console.log(order)
});

// Auto-init all .wedev-sortable containers
WedevSortable.initAll();
```

### Get Current Order

```javascript
const order = WedevSortable.getOrder(container);
// ['1', '3', '2', '4']
```

## HTML Usage

```html
<ul class="wedev-sortable list-group" data-handle=".drag-handle">
    <li class="list-group-item" data-id="1">
        <span class="drag-handle material-icons">drag_indicator</span>
        Item 1
    </li>
    <li class="list-group-item" data-id="2">
        <span class="drag-handle material-icons">drag_indicator</span>
        Item 2
    </li>
</ul>
<input type="hidden" class="wedev-sortable-order" name="order">
```

## Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-id` | Item identifier (required) |
| `data-handle` | Selector for drag handle (on container) |
| `data-order-input` | Selector for order input (on container) |

## CSS Classes

Uses Bootstrap classes:
- `.opacity-50` for ghost state
- `.list-group` / `.list-group-item` for lists

Custom `.drag-handle` styling:
```css
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
```

