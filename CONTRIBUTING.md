# Contributing to AgentVerse

Thank you for contributing to AgentVerse! This guide will help you get started.

## Development Workflow

### 1. Fork and Clone
```bash
git clone https://github.com/multifixconcepts/Agentverse.git
cd Agentverse
```

### 2. Create Feature Branch
```bash
git checkout -b feature/SCHOL-XXX-description
```

### 3. Make Changes
- Follow existing code style
- Add tests for new functionality
- Update documentation as needed

### 4. Run Tests Locally
```bash
# Control plane regression (30 tests)
bash _tests/control-plane-regression.sh

# Adversarial tests (28 tests)
bash _tests/scenario8-adversarial.sh

# Remediation tests (20 tests)
bash _tests/remediation-tests.sh
```

### 5. Commit and Push
```bash
git add .
git commit -m "feat: description of changes (SCHOL-XXX)"
git push origin feature/SCHOL-XXX-description
```

### 6. Create Pull Request
- Fill out PR template completely
- Link related ticket
- Ensure all CI checks pass

## Code Standards

### PHP
- Follow PSR-12 coding standards
- Use meaningful variable/function names
- Add PHPDoc blocks for complex functions
- Run `php -l` to check syntax before commit

### Shell Scripts
- Use `set -e` for error handling
- Add comments explaining complex logic
- Make scripts executable (`chmod +x`)

### JavaScript (Node.js)
- Use ES6+ syntax
- Add JSDoc comments
- Handle errors appropriately

## Testing Requirements

All changes must include:
- **Regression tests**: For control plane changes
- **Unit tests**: For new functions
- **Integration tests**: For feature changes

## Commit Messages

Use conventional commits:
```
feat: new feature description
fix: bug fix description
docs: documentation update
test: adding/updating tests
refactor: code refactoring
chore: maintenance tasks
```

## Pull Request Checklist

- [ ] Code follows existing style
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] ORG_CHECKSUM.json recomputed (if control plane changed)
- [ ] All CI checks pass
- [ ] Related ticket linked

## Questions?

Open a GitHub Discussion or contact @multifixconcepts.
