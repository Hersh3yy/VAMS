# Atomic Design Guide

## Overview
This guide implements Brad Frost's Atomic Design methodology for Vue 3 applications with Tailwind CSS. This pattern can be adapted to any Vue.js project seeking better component organization, reusability, and maintainability.

## Directory Structure

```
components/
├── Base/               # Foundational UI components (auto-import friendly)
│   ├── Button.vue
│   ├── Input.vue
│   ├── Modal.vue
│   ├── Icon.vue
│   └── [foundational components]
├── atoms/              # Specialized atomic components
│   ├── ActivityItem.vue
│   ├── EntityCard.vue
│   └── [atomic components]
├── molecules/          # Simple combinations of atoms
│   ├── FormField.vue
│   ├── ConfirmationDialog.vue
│   └── [molecule components]
├── organisms/          # Complex UI components
│   ├── HeaderActions.vue
│   ├── DashboardStats.vue
│   └── [organism components]
└── [feature-folders]/ # Feature-specific components
    ├── FeatureForm.vue
    └── [feature components]
```

## Design System Levels

### 🔬 Atoms
**Purpose**: Smallest functional units that can't be broken down further

**Examples**: 
- Status icons
- Badge components
- Loading spinners
- Simple card displays
- Upload status icons

**Rules**:
- Single responsibility
- Highly reusable
- Accept props for customization
- No complex business logic
- Follow design tokens (colors, spacing, typography)
- Typically 30-80 lines

**Note**: Foundational UI components (Button, Input, Modal, etc.) are often placed in a `Base/` directory for auto-import convenience, not in atoms/. This is a project preference and can be adapted.

### 🧪 Molecules  
**Purpose**: Simple combinations of atoms with specific functionality

**Examples**:
- Form fields (input + label + error message)
- Progress bars (bar + percentage + status)
- Upload items (icon + progress + status text)
- Confirmation dialogs (modal + buttons)
- Image collections with upload

**Rules**:
- Combine 2-5 atoms/molecules
- Single, focused purpose
- Emit events rather than handling complex logic
- Reusable across different contexts
- Typically 60-150 lines

### 🦠 Organisms
**Purpose**: Complex components that form distinct sections of UI

**Examples**:
- Complete forms (multiple form fields + validation)
- Upload progress systems (header + progress bar + multiple items)
- Dashboard statistics displays
- Navigation headers with actions
- Data tables with filtering

**Rules**:
- May contain atoms, molecules, and other organisms
- Handle specific business logic
- Can manage local state
- Emit events to communicate with parent components
- Typically 100-300 lines

### 📄 Domain-Specific Components
**Purpose**: Components specific to features or domains

**Examples**:
- Feature-specific grids/layouts
- Domain-specific modals/editors
- Business logic components

**Rules**:
- Feature-specific implementations
- Can use Base/, atoms/, molecules/, and organisms/
- Follow atomic design principles when possible
- May exceed typical size guidelines if complexity requires it

## Implementation Guidelines

### Component Naming
- **Base Components**: Descriptive names (e.g., `Button.vue`, `Modal.vue`)
- **Atoms**: Descriptive names (e.g., `ActivityItem.vue`, `StatusIcon.vue`)
- **Molecules**: Descriptive names (e.g., `FormField.vue`, `ProgressBar.vue`)
- **Organisms**: Feature-based names (e.g., `DashboardStats.vue`, `UploadProgress.vue`)
- **Domain Components**: Feature prefix (e.g., `AlbumGrid.vue`, `EntryForm.vue`)

### Props & Events
- **Atoms**: Focus on visual/behavioral props
- **Molecules**: Accept data props, emit user actions
- **Organisms**: Accept complex data objects, emit business events
- **Domain Components**: Accept domain-specific data, emit domain events

### State Management
- **Atoms**: No internal state (except UI state like hover/focus)
- **Molecules**: Minimal internal state for form handling
- **Organisms**: Can manage complex internal state
- **Domain Components**: Handle domain-specific state

### Styling Approach
- Use Tailwind CSS utility classes (or your chosen CSS framework)
- Create component variants through props
- Avoid deep style customization in higher-level components
- Use CSS custom properties for theming
- Support dark mode throughout when applicable

## Best Practices

### Do's ✅
- Keep components focused on single responsibility
- Use TypeScript for prop definitions when possible
- Implement proper error boundaries
- Write comprehensive prop documentation
- Use composition over inheritance
- Prefer props over slots for simple customization
- Keep component files under 300 lines (ideally 60-150 for molecules, 100-300 for organisms)
- Create reusable Base components for foundational UI patterns
- Always use Base components (Button, Modal, etc.) instead of custom implementations
- Extract complex logic to composables
- Follow the atomic hierarchy strictly (atoms don't import organisms)

### Don'ts ❌
- Don't mix business logic with presentation in atoms/molecules
- Don't create overly generic components that try to do everything
- Don't bypass the hierarchy (atoms shouldn't import organisms)
- Don't duplicate styling logic across components
- Don't create components with too many props (>10 is a red flag)
- Don't use custom implementations when Base components exist
- Don't create large monolithic components (>300 lines) without splitting

