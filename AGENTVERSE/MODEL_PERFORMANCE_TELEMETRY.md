# Model Performance Telemetry — AgentVerse 2.0

**Version:** 2.0.1
**Owner:** chief-architect
**Status:** Active

---

## Purpose

Track which models perform best for which tasks. Telemetry is organizational metadata only — it does not contain user data, secrets, or proprietary content.

---

## Metrics Per Model

| Metric | Description | How to Measure |
|--------|-------------|----------------|
| **Completion accuracy** | % of claimed completions that pass independent verification | `verified / total_claims * 100` |
| **Gate pass rate** | % of gate submissions that pass on first attempt | `first_attempt_passes / total_submissions * 100` |
| **Verification rate** | % of ACs verified on first attempt | `first_attempt_verified / total_acs * 100` |
| **Failure rate** | % of tasks requiring rework | `rework_required / total_tasks * 100` |
| **Context reconstruction time** | Time to load durable state and resume (seconds) | `productive_work_timestamp - failover_timestamp` |
| **Contract compliance** | % of outputs matching contract requirements | `contract_passes / total_outputs * 100` |
| **Secret leakage** | Number of secrets found in output | Count from `_tools/scan-secrets.sh` |

---

## Storage Format

Telemetry is stored in `AGENTVERSE/TELEMETRY.json`:

```json
{
  "version": "2.0.1",
  "models": {
    "model-name": {
      "metrics": {
        "completion_accuracy": 0.0,
        "gate_pass_rate": 0.0,
        "verification_rate": 0.0,
        "failure_rate": 0.0,
        "avg_context_reconstruction_seconds": 0.0,
        "contract_compliance": 0.0,
        "secret_leakage_count": 0
      },
      "task_history": [
        {
          "timestamp": "ISO-8601",
          "task_type": "implementation|verification|refactor|debug",
          "ticket_id": "T-XXX",
          "claimed_complete": true,
          "verified_complete": true,
          "gate_passed": true,
          "rework_required": false,
          "context_reconstruction_seconds": 0
        }
      ]
    }
  },
  "failover_history": [
    {
      "timestamp": "ISO-8601",
      "from_model": "model-a",
      "to_model": "model-b",
      "recovery_seconds": 0,
      "claims_verified": 0,
      "claims_rejected": 0
    }
  ]
}
```

---

## Collection Procedure

| Trigger | What to Record |
|---------|----------------|
| After each gate verdict | Model name, task type, verdict (PASS/FAIL), ACs verified |
| After each verification | Ticket ID, pass/fail, verification method used |
| After each failover | Source model, target model, recovery time, claims verified/rejected |
| After each secret scan | Model name, secrets found (count only, never content) |
| After each rework cycle | Ticket ID, original model, rework model, root cause category |

---

## Usage

- **Failover priority ordering**: Prefer models with higher completion accuracy and gate pass rate
- **Task routing**: Route task types to models with best historical performance for that type
- **Degradation detection**: Alert if a model's metrics drop below rolling average
- **Capacity planning**: Identify which models handle volume well vs. which degrade

---

## Privacy

- No user data in telemetry
- No file contents in telemetry
- No secrets in telemetry (only counts)
- Only organizational performance metrics
- Telemetry files are NOT committed to version control
