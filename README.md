# PHP Logo Design Discovery Form with reCAPTCHA v3

A lightweight HTML form with a PHP email handler and Google reCAPTCHA v3. The current form collects a logo design brief, including business details, brand values, audience, style preferences, intended uses, deadline and budget.

Repository: [Tanzi-Bee/php-fast-contact-form](https://github.com/Tanzi-Bee/php-fast-contact-form)

This README describes the repository files reviewed on 5 October 2026. The secret-handling update on 5 October 2026 removes the hard-coded secret, loads it from `RECAPTCHA_SECRET_KEY`, and sends verification using POST. Other recommended changes below remain separate.

## Important security notice

**An earlier version of `send_email.php` exposed a hard-coded reCAPTCHA secret. It has been removed from the current file, but remains exposed in repository history. Treat it as compromised and rotate it before using the form.** Removing it from the latest file does not revoke it or remove it from Git history, forks or downloaded copies. Revoke or replace the exposed credential through Google's administration tools and update every deployment that uses it. If replacement requires a new key pair, update both site-key references as well.

The existing site key is public by design. The secret is private and must never appear in HTML, JavaScript, README examples, screenshots, support tickets or future commits. This README deliberately does not reproduce either existing key.

The handler also contains a fixed recipient and sender addresses. Review these before deployment so enquiries go to your own inbox. reCAPTCHA reduces spam risk; it does not make this implementation fully secure or production-ready.

## Files

| File | Purpose |
| --- | --- |
| `index.html` | Logo discovery form, inline CSS and reCAPTCHA v3 JavaScript. Posts to `send_email.php`. |
| `send_email.php` | Verifies reCAPTCHA, builds a plain-text email and calls PHP `mail()`. |
| `thank-you.html` | Confirmation page reached through JavaScript after `mail()` returns true. |
| `README.md` | Setup, limitations and maintenance guidance. |
| `LICENSE` | MIT licence terms. |

The form entry point is **`index.html`**, not `index.php`. Keep the three runtime files in the same directory so the relative form action and confirmation link work.

There is no database, SMTP library, attachment upload or visitor email field. The inspiration field accepts text and links despite its current label mentioning uploads. Font declarations name Karla and Jost, but these files do not load those fonts, so browsers may use the sans-serif fallback.

## Requirements

- Web hosting with a supported PHP version. Static hosting, including GitHub Pages, cannot run `send_email.php`.
- HTTPS and a domain you control.
- A Google reCAPTCHA v3 key pair registered for the deployment domain.
- JavaScript and access to Google's reCAPTCHA scripts in the visitor's browser.
- Server access to Google's HTTPS verification endpoint. The current PHP handler uses `file_get_contents()` on a URL, which requires URL fopen wrappers, `allow_url_fopen` enabled and working TLS certificates.
- A host-configured mail service that permits PHP `mail()`, with appropriate sending limits and domain authentication.

No Composer or npm installation is required by the current files. The repository does not establish a tested PHP version range; test on your chosen host rather than assuming compatibility.

## Download and configuration

Download the ZIP using GitHub's **Code > Download ZIP** menu and extract it, or clone the correct repository:

```sh
git clone https://github.com/Tanzi-Bee/php-fast-contact-form.git
cd php-fast-contact-form
```

Make deployment configuration changes in a private working copy. Do not push credentials back to GitHub. The steps below describe deployment configuration. Never store a replacement secret in a committed file.

### 1. Configure reCAPTCHA v3

1. Open the [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin) and register the deployment domain for reCAPTCHA v3. Use credentials compatible with the current `siteverify` integration. A reCAPTCHA Enterprise assessment integration is not a drop-in replacement.
2. Add the production and any staging hostnames according to Google's domain settings. Enter hostnames, not full page URLs or folder paths. Keep domain verification enabled.
3. In `index.html`, replace the existing site key in **both** places: the script URL's `render=` parameter and the first argument to `grecaptcha.execute()`.
4. Configure a private server environment variable named `RECAPTCHA_SECRET_KEY` with the new, corresponding secret. Do not reuse the exposed secret or paste the replacement into PHP. Ask your hosting provider how to make this variable available to the domain's PHP process; cPanel's available controls vary by host. A shell-only environment variable may not reach web requests. Without this setting, the handler returns HTTP 503 and does not send email.
5. Preserve the hidden field's name `g-recaptcha-response`, its ID `recaptchaResponse` and the current action name `submit` unless you deliberately update the integration together.

The current handler requires `success` and a score of at least `0.5`. It also requires the returned action to be `submit`. It does **not** check the returned hostname; that remains a recommended code change.

Google's tokens expire after two minutes and can be verified only once. The current page generates a token on page load, which is unsuitable for a lengthy discovery form. A quick submission may work while a genuine visitor who takes longer is rejected. Refreshing and submitting promptly is a diagnostic workaround, not a production fix. Generate a fresh token at submission time as recommended below.

### 2. Configure email

In the private deployment copy of `send_email.php`, review:

| Setting | What to configure |
| --- | --- |
| `$to` | Your monitored recipient inbox. |
| `$subject` | Currently `New Logo Design Inquiry`; customise if needed. |
| `From` header | A sender address on a domain your host authorises you to send from. |
| `Reply-To` header | A monitored address appropriate for replies. |

The current `From` and `Reply-To` headers both use the repository owner's domain. Replace them for your deployment. The form does not collect a visitor's name, email address or telephone number, so you cannot reliably reply to the person submitting it unless they include contact details in another field.

Changing `$to` does not configure a mail server. Ask your host whether PHP `mail()` is supported, whether a particular sender or envelope sender is required, and what sending limits apply. Configure SPF, DKIM and DMARC for the actual sending service using your host's guidance. These records improve authentication but do not guarantee delivery.

### 3. Review content

Check the form wording, email subject, confirmation message and styling for your business. The link in `thank-you.html` points to `/`, meaning the domain homepage rather than the form's subdirectory. Do not rename form fields without updating the corresponding PHP handling.

## Upload through cPanel

1. Back up the existing website before uploading. Sign in to cPanel and open **File Manager**.
2. Locate the target domain's document root. It is often `public_html` for the main domain; addon domains and subdomains may use different folders. Check the domain's configured document root.
3. Create a dedicated folder, such as `logo-discovery`, to avoid overwriting an existing homepage or conflicting with WordPress's `index.php`.
4. Upload `index.html`, `send_email.php` and `thank-you.html` into that same folder. If uploading a ZIP, extract it and move the runtime files out of any extra repository folder. Remove the uploaded archive afterwards.
5. Do not upload `.git`, private configuration backups or unrelated development files. Keep any future secret configuration outside the public document root.
6. Use permissions required by your host, commonly `644` for files and `755` for directories. Do not use `777`.
7. Confirm PHP is enabled for this domain using your host's PHP controls, often **MultiPHP Manager** or **Select PHP Version**. Enable HTTPS through your host's certificate tools.
8. Open the explicit URL, for example `https://example.com/logo-discovery/index.html`. A folder-only URL depends on the server's directory-index settings.

If the server displays or downloads PHP source rather than executing it, remove public access to the handler immediately and ask the host to fix PHP configuration. Source exposure can disclose credentials.

## How submissions work

1. The browser loads reCAPTCHA and stores a token in the hidden field.
2. The form sends a POST request to `send_email.php`.
3. PHP calls Google's verification endpoint and checks success and score.
4. If accepted, PHP constructs a plain-text message and calls `mail()`.
5. The handler outputs a JavaScript alert and navigates to `thank-you.html` if `mail()` returns true. On failure, it uses an alert and browser history navigation.

**A successful `mail()` result means the message was accepted for delivery, not that it reached the recipient.** The thank-you page is not proof of inbox delivery. Mail can later bounce, be filtered or be rejected. This project has no delivery tracking, retry queue or saved submission record.

## Testing before launch

Use an HTTPS staging deployment with your own recipient inbox. Do not send test enquiries to the repository's configured recipient.

- Submit a complete brief promptly after loading the page. Check the alert, confirmation navigation and actual received email, including spam and junk folders.
- Check every field in the email. Test multiple checkbox selections, no checkbox selections, optional fields left blank, accented characters, ampersands and multiline text.
- Test on mobile and desktop. Check keyboard navigation and labels; the current markup needs accessibility improvements.
- Wait more than two minutes before submitting. Record the expected token-expiry problem so it is fixed before launch.
- Test with reCAPTCHA blocked or JavaScript disabled. Confirm the failure experience is acceptable; the existing alerts and redirect depend on JavaScript.
- On staging, test a missing or invalid token and direct access to the PHP handler. A request without valid verification should not send mail; a non-POST request produces an invalid-request script.
- Review PHP error logs and mail delivery logs. Confirm secrets, tokens and full enquiry contents are not exposed in public errors or shared logs.
- Test delivery to more than one mailbox provider. An inbox success with one provider does not establish reliable delivery elsewhere.

Opening `index.html` from your computer can preview the layout but cannot test the PHP handler. A PHP development server also does not automatically provide a working mail service.

## Troubleshooting

| Problem | Checks and next steps |
| --- | --- |
| reCAPTCHA verification fails | Check both site-key references, the matching rotated secret, registered domain, script loading and token age. The score must be at least `0.5`. Do not disable verification to force a pass. |
| `grecaptcha` is undefined or the token stays empty | Check browser console and network errors, content blockers, script restrictions and access to Google. Check that the hidden input exists when the callback runs. |
| Google verification cannot be reached | Ask the host to check outbound HTTPS, DNS, TLS certificates and `allow_url_fopen`. Do not disable TLS verification. |
| Form temporarily unavailable (HTTP 503) | Ask the host to make the new `RECAPTCHA_SECRET_KEY` environment variable available to the web PHP process. Never expose it through a public diagnostic page. |
| PHP warning, blank response or HTTP 500 | Consult private server logs for missing POST fields, unexpected field types, failed network requests or invalid JSON. Avoid enabling public error display. |
| “Issue submitting” message | Check whether `mail()` is enabled and properly configured, sender requirements and host sending limits. |
| Thank-you page appears but no email arrives | Check recipient spelling, spam folders, quarantine, mail logs, bounces and SPF/DKIM/DMARC. Ask the host to trace the message. |
| Form or confirmation returns 404 | Check exact filenames, letter case, directory placement and relative paths. Keep the three runtime files together. |
| Folder URL shows another website | Use the explicit `index.html` URL or a separate folder; another index file or rewrite rule may take precedence. |
| Email shows HTML entities | The handler applies `htmlspecialchars()` to many fields but sends plain text. Review context-appropriate handling in a separate code change. |
| Cannot reply to the visitor | There is no visitor email field and `Reply-To` is fixed. Add validated contact fields separately. |

## Security and privacy limitations

Do not rely on the browser's `required` attribute for security. The handler does not comprehensively validate required fields, lengths, data types or permitted checkbox values. `htmlspecialchars()` is output escaping for HTML, not complete input validation, and checkbox arrays are currently joined without equivalent checks.

The handler now sends the secret and token in an HTTPS POST body with a ten-second timeout. Missing configuration, missing tokens, failed network requests and invalid verification responses stop submission. Keep request bodies out of diagnostic logs. The handler still lacks application-level rate limiting and hostname verification. reCAPTCHA alone does not prevent all abuse.

Use HTTPS, supported server software and private error logs. Restrict access to the hosting account and backups. Do not request passwords, payment details or other sensitive information through this form. Email is not a secure storage system for confidential briefs.

Review your privacy notice and Google's reCAPTCHA disclosure requirements before launch. Explain how form data and third-party services are used. Do not hide the reCAPTCHA badge without meeting Google's applicable requirements. Avoid presenting this form as audited or guaranteed secure.

## Recommended code changes: handle separately

**The changes below are still required or recommended separately. Secret removal, environment-variable loading, POST verification, timeout handling and the action check are implemented; credential revocation and hosting configuration still require administrator action.**

1. **Rotate the exposed secret urgently.** Set the replacement in the private server environment variable `RECAPTCHA_SECRET_KEY`, which the handler now reads. Add appropriate Git exclusions and secret scanning. History cleanup may reduce exposure, but cannot replace revocation.
2. **Generate reCAPTCHA tokens on submission.** Wait for a fresh token before posting, prevent duplicate submissions and handle script errors without losing the user's brief.
3. **Strengthen server verification.** Add allowed-hostname validation. The handler now uses HTTPS POST, a timeout, response checks, success and score checks, and the expected `submit` action.
4. **Validate all submitted fields.** Require the business name on the server, enforce sensible length limits, reject incorrect scalar/array types, and allow only recognised checkbox and select values. Use output handling appropriate to plain-text email.
5. **Add contact details.** Collect and validate a visitor name and email address. Keep a fixed authorised `From` address and use only a validated visitor address for `Reply-To`, preventing header injection.
6. **Improve email reliability.** Replace `mail()` with a maintained SMTP library such as PHPMailer and authenticated SMTP or an email service. Protect its credentials and handle errors, bounces and delivery status appropriately.
7. **Improve response handling.** Replace JavaScript-only alerts and navigation with accessible responses and a server-side redirect after success. Preserve entered data on recoverable errors and use suitable HTTP status codes.
8. **Add abuse controls.** Implement rate limits and request-size limits; consider a honeypot and assess CSRF protection for the deployment. Monitor abuse without logging secrets or unnecessary personal data.
9. **Improve accessibility and content accuracy.** Associate labels with controls, group checkboxes with fieldsets and legends, provide accessible error messages, fix checkbox layout and remove the unsupported upload claim or implement safe uploads separately.
10. **Add meaningful tests and operational checks.** Cover token expiry, verification outages, malformed input, mail failure and successful submission. Define privacy-conscious logging and retention. Test on the supported PHP versions you intend to advertise.

## Useful links

- [Current repository](https://github.com/Tanzi-Bee/php-fast-contact-form)
- [Report an issue](https://github.com/Tanzi-Bee/php-fast-contact-form/issues)
- [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
- [Google reCAPTCHA v3 documentation](https://developers.google.com/recaptcha/docs/v3)
- [Server-side token verification](https://developers.google.com/recaptcha/docs/verify)
- [reCAPTCHA FAQ and badge guidance](https://developers.google.com/recaptcha/docs/faq)
- [PHP `mail()` documentation](https://www.php.net/manual/en/function.mail.php)
- [cPanel File Manager documentation](https://docs.cpanel.net/cpanel/files/file-manager/)
- [cPanel Email Deliverability documentation](https://docs.cpanel.net/cpanel/email/email-deliverability/)
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [GitHub guidance on removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
- [Web Honey Digital](https://webhoney.digital)
- [Tanzi Bee on DEV Community](https://dev.to/tanzi_bee/)

## Licence and contributions

Released under the [MIT licence](LICENSE). Retain the required copyright and licence notices when redistributing the software.

Issues and pull requests are welcome. Never include live secrets, private customer briefs or SMTP credentials in examples, patches or bug reports.
