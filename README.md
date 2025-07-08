# ACF JSON Import/Export Tool

A simple and reliable WordPress plugin for exporting and importing Advanced Custom Fields (ACF) data using JSON format.

## 🔧 Features

- **Universal Post Type Support**: Works with pages, posts, and all custom post types
- **Flexible Import Options**: Import via file upload or paste JSON directly
- **Export Any Content**: Export ACF fields from any post/page with a single click
- **Security Built-in**: WordPress nonces and permission checks
- **Smart Filtering**: Dynamic post type selection with instant updates
- **Clear Feedback**: Success messages confirm successful imports
- **Simple & Reliable**: Straightforward code that just works

## 📦 Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.0 or higher
- Advanced Custom Fields (ACF) plugin

### Installation Steps
1. Download the plugin files
2. Upload to `/wp-content/plugins/acf-json-import-export/`
3. Activate the plugin through the 'Plugins' menu in WordPress

## 📖 Usage

### Exporting ACF Fields
1. Go to **Tools > Export ACF Fields**
2. Select the post type (Pages, Posts, or Custom Post Types)
3. Choose the specific item you want to export
4. Click **Export ACF JSON**
5. Save the downloaded JSON file

### Importing ACF Fields
1. Go to **Tools > Import ACF JSON**
2. Select the target post type
3. Choose the item to import into
4. Either:
   - **Paste JSON**: Copy and paste your JSON data directly into the textarea
   - **Upload File**: Select a JSON file from your computer
5. Click **Import**
6. See the success message confirming the import

## 🛡️ Security

- All forms use WordPress nonces for security
- Requires `manage_options` capability
- Input validation and sanitization
- File type and size checks

## 🔄 Version History

### Version 1.5 (Current)
- Simplified codebase back to functional basics
- Removed OOP complexity for better compatibility
- Maintained all core features
- Fixed admin page loading issues

### Version 1.4
- Added advanced preview and backup features (removed in 1.5)
- OOP refactoring (reverted in 1.5)

### Version 1.0-1.3
- Initial development
- Added security features
- Extended post type support

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

**Author:** Breon Williams  
**Website:** [https://breonwilliams.com](https://breonwilliams.com)