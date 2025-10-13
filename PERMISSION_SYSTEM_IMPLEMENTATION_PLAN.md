# 🔐 Permission System Implementation Plan

## 📋 Overview

This plan outlines the step-by-step implementation of a comprehensive permission system for VAMS that enables subscription-based access control. Users will have different access levels based on their subscription tier (e.g., Albums-only vs. Full access including Mosaics).

---

## 🎯 **Implementation Phases**

### **Phase 1: Database Foundation** (2 hours)

#### **1.1 Create Permission Tables**
```bash
# Commands to run:
herd php artisan make:migration create_permissions_table
herd php artisan make:migration create_user_permissions_table
herd php artisan make:migration create_subscription_tiers_table
herd php artisan make:migration create_tier_permissions_table
herd php artisan make:migration add_subscription_tier_to_users_table
```

#### **1.2 Migration Files to Create:**
- `create_permissions_table.php` - Store all available permissions
- `create_user_permissions_table.php` - Link users to specific permissions
- `create_subscription_tiers_table.php` - Define subscription tiers (Free, Pro, Enterprise)
- `create_tier_permissions_table.php` - Link tiers to permissions
- `add_subscription_tier_to_users_table.php` - Add tier reference to users

#### **1.3 Database Schema:**
```sql
-- Permissions table
permissions: id, name, description, category, created_at, updated_at

-- User permissions (many-to-many)
user_permissions: id, user_id, permission_id, created_at, updated_at

-- Subscription tiers
subscription_tiers: id, name, description, price, created_at, updated_at

-- Tier permissions (many-to-many)
tier_permissions: id, tier_id, permission_id, created_at, updated_at

-- Users table addition
users: ... existing fields ..., subscription_tier_id (foreign key)
```

---

### **Phase 2: Model Implementation** (2 hours)

#### **2.1 Create Models**
```bash
# Commands to run:
herd php artisan make:model Permission
herd php artisan make:model SubscriptionTier
herd php artisan make:model UserPermission
herd php artisan make:model TierPermission
```

#### **2.2 Model Relationships:**
```php
// User Model Updates
- belongsTo(SubscriptionTier::class)
- belongsToMany(Permission::class, 'user_permissions')
- hasPermission(string $permission): bool
- hasFeature(string $feature): bool
- assignTier(SubscriptionTier $tier): void

// Permission Model
- belongsToMany(User::class, 'user_permissions')
- belongsToMany(SubscriptionTier::class, 'tier_permissions')

// SubscriptionTier Model
- hasMany(User::class)
- belongsToMany(Permission::class, 'tier_permissions')
```

---

### **Phase 3: Permission Definitions** (1 hour)

#### **3.1 Create Permission Seeder**
```bash
herd php artisan make:seeder PermissionSeeder
herd php artisan make:seeder SubscriptionTierSeeder
```

#### **3.2 Define Permissions:**
```php
// Album permissions
'albums.view', 'albums.create', 'albums.update', 'albums.delete'

// Mosaic permissions  
'mosaics.view', 'mosaics.create', 'mosaics.update', 'mosaics.delete'

// Admin permissions
'admin.users.view', 'admin.users.create', 'admin.users.update', 'admin.users.delete'

// Future entity permissions
'portfolios.view', 'portfolios.create', 'portfolios.update', 'portfolios.delete'
```

#### **3.3 Define Subscription Tiers:**
```php
// Free Tier (Albums only)
- albums.view, albums.create, albums.update, albums.delete

// Pro Tier (Albums + Mosaics)
- All album permissions
- mosaics.view, mosaics.create, mosaics.update, mosaics.delete

// Enterprise Tier (All features)
- All album and mosaic permissions
- portfolios.view, portfolios.create, portfolios.update, portfolios.delete
- admin.users.view (limited admin access)
```

---

### **Phase 4: Policy Updates** (2 hours)

#### **4.1 Update Existing Policies**
- `AlbumPolicy.php` - Add permission checks
- `MosaicPolicy.php` - Add permission checks
- Create `UserPolicy.php` for admin functions

#### **4.2 Policy Method Updates:**
```php
// Example: AlbumPolicy
public function viewAny(User $user): bool
{
    return $user->hasPermission('albums.view');
}

public function create(User $user): bool
{
    return $user->hasPermission('albums.create');
}

// Example: MosaicPolicy  
public function viewAny(User $user): bool
{
    return $user->hasPermission('mosaics.view');
}

public function create(User $user): bool
{
    return $user->hasPermission('mosaics.create');
}
```

---

### **Phase 5: Frontend Integration** (3 hours)

#### **5.1 Create Permission Composable**
```typescript
// resources/js/composables/usePermissions.ts
- hasPermission(permission: string): boolean
- hasFeature(feature: string): boolean
- canAccess(route: string): boolean
```

#### **5.2 Update Vue Components**
- Add permission checks to navigation menus
- Hide/show features based on user permissions
- Add upgrade prompts for missing features
- Update dashboard to show available features

#### **5.3 Component Updates:**
```vue
<!-- Navigation Menu -->
<nav v-if="hasFeature('albums')">
  <Link :href="route('albums.index')">Albums</Link>
</nav>

<nav v-if="hasFeature('mosaics')">
  <Link :href="route('mosaics.index')">Mosaics</Link>
</nav>

<!-- Dashboard -->
<div v-if="!hasFeature('mosaics')" class="upgrade-prompt">
  <h3>Unlock Mosaics</h3>
  <p>Upgrade to Pro to create beautiful mosaic layouts.</p>
  <Link :href="route('subscription.upgrade')">Upgrade Now</Link>
</div>
```

---

