# Frequently Asked Questions (FAQ)

## General Questions

### What is the Shipping Labels module?

It's a **foundation module** for PrestaShop 9 that provides standardized storage, management, and display of shipping labels from any carrier. Think of it as a common infrastructure that carrier modules can plug into instead of building their own label management system.

### Who is this module for?

**Three main audiences:**
1. **Module Developers** - Save time by using a ready-made label management system
2. **Merchants** - Get a unified interface for all carriers' labels
3. **Agencies** - Reduce development and maintenance costs on client projects

### Is this module free?

Yes! It's completely free and open-source under the Academic Free License 3.0 (AFL-3.0), the same license used by PrestaShop core.

### Does this module generate shipping labels?

**No.** This module provides the infrastructure to *store and manage* labels. The actual label generation is done by carrier-specific modules (Colissimo, UPS, DHL, etc.).

Think of it like this:
- **This module** = The filing cabinet for labels
- **Carrier modules** = The printers that create labels

---

## Technical Questions

### What PrestaShop versions are supported?

- **Required:** PrestaShop 9.0.0 or higher
- **Tested with:** 9.0.x and 9.1.x
- **PHP:** 8.1+ required

PrestaShop 8.x is **not** supported (different architecture).

### Does it work with my existing carrier module?

**Not automatically.** Existing carrier modules need to be updated to integrate with the Shipping Labels module. However, the integration is straightforward (typically 2-3 hours of work).

See [INTEGRATION_EXAMPLE.md](INTEGRATION_EXAMPLE.md) for a complete integration guide.

### What if I don't update my carrier module?

Your carrier module will continue to work exactly as before. The Shipping Labels module is **optional** and doesn't break existing functionality.

### How do I integrate my carrier module?

Three simple steps:

```php
// 1. Get the repository
$repository = $this->get('prestashop.module.extrashippinglabels.repository');

// 2. Save your PDF
$filename = 'label_' . $orderId . '.pdf';
file_put_contents($repository->getLabelsDirectory() . $filename, $pdfContent);

// 3. Register in database
$labelId = $repository->createLabel(
    orderId: $orderId,
    moduleName: $this->name,
    trackingNumber: $trackingNumber,
    labelFilepath: $filename
);
```

Full documentation: [INTEGRATION_EXAMPLE.md](INTEGRATION_EXAMPLE.md)

### Can I use this module without any carrier modules?

The module will install and work, but you won't see any labels until carrier modules start using it. It's designed to be infrastructure, not a standalone tool.

### Does this replace my carrier module?

**No!** Your carrier module still handles:
- API calls to the carrier
- Label generation
- Shipping rates
- Tracking
- Carrier-specific features

The Shipping Labels module only handles:
- Storing the PDF
- Displaying it in admin
- Download/print features
- Unified management interface

---

## Security Questions

### Is my data secure?

Yes. The module includes multiple security layers:

1. **Path Traversal Protection** - File paths are sanitized with `basename()` and validated
2. **PDF Validation** - Files are checked for MIME type, signature, and size limits
3. **Secure Storage** - Labels stored in `/var/shipping_labels/` outside the web-accessible modules directory
4. **Access Control** - All admin actions require proper permissions via `AdminSecurity` attributes
5. **SQL Injection Prevention** - All queries use parameterized statements via Doctrine DBAL

### Where are the PDF files stored?

In `/var/shipping_labels/` at the root of your PrestaShop installation.

**Why `/var/`?**
- Not directly web-accessible (better security)
- Persists through module updates/reinstalls
- Standard location for application data
- Easily excluded from backups if desired

### Can customers access the label PDFs?

No. The PDFs are not accessible via direct URLs. They can only be downloaded through admin controllers that check user permissions.

### What happens to labels when I uninstall the module?

The database table is dropped, but the PDF files in `/var/shipping_labels/` are **not** automatically deleted. You need to manually remove that directory if desired.

**Why?** To prevent accidental data loss. You might want to keep old labels for record-keeping.

