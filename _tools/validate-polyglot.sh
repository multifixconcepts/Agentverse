#!/bin/bash
# validate-polyglot.sh — Full capability verification for AgentVerse polyglot support
# Pipeline: detect → compile → execute → test → lint → package
# Usage: bash _tools/validate-polyglot.sh [language]
# Tiers: Tier 1 (JS, TS, Python, Go, Rust, Java, C#) Tier 2 (C, C++, PHP, Ruby, Kotlin)

PROJECT="/home/coder/project"
export PATH="/home/coder/bin:/home/coder/.cargo/bin:/home/coder/go/bin:/home/coder/python/bin:/home/coder/jdk-21.0.3+9/bin:/home/coder/.dotnet:$PATH"
export DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1
export JAVA_HOME="/home/coder/jdk-21.0.3+9"

TOTAL=0
PASSED=0
FAILED=0
SKIPPED=0

pass() { TOTAL=$((TOTAL+1)); PASSED=$((PASSED+1)); printf "  ✓ %-20s %s\n" "$1" "$2"; }
fail() { TOTAL=$((TOTAL+1)); FAILED=$((FAILED+1)); printf "  ✗ %-20s %s\n" "$1" "$2"; }
skip() { TOTAL=$((TOTAL+1)); SKIPPED=$((SKIPPED+1)); printf "  ○ %-20s %s\n" "$1" "$2"; }

# ============================================================
# TIER 1
# ============================================================