### **Phase 6: Testing Implementation** (2 hours)

#### **6.1 Create Permission Tests**
```bash
herd php artisan make:test PermissionSystemTest --pest
herd php artisan make:test SubscriptionTierTest --pest
herd php artisan make:test UserPermissionTest --pest
```

#### **6.2 Test Scenarios:**
- User with Free tier can only access albums
- User with Pro tier can access albums and mosaics
- User with Enterprise tier can access all features
- Permission checks work in policies
- Frontend permission checks work correctly
- Subscription tier changes update permissions

---

### **Phase 7: Admin Interface** (2 hours)

#### **7.1 Create Admin Components**
- `SubscriptionTierManager.vue` - Manage subscription tiers
- `UserPermissionManager.vue` - Manage user permissions
- `PermissionDashboard.vue` - Overview of permission system

#### **7.2 Admin Routes:**
```php
// Admin permission management routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('subscription-tiers', SubscriptionTierController::class);
    Route::resource('permissions', PermissionController::class);
    Route::get('users/{user}/permissions', [UserController::class, 'permissions']);
    Route::post('users/{user}/permissions', [UserController::class, 'updatePermissions']);
});
```

---

## 🚀 **Implementation Order**

### **Week 1: Foundation**
1. **Day 1-2**: Phase 1 (Database Foundation)
2. **Day 3**: Phase 2 (Model Implementation)
3. **Day 4**: Phase 3 (Permission Definitions)
4. **Day 5**: Phase 4 (Policy Updates)

### **Week 2: Integration**
1. **Day 1-2**: Phase 5 (Frontend Integration)
2. **Day 3**: Phase 6 (Testing Implementation)
3. **Day 4**: Phase 7 (Admin Interface)
4. **Day 5**: Testing and bug fixes

---

## 📊 **Success Metrics**

### **Technical Metrics:**
- [ ] All existing functionality works with permission system
- [ ] Users can only access features they have permissions for
- [ ] Subscription tier changes immediately update user permissions
- [ ] Admin can manage permissions and subscription tiers
- [ ] Frontend gracefully handles permission restrictions

### **User Experience Metrics:**
- [ ] Clear upgrade prompts for missing features
- [ ] Intuitive permission-based navigation
- [ ] No broken links or 403 errors for users
- [ ] Smooth subscription tier upgrade flow

---

## 🔧 **Configuration Options**

### **Environment Variables:**
```env
# Permission system configuration
PERMISSION_CACHE_TTL=3600
DEFAULT_SUBSCRIPTION_TIER=free
ENABLE_PERMISSION_DEBUG=false
```

### **Config File:**
```php
// config/permissions.php
return [
    'cache_ttl' => env('PERMISSION_CACHE_TTL', 3600),
    'default_tier' => env('DEFAULT_SUBSCRIPTION_TIER', 'free'),
    'debug' => env('ENABLE_PERMISSION_DEBUG', false),
    'features' => [
        'albums' => ['albums.view', 'albums.create'],
        'mosaics' => ['mosaics.view', 'mosaics.create'],
        'portfolios' => ['portfolios.view', 'portfolios.create'],
    ],
];
```

---

## 🎯 **Migration Strategy**

### **Existing Users:**
1. All existing users get "Free" tier by default
2. Existing albums and mosaics remain accessible
3. Users can upgrade to higher tiers to unlock more features
4. No data loss during migration

### **Rollback Plan:**
1. Keep existing authorization logic as fallback
2. Add feature flags to enable/disable permission system
3. Database migrations are reversible
4. Frontend changes are backward compatible

---

## 📝 **Documentation Updates**

### **Developer Documentation:**
- Update API documentation with permission requirements
- Document new permission system architecture
- Create examples for adding new entities with permissions
- Update testing guidelines

### **User Documentation:**
- Create subscription tier comparison
- Document feature availability by tier
- Create upgrade guides for users
- Update admin documentation

---

## 🚨 **Risk Mitigation**

### **Technical Risks:**
- **Performance Impact**: Cache permissions to avoid N+1 queries
- **Complexity**: Keep permission logic simple and well-documented
- **Breaking Changes**: Implement feature flags for gradual rollout

### **User Experience Risks:**
- **Confusion**: Clear messaging about feature availability
- **Frustration**: Graceful degradation with upgrade prompts
- **Data Loss**: Ensure existing data remains accessible

---

## ✅ **Implementation Checklist**

### **Database:**
- [ ] Create permission tables
- [ ] Create subscription tier tables
- [ ] Add tier reference to users table
- [ ] Run migrations successfully

### **Models:**
- [ ] Create Permission model
- [ ] Create SubscriptionTier model
- [ ] Update User model with permission methods
- [ ] Test model relationships

### **Permissions:**
- [ ] Define all required permissions
- [ ] Create permission seeder
- [ ] Create subscription tier seeder
- [ ] Test permission assignment

### **Policies:**
- [ ] Update AlbumPolicy with permission checks
- [ ] Update MosaicPolicy with permission checks
- [ ] Create UserPolicy for admin functions
- [ ] Test all policy methods

### **Frontend:**
- [ ] Create permission composable
- [ ] Update navigation with permission checks
- [ ] Add upgrade prompts for missing features
- [ ] Test frontend permission logic

### **Testing:**
- [ ] Create comprehensive permission tests
- [ ] Test subscription tier functionality
- [ ] Test user permission assignment
- [ ] Test frontend permission checks

### **Admin:**
- [ ] Create admin permission management interface
- [ ] Create subscription tier management
- [ ] Test admin functionality
- [ ] Document admin procedures

This plan provides a comprehensive roadmap for implementing the permission system while maintaining system stability and user experience.