---

## Usage Questions

### How do I download a label?

**Single label:**
1. Go to "Shipping → Shipping Labels" in admin
2. Click the download icon next to the label

**Multiple labels:**
1. Select checkboxes for desired labels
2. Choose "Print selected" from bulk actions
3. All PDFs are merged into one file

### Can I reprint a label?

Yes! Labels are stored permanently until you delete them. You can download/print them as many times as needed.

### How do I search for a specific label?

The admin interface has filters for:
- Label ID
- Order ID
- Tracking number
- Module name (carrier)

You can also search by tracking number using the repository:
```php
$labels = $repository->findByTrackingNumber('TRACK123');
```

### Can I have multiple labels for one order?

Yes! This is common for multi-parcel shipments. Each label is stored separately with the same `id_order`.

### How do I delete a label?

**From admin:**
1. Go to "Shipping → Shipping Labels"
2. Click delete icon
3. Confirm

**Programmatically:**
```php
$repository->deleteLabel($labelId); // Also deletes the PDF file
```

---

## Performance Questions

### Does this module slow down my store?

No. The module:
- Only loads in admin (zero front-office impact)
- Uses indexed database queries
- Lazy-loads labels (only when needed)
- Doesn't process during page loads

### How many labels can it handle?

There's no hard limit. Tested with:
- 100,000+ labels in database
- Queries remain fast thanks to indexes
- Bulk print limited by PHP memory (configurable)

### What about disk space?

A typical shipping label PDF is 20-100 KB. So:
- 1,000 labels = ~50 MB
- 10,000 labels = ~500 MB
- 100,000 labels = ~5 GB

Consider periodic cleanup of old labels (>1 year) if disk space is limited.

### Can I auto-delete old labels?

Not currently, but you can create a cron job:

```php
// Delete labels older than 1 year
$cutoffDate = date('Y-m-d', strtotime('-1 year'));
Db::getInstance()->execute(
    'DELETE FROM ' . _DB_PREFIX_ . 'shipping_label WHERE date_add < "' . pSQL($cutoffDate) . '"'
);
```

---

## Compatibility Questions

### Does it work with multistore?

**Partially.** The module works in multistore environments, but labels are shared across stores (same database table). This is usually fine since orders are already store-specific.

Future version may add per-store filtering.

### Does it work with multi-language stores?

Yes. The admin interface is translatable, and label filenames are language-agnostic.

### What about GDPR?

The module stores:
- Order IDs (already in PrestaShop)
- Tracking numbers
- Module names
- PDF filenames

**No personal data** is stored beyond what's in the PDF itself (which the carrier module controls).

For GDPR compliance, consider:
- Delete labels when orders are deleted
- Include labels in data export requests
- Auto-delete old labels (>1 year)

### Does it work with REST API?

Not currently. The module provides:
- PHP repository API
- Admin controllers

A REST API could be added in a future version if there's demand.

---

## Troubleshooting

### "Cannot find repository service"

**Cause:** The module isn't properly installed or services aren't registered.

**Solution:**
1. Reinstall the module
2. Clear Symfony cache: `php bin/console cache:clear`
3. Check that `config/services.yml` is loaded

### "Label file not found"

**Cause:** The PDF was deleted manually or the path is incorrect.

**Solution:**
1. Check if `/var/shipping_labels/` exists and is writable
2. Verify the filename in database matches the actual file
3. Regenerate the label if possible

### "Could not merge PDFs"

**Cause:** TCPDF error or memory limit reached.

**Solution:**
1. Increase PHP memory limit: `memory_limit = 512M`
2. Try printing fewer labels at once
3. Download individually if merge continues to fail

### "Permission denied on /var/shipping_labels/"

**Cause:** Web server doesn't have write permissions.

**Solution:**
```bash
sudo chown -R www-data:www-data /var/shipping_labels/
sudo chmod -R 755 /var/shipping_labels/
```

### Labels not showing on order page

