# Changelog

All notable changes to the Shipping Labels module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-12

### Added
- Initial release of the Shipping Labels module
- Centralized storage for shipping labels from all carrier modules
- `ShippingLabelRepository` for clean code architecture and business logic separation
- Download action for individual shipping labels
- Bulk print functionality to merge multiple labels into one PDF
- Secure file storage in `/var/shipping_labels/` (outside module directory)
- Path traversal protection on all file operations
- PDF file validation (MIME type, signature, size limit)
- Admin interface with PrestaShop Grid system
- Filters for label search (ID, Order ID, Tracking Number, Module)
- Display labels on order pages via `displayAdminOrderMainBottom` hook
- Repository methods for external modules:
  - `createLabel()` - Create a new label
  - `updateLabel()` - Update label information
  - `deleteLabel()` - Delete label and associated file
  - `getLabelsForOrder()` - Get all labels for an order
  - `getLabelById()` - Get specific label
  - `findByTrackingNumber()` - Search by tracking number
  - `findByModule()` - Get labels from specific module
- Extensibility hooks:
  - `actionShippingLabelsModuleInstalled` - Fired on module installation
  - `actionGetShippingLabelData` - Modify label data before display
  - `actionModifyShippingLabelsForOrder` - Modify labels list
  - `displayShippingLabelActions` - Add custom actions to labels
- Database table `ps_shipping_label` with optimized indexes
- Symfony routes for all CRUD operations
- AdminSecurity attributes on all controller actions
- Comprehensive documentation (README.md, INTEGRATION_EXAMPLE.md)
- Support for PHP 8.1+
- PrestaShop 9.0+ compatibility

### Security
- Path traversal protection using `basename()` and `realpath()` validation
- Secure filename validation with `isFileName` validator
- AdminSecurity attributes enforcing role-based access control
- PDF validation (MIME type, file signature, size limit 50MB)
- Files stored outside module directory to prevent unauthorized access
- Proper error handling without exposing sensitive paths

### Fixed
- config.xml now contains correct module information (was showing democontrollertabs)
- Consistent file paths (extrashippinglabels instead of shippinglabels)
- Correct route prefixes (ps_extrashippinglabels_* instead of ps_shippinglabels_*)
- SQL injection prevention in all database queries
- Proper validation in ShippingLabel ObjectModel

### Technical Details
- Repository Pattern for business logic
- Dependency Injection via services.yml
- Symfony Controllers with autowiring
- Doctrine DBAL for database operations
- TCPDF integration for PDF merging (optional)
- Grid Definition Factory for admin interface
- Query Builder for optimized database queries

## [Unreleased]

### Planned Features
- Label status management (printed, shipped, cancelled)
- Print history tracking
- Multi-parcel support (multiple labels per order)
- Automatic label cleanup via cron
- Bulk export to ZIP
- REST API for external integrations
- Webhooks for label events
- Custom label templates support
- Integration with tracking services
- Email notification system

---

For migration guide and integration examples, see INTEGRATION_EXAMPLE.md
