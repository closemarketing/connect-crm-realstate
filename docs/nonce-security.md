# Nonce Security Implementation

## Overview

Nonces (Number used ONCE) are WordPress security tokens used to protect against CSRF (Cross-Site Request Forgery) attacks. This document explains how nonces are implemented in this plugin.

## What are Nonces?

A nonce is a "number used once" to verify that the request came from a legitimate source and hasn't been tampered with. WordPress nonces:

- Are valid for 24 hours by default
- Are tied to the current user
- Are tied to a specific action
- Automatically expire after use (in some contexts)

## Implementation in This Plugin

### 1. Manual Import Nonce

**Location:** `includes/class-iip-import.php`

#### Generation (PHP)

```php
wp_localize_script(
    'connect-realstate-manual',
    'ajaxAction',
    array(
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'ccrmre_manual_import_nonce' ),
    )
);
```

#### Sending (JavaScript)

```javascript
fetch( ajaxAction.url, {
    method: 'POST',
    body: 'action=manual_import&nonce=' + ajaxAction.nonce + '&loop=' + loop
})
```

#### Verification (PHP)

```php
public function manual_import() {
    // Verify nonce manually for better error handling
    if ( ! isset( $_POST['nonce'] ) || 
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ccrmre_manual_import_nonce' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security verification failed. Please refresh the page and try again.', 'connect-crm-realstate' ),
            )
        );
        return;
    }
    
    // Continue with import...
}
```

**Note:** This uses `wp_verify_nonce()` for better error handling and to avoid unexpected `die()` calls during long-running import processes.

---

### 2. Auto-Map Fields Nonce

**Location:** `includes/class-iip-admin.php`

#### Generation (PHP)

```php
wp_localize_script(
    'ccrmre-merge-fields',
    'ccrmreMergeFields',
    array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'ccrmre_auto_map_nonce' ),
    )
);
```

#### Sending (JavaScript)

```javascript
$.ajax({
    url: ccrmreMergeFields.ajaxUrl,
    type: 'POST',
    data: {
        action: 'ccrmre_auto_map_fields',
        nonce: ccrmreMergeFields.nonce
    }
})
```

#### Verification (PHP)

```php
public function ajax_auto_map_fields() {
    // Verify nonce manually
    if ( ! isset( $_POST['nonce'] ) || 
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ccrmre_auto_map_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed', 'connect-crm-realstate' ) ) );
    }
    
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'connect-crm-realstate' ) ) );
    }
    
    // Continue with auto-mapping...
}
```

**Note:** This uses `wp_verify_nonce()` because we want custom error messages via `wp_send_json_error()`.

---

## Two Verification Methods

### Method 1: `check_ajax_referer()` (Recommended for AJAX)

**Use when:** You want WordPress to handle the error automatically.

```php
public function ajax_handler() {
    check_ajax_referer( 'my_action_nonce', 'nonce' );
    // Dies automatically with -1 if invalid
    // Continues here if valid
}
```

**Behavior:**
- ✅ Automatically sends `-1` response if invalid
- ✅ Automatically calls `die()`
- ✅ Less code needed
- ❌ Can't customize error message

**Parameters:**
1. `$action` - The nonce action name
2. `$query_arg` - The $_REQUEST key where nonce is stored (default: `'_ajax_nonce'`)
3. `$die` - Whether to die on failure (default: `true`)

---

### Method 2: `wp_verify_nonce()` (Manual Verification)

**Use when:** You want to handle the error manually or need custom messages.

```php
public function ajax_handler() {
    if ( ! isset( $_POST['nonce'] ) || 
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_action_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Custom error message' ) );
        return;
    }
    // Continue if valid
}
```

**Behavior:**
- ✅ Returns `false` if invalid
- ✅ Allows custom error handling
- ✅ More control over response
- ❌ More code needed
- ❌ Need to sanitize input manually

**Return Values:**
- `1` - Nonce is valid and not older than 12 hours
- `2` - Nonce is valid but older than 12 hours
- `false` - Nonce is invalid

---

## Common Mistakes

### ❌ Wrong: Checking return value of `check_ajax_referer()`

```php
// DON'T DO THIS
if ( ! check_ajax_referer( 'my_nonce', 'nonce' ) ) {
    wp_send_json_error();
    return;
}
```

**Problem:** `check_ajax_referer()` dies before returning false. The `if` statement never executes.

### ✅ Correct: Use it directly

