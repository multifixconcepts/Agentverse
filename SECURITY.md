# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability, please report it responsibly:

1. **Do NOT** open a public GitHub issue
2. Email: security@multifixconcepts.com (or contact @multifixconcepts directly)
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

## Response Timeline

- **Acknowledgment**: Within 48 hours
- **Initial assessment**: Within 1 week
- **Fix timeline**: Depends on severity
  - Critical: 24-48 hours
  - High: 1 week
  - Medium: 2 weeks
  - Low: Next release

## Scope

### In Scope
- AgentVerse control plane
- ScholaPro application code
- CI/CD pipeline
- Authentication/authorization
- Data handling

### Out of Scope
- Third-party dependencies (report upstream)
- Social engineering attacks
- Physical attacks

## Security Best Practices

1. Never commit secrets, API keys, or credentials
2. Use environment variables for sensitive configuration
3. Validate all user inputs
4. Follow principle of least privilege
5. Regular dependency updates (Dependabot enabled)

## Recognition

We appreciate responsible disclosure and will acknowledge researchers who help improve our security.
