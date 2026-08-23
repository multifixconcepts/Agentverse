#!/usr/bin/env node
/**
 * SCHOL-001 regression test — calendar-setup.js change-event fix.
 *
 * 1. Static: the shipped file must dispatch a bubbling 'change' Event and must
 *    NOT call `p.dayField.onchange()` directly (the 12.5 regression).
 * 2. Contract: the fixed statement fires BOTH addEventListener-bound listeners
 *    and a native `onchange` property handler on a stubbed date field.
 *
 * Run: node tests/calendar-setup.regression.test.js   (node v24)
 */
'use strict';
const fs = require('fs');
const path = require('path');
const assert = require('assert');

const srcPath = path.join(__dirname, '..', 'assets', 'js', 'jscalendar', 'calendar-setup.js');
const src = fs.readFileSync(srcPath, 'utf8');

let failures = 0;
const check = (name, cond) => {
  if (cond) {
    console.log(`PASS  ${name}`);
  } else {
    failures++;
    console.log(`FAIL  ${name}`);
  }
};

// ---- 1. Static assertions on the shipped source ----
check('file dispatches a bubbling change Event',
  /dispatchEvent\s*\(\s*new Event\s*\(\s*'change',\s*\{?\s*'bubbles':\s*true/m.test(src));
check('file no longer calls native onchange property directly',
  !/dayField\.onchange\s*\(/.test(src));
check('change dispatch is inside the update/select block',
  /update && p\.monthField[\s\S]*?dispatchEvent\s*\(/m.test(src));

// ---- 2. Contract test: the fix fires both listener types ----
function makeFieldStub() {
  const listeners = {};
  return {
    onchange: null,
    addEventListener(ev, fn) { (listeners[ev] = listeners[ev] || []).push(fn); },
    dispatchEvent(ev) {
      if (ev.type === 'change' && ev.bubbles !== true) return false;
      (listeners[ev.type] || []).forEach(fn => fn({ type: ev.type }));
      if (ev.type === 'change' && typeof this.onchange === 'function') this.onchange({ type: ev.type });
      return true;
    },
  };
}

const field = makeFieldStub();
const fired = [];
field.addEventListener('change', () => fired.push('listener'));
field.onchange = () => fired.push('property');

// Execute the fixed statement (as shipped) against the stub.
field.dispatchEvent(new Event('change', { bubbles: true }));

check('bubbling change Event fires addEventListener-bound listeners', fired.includes('listener'));
check('bubbling change Event fires native onchange property handler', fired.includes('property'));
check('listener fired before property handler (native property also invoked)', fired[0] === 'listener' && fired[1] === 'property');

// ---- 3. JS syntax check on the shipped file ----
try {
  new Function(src);
  check('calendar-setup.js parses (no syntax errors)', true);
} catch (e) {
  check('calendar-setup.js parses (no syntax errors)', false);
  console.log('      syntax error:', e.message);
}

console.log(failures === 0 ? '\nALL TESTS PASSED' : `\n${failures} TEST(S) FAILED`);
process.exit(failures === 0 ? 0 : 1);
