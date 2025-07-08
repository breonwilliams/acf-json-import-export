# ACF JSON Import/Export Tool

A powerful, secure, and user-friendly WordPress plugin for exporting and importing Advanced Custom Fields (ACF) data across any post type using JSON format.

## 🚀 Key Features

### Core Functionality
- **Universal Post Type Support**: Works with pages, posts, and all custom post types
- **Flexible Import Options**: Import via file upload or paste JSON directly
- **Export Any Content**: Export ACF fields from any post/page with a single click

### Advanced Features
- **Import Preview**: See exactly what will change before committing
- **Automatic Backup**: Optional backup creation before importing new data
- **Visual Diff**: Color-coded preview showing new, changed, and removed fields
- **AJAX-Powered**: Preview imports without page reload
- **Batch Processing**: Handles complex field structures and nested data

### Security & Reliability
- **Enhanced Security**: WordPress nonces, file validation, and JSON structure checking
- **Safe Operations**: Permission checks and data sanitization
- **Error Recovery**: Comprehensive error handling with clear user messages
- **File Size Limits**: 2MB maximum to prevent server issues

### User Experience
- **Intuitive Interface**: Clean, WordPress-native admin interface
- **Smart Filtering**: Dynamic post type selection with instant updates
- **Progress Feedback**: Clear success/error messages for all operations
- **Internationalization**: Fully translatable with proper text domains

## 📦 Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.0 or higher
- Advanced Custom Fields (ACF) plugin (free or Pro)

### Method 1: Upload via WordPress Admin
1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Select the ZIP file and click **Install Now**
4. Activate the plugin

### Method 2: Manual Installation
1. Unzip the plugin file
2. Upload the `acf-json-import-export` folder to `/wp-content/plugins/`
3. Activate through the **Plugins** menu in WordPress

## 📖 Usage Guide

### Exporting ACF Fields

1. Navigate to **Tools > Export ACF Fields**
2. Select the post type from the dropdown
3. Choose the specific item to export
4. Click **Export ACF JSON**
5. Save the downloaded JSON file

### Importing ACF Fields

1. Navigate to **Tools > Import ACF JSON**
2. Select the target post type
3. Choose the item to import into
4. Add your JSON data:
   - **Option A**: Paste JSON directly into the textarea
   - **Option B**: Upload a JSON file
5. (Optional) Check **"Create backup before import"**
6. Click **Preview Import** to review changes
7. Click **Import** to apply changes

### Understanding the Preview

The import preview uses color coding:
- 🟡 **Yellow**: Fields that will be modified
- 🔵 **Blue**: New fields that will be added
- 🔴 **Red**: Fields that will be removed
- ⚪ **Default**: Fields that remain unchanged

## 🛡️ Security Features

- **Nonce Verification**: All forms use WordPress nonces
- **Capability Checks**: Requires `manage_options` permission
- **File Type Validation**: Only accepts valid JSON files
- **Size Restrictions**: 2MB file upload limit
- **Data Sanitization**: All inputs are properly sanitized
- **Direct Access Prevention**: Files protected from direct access

## 💾 Backup System

When enabled, backups are stored in:
```
/wp-content/uploads/acf-json-backups/
```

Backup files are named with timestamps:
```
backup-{post-name}-{post-id}-{timestamp}.json
```

Each backup contains:
- Post metadata (ID, title, type)
- Backup timestamp
- Complete ACF field data

## 🔧 Technical Details

### Architecture
- **Object-Oriented**: Clean OOP structure with namespace
- **Singleton Pattern**: Ensures single instance
- **WordPress Standards**: Follows coding best practices
- **Hooks & Filters**: Properly integrated with WordPress

### File Structure
```
acf-json-import-export/
├── acf-json-import-export.php    # Main plugin file
├── assets/
│   ├── js/
│   │   └── admin.js             # Admin JavaScript
│   └── css/
│       └── admin.css            # Admin styles
└── README.md                    # Documentation
```

### Performance
- **Efficient Queries**: Optimized database operations
- **Memory Safe**: Handles large datasets gracefully
- **AJAX Operations**: Non-blocking preview functionality

## 🔄 Version History

### Version 1.4 (Current)
- Added import preview with visual diff
- Implemented automatic backup system
- AJAX-powered preview functionality
- Enhanced UI with custom CSS
- Improved user experience

### Version 1.3
- Complete OOP refactoring
- Added namespace for conflict prevention
- Improved code maintainability
- Singleton pattern implementation

### Version 1.2
- Added all post type support
- Paste JSON functionality
- Moved to Tools menu
- Better error handling

### Version 1.1
- Security enhancements
- ACF dependency checking
- Internationalization support

### Version 1.0
- Initial release
- Basic import/export for pages

## 🤝 Contributing

Contributions are welcome! Please feel free to submit issues or pull requests on the [GitHub repository](https://github.com/breonwilliams/acf-json-import-export).

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 👨‍💻 Credits

**Author:** Breon Williams  
**Website:** [https://breonwilliams.com](https://breonwilliams.com)  
**Version:** 1.4

## 🆘 Support

For support, please:
1. Check the [FAQ section](#-frequently-asked-questions)
2. Search existing issues
3. Create a new issue with detailed information

## ❓ Frequently Asked Questions

**Q: What happens if I import without a backup?**  
A: The current ACF data will be overwritten. Always use the preview feature first.

**Q: Can I import into multiple posts at once?**  
A: Currently, the plugin supports single-post imports. Bulk operations are planned for a future release.

**Q: Are custom field types supported?**  
A: Yes, all ACF field types are supported, including complex fields like repeaters and flexible content.

**Q: Where are backups stored?**  
A: Backups are stored in `/wp-content/uploads/acf-json-backups/` with timestamped filenames.

**Q: Can I restore from a backup?**  
A: Yes, simply import the backup JSON file like any other import.

---

Made by [Breon Williams](https://breonwilliams.com)