**Cause:** Hook not registered or carrier module not integrated.

**Solution:**
1. Check module is installed: "Modules → Module Manager"
2. Verify hook is registered: "Advanced Parameters → Performance → Clear cache"
3. Ensure carrier module is calling `createLabel()`

---

## Development Questions

### Can I extend the database schema?

**Not recommended.** The schema is intentionally minimal to keep it generic.

Instead, use the hooks to add custom data:

```php
public function hookActionGetShippingLabelData($params)
{
    $label = &$params['label'];
    $label['my_custom_field'] = 'value';
}
```

### Can I add custom columns to the grid?

Not easily. The Grid Definition is in the module's code.

**Better approach:** Use hooks to display custom data below the grid or add custom actions.

### Can I change where PDFs are stored?

Yes, but you'll need to fork the module and modify `ShippingLabelRepository::__construct()`.

**Why?** The storage path is designed to be consistent across all installations for easier support.

### How do I contribute?

1. Fork the repository on GitHub
2. Create a feature branch
3. Write tests for your changes
4. Submit a pull request

See CONTRIBUTING.md for detailed guidelines.

### Is there a roadmap?

Yes! See [CHANGELOG.md](CHANGELOG.md) under "Unreleased" section.

Planned features:
- Label status management
- Print history
- Multi-parcel support
- REST API
- Auto-cleanup cron

---

## Business Questions

### Can I use this in commercial projects?

Yes! The AFL 3.0 license allows commercial use.

### Do I need to credit the module?

Attribution is appreciated but not required by the license.

### Can I fork and modify it?

Yes, under the terms of AFL 3.0. If you make improvements, please consider contributing back!

### Is there paid support?

Currently no official paid support. For commercial support inquiries, contact [your email/company].

### Can agencies white-label this?

Yes, AFL 3.0 allows this. You can rebrand and resell integration services.

---

## Migration Questions

### I have a custom label system. Can I migrate?

Yes! Create a migration script:

```php
// Pseudo-code
foreach ($oldLabels as $oldLabel) {
    // Copy PDF to new location
    $newFilename = 'migrated_' . $oldLabel['id'] . '.pdf';
    copy($oldPath, $repository->getLabelsDirectory() . $newFilename);

    // Create in new system
    $repository->createLabel(
        orderId: $oldLabel['order_id'],
        moduleName: 'your_module',
        trackingNumber: $oldLabel['tracking'],
        labelFilepath: $newFilename
    );
}
```

### Will this break my existing setup?

No. The module can coexist with existing label systems. Migrate at your own pace.

### How do I test before going live?

1. Install on a staging environment
2. Migrate a small batch of labels
3. Test all workflows (download, print, search)
4. Deploy to production when confident

---

## Community Questions

### Where can I get help?

1. **Documentation:** [README.md](README.md), [INTEGRATION_EXAMPLE.md](INTEGRATION_EXAMPLE.md)
2. **Issues:** GitHub Issues for bugs
3. **Discussions:** GitHub Discussions for questions
4. **Forum:** PrestaShop Forums → Module Development

### How can I report a bug?

1. Check if it's already reported on GitHub Issues
2. If not, create a new issue with:
   - PrestaShop version
   - PHP version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error logs (if any)

### Can I request features?

Yes! Open a GitHub Discussion or Issue with:
- Use case description
- Why it's needed
- Proposed solution (if you have one)

### When is the next release?

See the roadmap in [CHANGELOG.md](CHANGELOG.md). Releases follow semantic versioning:
- Major (2.0): Breaking changes
- Minor (1.1): New features
- Patch (1.0.1): Bug fixes

---

## Still have questions?

- **Technical:** Open a GitHub Issue
- **Integration Help:** See [INTEGRATION_EXAMPLE.md](INTEGRATION_EXAMPLE.md)
- **Business Inquiries:** Contact [your email]

---

*Last Updated: 2026-01-12*
*Module Version: 1.0.0*