## Component Size Guidelines

**Recommended:**
- Atoms: 30-80 lines
- Molecules: 60-150 lines
- Organisms: 100-300 lines
- Domain Components: Up to 400 lines (if complexity requires)

**When to Split:**
- Component exceeds 300 lines
- Multiple distinct responsibilities
- Complex conditional rendering that could be separate components
- Repeated patterns that could be extracted

**Refactoring Strategy:**
1. Identify distinct sections/concerns
2. Extract into smaller molecules/atoms
3. Compose parent component using extracted pieces
4. Maintain single responsibility principle

## Upload Progress Pattern Example

**Problem**: Complex upload UI with multiple files, progress tracking, errors

**Solution - Atomic Breakdown:**

1. **Atom**: `UploadItemStatusIcon.vue` - Simple icon for upload status
2. **Molecule**: `UploadProgressBar.vue` - Progress bar with percentage
3. **Molecule**: `UploadItemErrorBox.vue` - Error display with actions
4. **Molecule**: `UploadItem.vue` - Individual file upload item (composes icon + progress + error box)
5. **Molecule**: `UploadProgressHeader.vue` - Header with statistics
6. **Organism**: `UploadProgress.vue` - Complete system (composes all molecules)

**Benefits:**
- Each component is testable independently
- Progress bar can be reused elsewhere
- Error box pattern can be used in other contexts
- Easy to modify individual pieces without affecting others

## Modal Pattern Example

**Problem**: Multiple modals with duplicated markup

**Solution:**
1. Create `Base/Modal.vue` - Generic modal wrapper with slots
2. All specific modals use BaseModal:
   - Confirmation dialogs
   - Form modals
   - Image viewers
   - Editors

**Benefits:**
- Single source of truth for modal behavior
- Consistent accessibility features
- Easier maintenance
- Significant code reduction (600+ lines in many projects)

## Form Pattern Example

**Problem**: Repetitive form field markup

**Solution:**
1. **Molecule**: `FormField.vue` - Input + Label + Error message
2. **Molecule**: `FormField.vue` supports multiple input types (text, textarea, etc.)
3. All forms use FormField molecule

**Benefits:**
- Consistent form styling
- Centralized error display
- Reduced duplication
- Easier to update form styles globally

## Migration Strategy

### Phase 1: Extract Base Components
1. Identify repeated UI patterns (buttons, inputs, modals)
2. Create reusable Base components
3. Replace inline elements with Base components
4. **Benefit**: Immediate consistency

### Phase 2: Build Molecules
1. Identify groups of Base components that commonly appear together
2. Extract into focused molecule components
3. Update organisms to use molecules
4. **Benefit**: Reduced duplication

### Phase 3: Refactor Large Components
1. Identify components >300 lines
2. Break down into smaller molecules/atoms
3. Compose parent using extracted pieces
4. **Benefit**: Better maintainability

### Phase 4: Create Reusable Patterns
1. Extract common patterns (upload progress, form handling)
2. Create reusable composables for logic
3. Document patterns for team
4. **Benefit**: Faster development

## Testing Strategy
- **Atoms**: Focus on visual states and prop variations
- **Molecules**: Test user interactions and event emissions
- **Organisms**: Test business logic and data handling
- **Base Components**: Test thoroughly once, reuse everywhere

## File Organization Tips
- Group related components in the same directory
- Use descriptive file names that match component purpose
- Keep component files under 300 lines
- Extract complex logic to composables
- Follow atomic design hierarchy strictly
- Use feature folders for domain-specific components

## Common Patterns

### Upload System Pattern
```
atoms/UploadStatusIcon.vue
molecules/UploadProgressBar.vue
molecules/UploadItem.vue (uses StatusIcon + ProgressBar)
molecules/UploadItemErrorBox.vue
organisms/UploadProgress.vue (composes all molecules)
```

### Form Pattern
```
Base/Input.vue
Base/Label.vue
molecules/FormField.vue (uses Input + Label)
organisms/FeatureForm.vue (uses multiple FormFields)
```

### Modal Pattern
```
Base/Modal.vue (generic wrapper)
molecules/ConfirmationDialog.vue (uses BaseModal)
organisms/FeatureModal.vue (uses BaseModal + feature-specific content)
```

## Benefits of Atomic Design

### Code Quality
- **Reduced Duplication**: ~600+ lines eliminated in typical projects
- **Consistency**: Single source of truth for UI patterns
- **Maintainability**: Easier to update styles/behavior globally
- **Testability**: Smaller components are easier to test

### Developer Experience
- **Faster Development**: Compose instead of copy-paste
- **Better Discovery**: Clear component hierarchy
- **Easier Onboarding**: New developers understand structure quickly
- **Reusability**: Components can be used across features

### User Experience
- **Consistency**: UI behaves the same way everywhere
- **Accessibility**: Base components handle ARIA, focus management, etc.
- **Performance**: Smaller components can be optimized individually

---

**Note**: This guide is designed to be adapted to any Vue.js project. Adjust directory structure, naming conventions, and patterns to fit your specific needs while maintaining the core atomic design principles.