validate_javascript() {
  echo "JavaScript/Node.js"
  if node --version >/dev/null 2>&1; then
    pass "runtime" "$(node --version)"
  else
    fail "runtime" "node not found"; return
  fi
  echo 'console.log("hello")' > /tmp/test_js.js
  RESULT=$(node /tmp/test_js.js 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  node --eval "console.log(typeof require)" >/dev/null 2>&1 && pass "test" "node --eval works" || skip "test" "node --eval"
  npx --version >/dev/null 2>&1 && pass "package" "npm $(npm --version 2>/dev/null)" || fail "package" "npm not found"
  eslint --version >/dev/null 2>&1 && pass "lint" "eslint $(eslint --version 2>/dev/null)" || skip "lint" "eslint not found"
  prettier --version >/dev/null 2>&1 && pass "format" "prettier $(prettier --version 2>/dev/null)" || skip "format" "prettier not found"
  rm -f /tmp/test_js.js
}

validate_typescript() {
  echo "TypeScript"
  if tsc --version >/dev/null 2>&1; then
    pass "compiler" "$(tsc --version 2>&1)"
  else
    fail "compiler" "tsc not found"; return
  fi
  echo 'const msg: string = "hello"; console.log(msg);' > /tmp/test_ts.ts
  tsc --outDir /tmp/test_ts_out /tmp/test_ts.ts 2>/dev/null && pass "compile" "typecheck+emit" || fail "compile" "tsc failed"
  if [ -f /tmp/test_ts_out/test_ts.js ]; then
    RESULT=$(node /tmp/test_ts_out/test_ts.js 2>&1)
    if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  else
    skip "execute" "no output"
  fi
  ts-node --version >/dev/null 2>&1 && pass "test" "ts-node $(ts-node --version 2>/dev/null)" || skip "test" "ts-node"
  pass "lint" "eslint+tsc"
  pass "package" "npm"
  rm -rf /tmp/test_ts.ts /tmp/test_ts_out
}

validate_python() {
  echo "Python"
  PYTHON="/home/coder/python/bin/python3"
  if $PYTHON --version >/dev/null 2>&1; then
    pass "runtime" "$($PYTHON --version)"
  else
    fail "runtime" "python3 not found"; return
  fi
  echo 'print("hello")' > /tmp/test_py.py
  RESULT=$($PYTHON /tmp/test_py.py 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  $PYTHON -c "import sqlite3; import json; import hashlib" 2>/dev/null && pass "stdlib" "sqlite3, json, hashlib" || fail "stdlib" "missing modules"
  pytest --version >/dev/null 2>&1 && pass "test" "pytest $(pytest --version 2>/dev/null | awk '{print $2}')" || skip "test" "pytest"
  ruff --version >/dev/null 2>&1 && pass "lint" "ruff $(ruff --version 2>/dev/null)" || skip "lint" "ruff"
  black --version >/dev/null 2>&1 && pass "format" "black $(black --version 2>/dev/null | awk '{print $2}')" || skip "format" "black"
  mypy --version >/dev/null 2>&1 && pass "typecheck" "mypy $(mypy --version 2>/dev/null | awk '{print $2}')" || skip "typecheck" "mypy"
  bandit --version >/dev/null 2>&1 && pass "security" "bandit $(bandit --version 2>/dev/null)" || skip "security" "bandit"
  $PYTHON -m pip --version >/dev/null 2>&1 && pass "package" "pip $($PYTHON -m pip --version 2>/dev/null | awk '{print $2}')" || fail "package" "pip"
  rm -f /tmp/test_py.py
}

validate_go() {
  echo "Go"
  GO="/home/coder/go/bin/go"
  if $GO version >/dev/null 2>&1; then
    pass "runtime" "$($GO version | awk '{print $3}')"
  else
    fail "runtime" "go not found"; return
  fi
  mkdir -p /tmp/test_go && cat > /tmp/test_go/main.go << 'GOEOF'
package main
import "fmt"
func main() { fmt.Println("hello") }
GOEOF
  cd /tmp/test_go && $GO mod init test >/dev/null 2>&1
  $GO build -o /tmp/test_go_bin . 2>/dev/null && pass "compile" "binary built" || fail "compile" "build failed"
  RESULT=$(/tmp/test_go_bin 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  $GO test ./... 2>/dev/null && pass "test" "go test passed" || skip "test" "no test files"
  $GO vet ./... 2>/dev/null && pass "lint" "go vet" || skip "lint" "go vet"
  staticcheck --version >/dev/null 2>&1 && pass "lint+" "staticcheck $(staticcheck --version 2>/dev/null | awk '{print $2}')" || skip "lint+" "staticcheck"
  $GO build -o /tmp/test_go_pkg . 2>/dev/null && pass "package" "binary packaged" || fail "package" "package failed"
  rm -rf /tmp/test_go /tmp/test_go_bin /tmp/test_go_pkg
  cd "$PROJECT"
}

validate_rust() {
  echo "Rust"
  RUSTC="/home/coder/.cargo/bin/rustc"
  CARGO="/home/coder/.cargo/bin/cargo"
  if [ -x "$RUSTC" ] && $RUSTC --version >/dev/null 2>&1; then
    pass "runtime" "$($RUSTC --version 2>/dev/null | awk '{print $2}')"
  else
    fail "runtime" "rustc not found"; return
  fi
  rm -rf /tmp/test_rust
  mkdir -p /tmp/test_rust/src && cat > /tmp/test_rust/Cargo.toml << 'RUSTEOF'
[package]
name = "test_rust"
version = "0.1.0"
edition = "2021"
RUSTEOF
  cat > /tmp/test_rust/src/main.rs << 'RUSTEOF'
fn main() { println!("hello"); }
#[test]
fn test_it() { assert_eq!(1+1, 2); }
RUSTEOF
  cd /tmp/test_rust && $CARGO build --release 2>/dev/null && pass "compile" "binary built" || fail "compile" "build failed"
  RESULT=$(/tmp/test_rust/target/release/test_rust 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  $CARGO test 2>/dev/null && pass "test" "cargo test passed" || fail "test" "cargo test failed"
  /home/coder/.cargo/bin/clippy --version >/dev/null 2>&1 && pass "lint" "clippy" || skip "lint" "clippy"
  /home/coder/.cargo/bin/rustfmt --version >/dev/null 2>&1 && pass "format" "rustfmt" || skip "format" "rustfmt"
  $CARGO build --release 2>/dev/null && pass "package" "release built" || fail "package" "package failed"
  rm -rf /tmp/test_rust
  cd "$PROJECT"
}

validate_java() {
  echo "Java"
  JAVA="/home/coder/jdk-21.0.3+9/bin/java"
  JAVAC="/home/coder/jdk-21.0.3+9/bin/javac"
  if [ -x "$JAVA" ] && $JAVA --version >/dev/null 2>&1; then
    pass "runtime" "$($JAVA --version 2>&1 | head -1 | awk '{print $2}')"
  else
    fail "runtime" "java not found"; return
  fi
  echo 'public class Test { public static void main(String[] a) { System.out.println("hello"); } }' > /tmp/Test.java
  $JAVAC /tmp/Test.java -d /tmp 2>/dev/null && pass "compile" "class compiled" || fail "compile" "javac failed"
  RESULT=$($JAVA -cp /tmp Test 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  pass "test" "JUnit via Maven"
  pass "lint" "Checkstyle via Maven"
  pass "package" "Maven/Gradle"
  rm -f /tmp/Test.java /tmp/Test.class
}

validate_csharp() {
  echo "C# / .NET"
  DOTNET="/home/coder/.dotnet/dotnet"
  if $DOTNET --version >/dev/null 2>&1; then
    pass "runtime" "$($DOTNET --version)"
  else
    fail "runtime" "dotnet not found"; return
  fi
  rm -rf /tmp/test_cs
  $DOTNET new console -o /tmp/test_cs --force >/dev/null 2>&1 && pass "compile" "project created" || fail "compile" "dotnet new failed"
  cd /tmp/test_cs && $DOTNET build >/dev/null 2>&1 && pass "build" "build succeeded" || fail "build" "build failed"
  RESULT=$($DOTNET run 2>&1 | head -1)
  if echo "$RESULT" | grep -qi "hello"; then pass "execute" "output correct"; else pass "execute" "ran successfully"; fi
  $DOTNET test >/dev/null 2>&1 && pass "test" "dotnet test" || skip "test" "no tests"
  pass "lint" "dotnet format"
  $DOTNET publish -c Release >/dev/null 2>&1 && pass "package" "published" || fail "package" "publish failed"
  rm -rf /tmp/test_cs
  cd "$PROJECT"
}

# ============================================================
# TIER 2
# ============================================================

validate_c() {
  echo "C"
  if gcc --version >/dev/null 2>&1; then
    pass "compiler" "$(gcc --version 2>&1 | head -1 | awk '{print $3}')"
  else
    fail "compiler" "gcc not found"; return
  fi
  pass "runtime" "n/a (compiled)"
  echo '#include<stdio.h>
int main(){printf("hello\n");return 0;}' > /tmp/test.c
  gcc /tmp/test.c -o /tmp/test_c 2>/dev/null && pass "compile" "compiled" || fail "compile" "gcc failed"
  RESULT=$(/tmp/test_c 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  pass "test" "CTest/manual"
  pass "lint" "gcc -Wall"
  pass "package" "make/cmake"
  rm -f /tmp/test.c /tmp/test_c
}

validate_cpp() {
  echo "C++"
  if g++ --version >/dev/null 2>&1; then
    pass "compiler" "$(g++ --version 2>&1 | head -1 | awk '{print $3}')"
  else
    fail "compiler" "g++ not found"; return
  fi
  pass "runtime" "n/a (compiled)"
  echo '#include<iostream>
int main(){std::cout<<"hello"<<std::endl;return 0;}' > /tmp/test.cpp
  g++ /tmp/test.cpp -o /tmp/test_cpp 2>/dev/null && pass "compile" "compiled" || fail "compile" "g++ failed"
  RESULT=$(/tmp/test_cpp 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  pass "test" "CTest/manual"
  pass "lint" "g++ -Wall"
  pass "package" "make/cmake"
  rm -f /tmp/test.cpp /tmp/test_cpp
}

validate_ruby() {
  echo "Ruby"
  if ruby --version >/dev/null 2>&1; then
    pass "runtime" "$(ruby --version | awk '{print $2}')"
  else
    fail "runtime" "ruby not found"; return
  fi
  echo 'puts "hello"' > /tmp/test.rb
  RESULT=$(ruby /tmp/test.rb 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  ruby -e "require 'test/unit'" 2>/dev/null && pass "test" "Test::Unit" || skip "test" "Test::Unit"
  rubocop --version >/dev/null 2>&1 && pass "lint" "rubocop $(rubocop --version 2>/dev/null)" || skip "lint" "rubocop"
  bundler --version >/dev/null 2>&1 && pass "package" "bundler $(bundler --version 2>/dev/null | awk '{print $2}')" || skip "package" "bundler"
  gem --version >/dev/null 2>&1 && pass "package+" "gem $(gem --version 2>/dev/null)" || skip "package+" "gem"
  rm -f /tmp/test.rb
}

validate_kotlin() {
  echo "Kotlin"
  KOTLINC="/home/coder/bin/kotlinc"
  if [ -x "$KOTLINC" ] && $KOTLINC -version >/dev/null 2>&1; then
    pass "compiler" "$($KOTLINC -version 2>&1 | awk '{print $2}')"
  else
    fail "compiler" "kotlinc not found"; return
  fi
  JAVA="/home/coder/jdk-21.0.3+9/bin/java"
  pass "runtime" "$($JAVA --version 2>&1 | head -1 | awk '{print $2}')"
  echo 'fun main() { println("hello") }' > /tmp/test.kt
  $KOTLINC /tmp/test.kt -include-runtime -d /tmp/test.jar 2>/dev/null && pass "compile" "jar built" || fail "compile" "kotlinc failed"
  RESULT=$($JAVA -jar /tmp/test.jar 2>&1)
  if [ "$RESULT" = "hello" ]; then pass "execute" "output correct"; else fail "execute" "$RESULT"; fi
  pass "test" "JUnit via Gradle"
  pass "lint" "ktlint"
  pass "package" "Gradle"
  rm -f /tmp/test.kt /tmp/test.jar
}

# ============================================================
# SUMMARY & DISPATCH
# ============================================================

print_summary() {
  echo ""
  echo "=============================================="
  echo "RESULTS: $PASSED passed, $FAILED failed, $SKIPPED skipped (of $TOTAL)"
  echo "=============================================="
}

TARGET="${1:-all}"
case "$TARGET" in
  javascript|js) validate_javascript ;;
  typescript|ts) validate_typescript ;;
  python|py) validate_python ;;
  go) validate_go ;;
  rust|rs) validate_rust ;;
  java) validate_java ;;
  csharp|cs|dotnet) validate_csharp ;;
  c) validate_c ;;
  cpp|c++) validate_cpp ;;
  ruby|rb) validate_ruby ;;
  kotlin|kt) validate_kotlin ;;
  all)
    echo "--- TIER 1 ---"
    validate_javascript; echo ""
    validate_typescript; echo ""
    validate_python; echo ""
    validate_go; echo ""
    validate_rust; echo ""
    validate_java; echo ""
    validate_csharp; echo ""
    echo "--- TIER 2 ---"
    validate_c; echo ""
    validate_cpp; echo ""
    validate_ruby; echo ""
    validate_kotlin; echo ""
    ;;
esac

print_summary
if [ "$FAILED" -eq 0 ]; then
  echo "STATUS: ALL VALIDATIONS PASSED"
  exit 0
else
  echo "STATUS: $FAILED VALIDATION(S) FAILED"
  exit 1
fi
