# Security Policy

## Supported Versions

We take security seriously and actively maintain the following versions:

| Version | Supported          | TYPO3 Version | PHP Version |
| ------- | ------------------ | ------------- | ----------- |
| 3.x     | :white_check_mark: | 13.x          | 8.3+        |
| 2.x     | :x:                | 13.x          | 8.2+        |
| 1.x     | :x:                | 12.x          | 8.1+        |

**Note:** Only the latest major version (3.x) receives security updates. We strongly recommend upgrading to the latest version.

## Reporting a Vulnerability

We appreciate responsible disclosure of security vulnerabilities.

### How to Report

**DO NOT** open a public GitHub issue for security vulnerabilities.

Instead, please report security issues privately via:

1. **GitHub Security Advisories** (preferred):
   - Navigate to [Security Advisories](https://github.com/ndrstmr/dp_t3_toc/security/advisories)
   - Click "Report a vulnerability"
   - Fill out the form with details

2. **Email** (alternative):
   - Contact: **ndrstmr** via GitHub profile
   - Subject: `[SECURITY] dp_t3_toc: Brief description`
   - Include detailed information (see below)

### What to Include

Please provide as much information as possible:

- **Description**: Clear description of the vulnerability
- **Impact**: What could an attacker do with this vulnerability?
- **Affected versions**: Which versions are affected?
- **Steps to reproduce**: Detailed steps to reproduce the issue
- **Proof of concept**: Code snippet or configuration that demonstrates the issue
- **Suggested fix**: If you have ideas on how to fix it
- **Your contact information**: For follow-up questions

### Example Report

```
Title: SQL Injection in TOC Builder

Description:
The TocBuilderService does not properly sanitize user input when...

Impact:
An attacker could inject malicious SQL code by...

Affected Versions:
- 3.0.0 to 3.0.5

Steps to Reproduce:
1. Configure the DataProcessor with...
2. Create a content element with...
3. Access the page at...

Proof of Concept:
10 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
10 {
    pidInList = 123' OR '1'='1
}

Suggested Fix:
Use parameterized queries instead of string concatenation...
```

## Response Timeline

We aim to respond to security reports according to the following timeline:

- **Initial response**: Within 48 hours
- **Status update**: Within 7 days (confirming/rejecting the vulnerability)
- **Fix timeline**:
  - **Critical**: Within 7 days
  - **High**: Within 30 days
  - **Medium**: Within 90 days
  - **Low**: Next regular release

## Disclosure Policy

- Security issues will be handled privately until a fix is available
- We will credit the reporter (unless they wish to remain anonymous)
- After a fix is released, we will publish a security advisory on GitHub
- We follow a **responsible disclosure** model with a typical embargo period of 90 days

## Security Best Practices

When using `dp_t3_toc`, follow these best practices:

### 1. Keep Extension Updated

Always use the latest version to receive security patches:

```bash
composer update ndrstmr/dp-t3-toc
```

### 2. Validate Configuration

Never trust user input in TypoScript configuration:

```typoscript
# ❌ BAD: User-controllable input
10 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
10.pidInList = {GP:page}

# ✅ GOOD: Static configuration
10 = Ndrstmr\DpT3Toc\DataProcessing\TocProcessor
10.pidInList = this
```

### 3. Restrict Backend Access

- Limit backend user permissions appropriately
- Only grant necessary access to content editing
- Use TYPO3's permission system correctly

### 4. Monitor TYPO3 Security Bulletins

Stay informed about TYPO3 core security issues:
- [TYPO3 Security Advisories](https://typo3.org/help/security-advisories)
- [TYPO3 Security Team](https://typo3.org/community/teams/security)

### 5. Regular Security Audits

- Review your TypoScript configuration regularly
- Check for unused content elements and pages
- Audit backend user permissions

## Known Security Considerations

### DataProcessor Input Validation

The `TocProcessor` validates all configuration parameters and uses Doctrine DBAL's parameterized queries to prevent SQL injection. All user input is properly escaped.

### XSS Prevention

All output is escaped using Fluid templating engine's automatic escaping. If you create custom templates, ensure you use `{item.title -> f:format.htmlspecialchars()}` for untrusted data.

### Access Control

The extension respects TYPO3's frontend restrictions (hidden, starttime, endtime, fe_group). Backend users can only see content they have permission to access.

## Security Credits

We would like to thank the following individuals for responsibly disclosing security issues:

<!-- This section will be updated when security issues are reported and fixed -->

*No security issues have been reported yet.*

## Questions?

If you have questions about this security policy, please [open a discussion](https://github.com/ndrstmr/dp_t3_toc/discussions) or contact the maintainer.

---

**Remember:** Security is a shared responsibility. If you see something, say something!
