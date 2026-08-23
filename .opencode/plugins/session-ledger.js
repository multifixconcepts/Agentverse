import { appendFileSync, mkdirSync } from "node:fs";
import { join } from "node:path";

const SESSION_EVENTS = new Set([
  "session.created",
  "session.idle",
  "session.error",
  "session.compacted",
  "session.deleted",
]);

export const SessionLedger = async ({ directory, project }) => {
  const base = directory || (project && project.directory) || process.cwd();
  const logDir = join(base, "AGENTVERSE", ".sessions");
  const logFile = join(logDir, "session.log.ndjson");
  mkdirSync(logDir, { recursive: true });

  return {
    event: async ({ event }) => {
      try {
        if (!event || !SESSION_EVENTS.has(event.type)) return;
        const line = JSON.stringify({
          ts: new Date().toISOString(),
          type: event.type,
          sessionID: event.sessionID || event.properties?.sessionID || null,
        });
        appendFileSync(logFile, line + "\n");
      } catch {
        // Non-fatal: the ledger must never break the session.
      }
    },
  };
};
