# User Preferences for VAMS Development

## Vue Component Structure
The user prefers Vue components to follow this specific structure order:
1. `<template />` - Template section first
2. `<script />` - Script section second  
3. `<style />` - Style section third (if present)

**Note**: Many files currently have script on top, which should be reorganized to match this preference.

## Component Organization Principles
- **Pages directory** should contain only actual Inertia.js pages (Edit.vue, Show.vue, Index.vue, etc.)
- **Components directory** should contain all reusable components organized by domain
- **Pages should compose components, not contain them**
- Follow atomic design principles with proper separation of concerns

## Code Style Preferences
- Use BaseButton component instead of raw `<button>` tags
- Extract reusable components rather than duplicating code
- Prefer component composition over large monolithic files
- Use proper TypeScript types and props definitions

