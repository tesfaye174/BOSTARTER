# BOSTARTER Core Systems

This directory contains centralized core systems for the BOSTARTER platform. These systems provide
standardized functionality across the application, helping to reduce code duplication and
improve maintainability.

## Systems Overview

### NotificationSystem

Centralized notification management for consistent user alerts.

- Creates standardized notifications for success, error, warning, and info messages
- Handles notification positioning, animation, and auto-dismissal
- Supports accessibility features
- Provides dark mode compatibility

### AnimationSystem

Centralized animation management for consistent UI transitions.

- Provides standardized animations (fade, slide, zoom, etc.)
- Supports staggered animations for multiple elements
- Includes scroll-based animations with Intersection Observer
- Handles reduced motion preferences for accessibility

### Utils

Comprehensive utility functions for common operations.

- Date formatting with internationalization support
- Number formatting (currency, compact notation)
- Text handling (truncation, escaping HTML)
- Progress calculation
- General helpers

### Loader (loader.js)

Entry point for the core systems.

- Initializes all core functionalities
- Makes systems available globally
- Sets up event handling

## Usage

To use these systems in your code, include the loader:

```html
<script src="js/core/loader.js" type="module"></script>
```

This will provide access to all systems through the global `window.BOSTARTER` namespace.

## Migration

Legacy standalone files (animations.js, notifications.js, etc.) are now deprecated.
Please use these centralized systems instead.

## Documentation

For more detailed documentation and usage examples, see the index.html file in this directory.