```php
// DO THIS
check_ajax_referer( 'my_nonce', 'nonce' );
// Code here only runs if nonce is valid
```

---

### ❌ Wrong: Not sanitizing with `wp_verify_nonce()`

```php
// DON'T DO THIS
if ( ! wp_verify_nonce( $_POST['nonce'], 'my_nonce' ) ) {
    // ...
}
```

**Problem:** Direct access to `$_POST` without sanitization.

### ✅ Correct: Always sanitize

```php
// DO THIS
if ( ! isset( $_POST['nonce'] ) || 
     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_nonce' ) ) {
    // ...
}
```

---

## Best Practices

### 1. Always Verify Nonces

Every AJAX request should verify a nonce:

```php
add_action( 'wp_ajax_my_action', 'my_ajax_handler' );

function my_ajax_handler() {
    check_ajax_referer( 'my_action_nonce', 'nonce' );
    // Rest of code...
}
```

### 2. Use Descriptive Action Names

```php
// ✅ Good
wp_create_nonce( 'ccrmre_manual_import_nonce' )
wp_create_nonce( 'ccrmre_auto_map_nonce' )

// ❌ Bad
wp_create_nonce( 'import' )
wp_create_nonce( 'nonce1' )
```

### 3. Check Permissions Too

Nonces verify the request origin, but not user capabilities:

```php
check_ajax_referer( 'my_action_nonce', 'nonce' );

// Also check permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
}
```

### 4. Use `wp_localize_script()` to Pass Nonces

```php
wp_localize_script(
    'my-script',
    'myAjax',
    array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'my_action_nonce' ),
    )
);
```

Then in JavaScript:

```javascript
fetch( myAjax.ajaxUrl, {
    method: 'POST',
    body: 'action=my_action&nonce=' + myAjax.nonce
})
```

### 5. Always Sanitize in Manual Verification

```php
// Always use these three together
if ( ! isset( $_POST['nonce'] ) || 
     ! wp_verify_nonce( 
         sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 
         'my_action_nonce' 
     ) 
) {
    // Error handling
}
```

---

## Security Checklist

When implementing AJAX with nonces:

- [ ] Nonce is created: `wp_create_nonce()`
- [ ] Nonce is passed to JavaScript: `wp_localize_script()`
- [ ] Nonce is sent in AJAX request
- [ ] Nonce is verified in handler: `check_ajax_referer()` or `wp_verify_nonce()`
- [ ] User permissions are checked: `current_user_can()`
- [ ] Input is sanitized: `sanitize_text_field()`, etc.
- [ ] Output is escaped: `esc_html()`, `esc_attr()`, etc.

---

## Testing Nonces

### Test Invalid Nonce

```javascript
// Send request with invalid nonce
fetch( ajaxUrl, {
    method: 'POST',
    body: 'action=my_action&nonce=invalid_nonce_12345'
})
.then(resp => resp.json())
.then(result => {
    console.log(result); // Should be error response
})
```

**Expected:** Request should fail with error message

### Test Expired Nonce

Nonces expire after 24 hours. To test:

1. Generate a nonce
2. Wait 24+ hours (or modify WordPress time)
3. Try using the old nonce

**Expected:** Request should fail

### Test Missing Nonce

```javascript
// Send request without nonce
fetch( ajaxUrl, {
    method: 'POST',
    body: 'action=my_action'
})
```

**Expected:** Request should fail

---

## Troubleshooting

### "Invalid nonce" error

**Possible causes:**
1. User logged out between page load and AJAX request
2. Session expired (24+ hours)
3. Nonce action name mismatch
4. Nonce parameter name mismatch
5. Caching plugin serving old nonce

**Solutions:**
- Check action names match in `wp_create_nonce()` and verification
- Check parameter names match (`'nonce'` in example)
- Exclude AJAX endpoints from caching
- Implement nonce refresh mechanism

### Nonce verification fails after logout

**Expected behavior:** This is correct. Nonces are tied to user sessions.

**Solution:** Redirect user to login page if nonce fails

---

## References

- [WordPress Nonces Documentation](https://developer.wordpress.org/apis/security/nonces/)
- [check_ajax_referer() Reference](https://developer.wordpress.org/reference/functions/check_ajax_referer/)
- [wp_verify_nonce() Reference](https://developer.wordpress.org/reference/functions/wp_verify_nonce/)
- [wp_create_nonce() Reference](https://developer.wordpress.org/reference/functions/wp_create_nonce/)

