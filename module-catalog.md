# Module catalog

Modules built for Motherboard, by the Motherboard project and by the community.

To install a module, copy its folder into `public_html/modules/<slug>/`, then enable it under **Module Manager**. See [documentation/module-development.md](documentation/module-development.md) to build your own.

## Add your module

Open a pull request that adds an entry under **Community Modules** below. Keep entries in alphabetical order by name and use this format:

```markdown
### Module Name

- **Author:** Your Name
- **Description:** One or two sentences on what the module does.
- **Repository:** https://github.com/you/your-module
```

The repository should contain the module folder (with its `index.php`) and say which Motherboard version it needs. Listing a module here is not an endorsement or a security review — review a module's code before you install it.

## Official Modules

These modules are made by the Motherboard project and ship with the core in `public_html/modules/`.

### Cloudflare Turnstile

- **Author:** Michael Staake
- **Description:** Protect login and password reset forms with Cloudflare Turnstile.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/cloudflare-turnstile

### Customer Email

- **Author:** Michael Staake
- **Description:** Email customers when their work order is created, first worked on, completed, and picked up.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/customer-email

### Email Two-Factor Authentication

- **Author:** Michael Staake
- **Description:** Require an emailed code on every login or when signing in from a new IP address.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/email-2fa

### Google reCAPTCHA

- **Author:** Michael Staake
- **Description:** Protect login and password reset forms with Google reCAPTCHA v2.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/google-recaptcha

### Inventory

- **Author:** Michael Staake
- **Description:** Track product categories, stock, pricing, and assign products to work orders.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/inventory

### S3 Compatible Storage

- **Author:** Michael Staake
- **Description:** Store work order attachments on Amazon S3, Backblaze B2, Wasabi, or other S3-compatible services.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/s3-compatible-storage

### Warranty

- **Author:** Michael Staake
- **Description:** Flag work orders as warranty repairs, reference the prior work order, and mark it on the printout.
- **Repository:** https://github.com/michaelstaake/Motherboard/tree/main/public_html/modules/warranty

## Community Modules

Modules made by other people.

### EDS Warranty Cards

- **Author:** EDS Design
- **Description:** Create, issue, print, and search warranty cards for work orders, with formatted numbering, immutable customer snapshots, and Bulgarian and English localization.
- **Repository:** https://github.com/eds-design/motherboard-eds-warranty-cards
