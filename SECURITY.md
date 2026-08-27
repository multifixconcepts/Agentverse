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
- AgentVerse framework and control plane (`.opencode/`, `AGENTVERSE/`, `_tools/`, `_tests/`)
- CI/CD pipeline and GitHub Actions configuration
- Any target-project code in this repository that is not a vendored/upstream dependency (e.g. `clientflow/`, AgentVerse-owned additions)

### Out of Scope
- Upstream / vendored third-party code (e.g. `scholapro/` upstream RosarioSIS, `node_modules`) — report upstream
- Changes you can reproduce on local infrastructure you do not own
- Social engineering attacks
- Physical attacks

## Credential handling

- This repository is **public**. Never commit secrets, API keys, tokens, passwords, or connection strings.
- Sensitive values must be supplied via environment variables at deployment time, never hardcoded.
- The `secret-scan` CI job enforces this on every pull request.

## Security Best Practices

1. Never commit secrets, API keys, or credentials
2. Use environment variables for sensitive configuration
3. Validate all user inputs
4. Follow principle of least privilege
5. Regular dependency updates (Dependabot enabled)

## Recognition

We appreciate responsible disclosure and will acknowledge researchers who help improve our security.
