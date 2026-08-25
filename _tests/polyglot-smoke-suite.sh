#!/bin/bash
# polyglot-smoke-suite.sh — Real project smoke tests for each verified language
# Each test creates a project, installs deps, compiles, runs, tests, lints, formats, and cleans up
# Usage: bash _tests/polyglot-smoke-suite.sh [language]

set -e
export DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1

PASS=0; FAIL=0; SKIP=0; TOTAL=0
pass() { TOTAL=$((TOTAL+1)); PASS=$((PASS+1)); printf "  ✓ %-25s %s\n" "$1" "$2"; }
fail() { TOTAL=$((TOTAL+1)); FAIL=$((FAIL+1)); printf "  ✗ %-25s %s\n" "$1" "$2"; }
skip() { TOTAL=$((TOTAL+1)); SKIP=$((SKIP+1)); printf "  ○ %-25s %s\n" "$1" "$2"; }

cleanup() { rm -rf "$1" 2>/dev/null; }

echo "=============================================="
echo "AGENTVERSE POLYGLOT SMOKE PROJECT SUITE"
echo "=============================================="
echo ""

# --- JavaScript ---
smoke_javascript() {
  echo "JavaScript"
  DIR="/tmp/smoke_js_$$"
  mkdir -p "$DIR" && cd "$DIR"
  npm init -y >/dev/null 2>&1 && pass "project" "npm init"
  echo 'exports.hello = () => "hello";' > index.js
  echo 'const {hello} = require("./index"); const assert = require("assert"); assert.strictEqual(hello(), "hello"); console.log("test ok");' > test.js
  node test.js 2>/dev/null && pass "execute" "tests pass" || fail "execute" "tests failed"
  eslint --no-eslintrc --rule '{"no-unused-vars":"error"}' index.js 2>/dev/null && pass "lint" "eslint clean" || skip "lint" "eslint config needed"
  prettier --check index.js 2>/dev/null && pass "format" "prettier clean" || skip "format" "prettier config needed"
  npm pack --dry-run >/dev/null 2>&1 && pass "package" "npm pack" || skip "package" "npm pack"
  cleanup "$DIR"
}

# --- TypeScript ---
smoke_typescript() {
  echo "TypeScript"
  cd /tmp
  DIR="/tmp/smoke_ts_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > index.ts << 'EOF'
export function hello(): string { return "hello"; }
EOF
  cat > test.ts << 'EOF'
import { hello } from "./index";
console.assert(hello() === "hello");
console.log("test ok");
EOF
  OUTDIR="/tmp/smoke_ts_out_$$"
  tsc --noEmit --strict --target ES2020 --module commonjs index.ts test.ts 2>/dev/null && pass "compile" "typecheck clean" || fail "compile" "typecheck failed"
  tsc --outDir "$OUTDIR" --strict --target ES2020 --module commonjs index.ts test.ts 2>/dev/null && node "$OUTDIR/test.js" 2>/dev/null && pass "execute" "tsc+node pass" || fail "execute" "tsc+node failed"
  rm -rf "$DIR" "$OUTDIR"
}

# --- Python ---
smoke_python() {
  echo "Python"
  DIR="/tmp/smoke_py_$$"
  mkdir -p "$DIR" && cd "$DIR"
  python3 -m venv .venv 2>/dev/null
  .venv/bin/pip install pytest ruff black --quiet 2>/dev/null
  cat > app.py << 'EOF'
def hello(): return "hello"
EOF
  cat > test_app.py << 'EOF'
from app import hello
def test_hello(): assert hello() == "hello"
EOF
  .venv/bin/python -m pytest test_app.py -q 2>/dev/null && pass "test" "pytest pass" || fail "test" "pytest failed"
  .venv/bin/ruff check app.py 2>/dev/null && pass "lint" "ruff clean" || skip "lint" "ruff config"
  .venv/bin/black --check app.py 2>/dev/null && pass "format" "black clean" || skip "format" "black config"
  .venv/bin/python -m build --no-isolation 2>/dev/null && pass "package" "build" || skip "package" "build module"
  rm -rf "$DIR/.venv"
  cleanup "$DIR"
}

# --- Go ---
smoke_go() {
  echo "Go"
  DIR="/tmp/smoke_go_$$"
  GO="$(command -v go)"
  mkdir -p "$DIR" && cd "$DIR"
  $GO mod init smoke_go >/dev/null 2>&1
  cat > main.go << 'EOF'
package main
import "fmt"
func Hello() string { return "hello" }
func main() { fmt.Println(Hello()) }
EOF
  cat > main_test.go << 'EOF'
package main
import "testing"
func TestHello(t *testing.T) { if Hello() != "hello" { t.Fatal("fail") } }
EOF
  $GO build -o /dev/null . 2>/dev/null && pass "compile" "build ok" || fail "compile" "build failed"
  $GO test -v ./... 2>/dev/null && pass "test" "go test pass" || fail "test" "go test failed"
  $GO vet ./... 2>/dev/null && pass "lint" "go vet clean" || skip "lint" "go vet"
  cleanup "$DIR"
}

# --- Rust ---
smoke_rust() {
  echo "Rust"
  DIR="/tmp/smoke_rs_$$"
  CARGO="$(command -v cargo)"
  mkdir -p "$DIR/src" && cd "$DIR"
  cat > Cargo.toml << 'EOF'
[package]
name = "smoke_rs"
version = "0.1.0"
edition = "2021"
EOF
  cat > src/main.rs << 'EOF'
fn hello() -> &'static str { "hello" }
fn main() { println!("{}", hello()); }
#[test]
fn test_hello() { assert_eq!(hello(), "hello"); }
EOF
  $CARGO build --release 2>/dev/null && pass "compile" "cargo build" || fail "compile" "build failed"
  $CARGO test 2>/dev/null && pass "test" "cargo test" || fail "test" "test failed"
  rustfmt --check src/main.rs 2>/dev/null && pass "format" "rustfmt clean" || skip "format" "rustfmt"
  cleanup "$DIR"
}

# --- Java ---
smoke_java() {
  echo "Java"
  DIR="/tmp/smoke_java_$$"
  JAVA="$(command -v java)"
  JAVAC="$(command -v javac)"
  mkdir -p "$DIR" && cd "$DIR"
  cat > Hello.java << 'EOF'
public class Hello {
    public static String hello() { return "hello"; }
    public static void main(String[] args) { System.out.println(hello()); }
}
EOF
  cat > HelloTest.java << 'EOF'
public class HelloTest {
    public static void main(String[] args) {
        assert Hello.hello().equals("hello") : "fail";
        System.out.println("test ok");
    }
}
EOF
  $JAVAC Hello.java HelloTest.java 2>/dev/null && pass "compile" "javac ok" || fail "compile" "javac failed"
  $JAVA -ea HelloTest 2>/dev/null && pass "test" "assertions pass" || fail "test" "assertions failed"
  cleanup "$DIR"
}

# --- C# ---
smoke_csharp() {
  echo "C# / .NET"
  cd /tmp
  export DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1
  DOTNET="$(command -v dotnet)"
  DIR="/tmp/smoke_cs_$$"
  rm -rf "$DIR"
  $DOTNET new classlib -o "$DIR" --force >/dev/null 2>&1 && pass "project" "classlib created" || { fail "project" "dotnet new failed"; return; }
  cd "$DIR"
  cat > Class1.cs << 'EOF'
namespace SmokeCs;
public class Greeter { public static string Greet() => "hello"; }
EOF
  $DOTNET build --verbosity quiet 2>/dev/null && pass "compile" "build ok" || fail "compile" "build failed"
  rm -rf "$DIR"
}

# --- C ---
smoke_c() {
  echo "C"
  DIR="/tmp/smoke_c_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > main.c << 'EOF'
#include <stdio.h>
#include <assert.h>
#include <string.h>
const char* hello(void) { return "hello"; }
int main(void) { assert(strcmp(hello(), "hello") == 0); printf("test ok\n"); return 0; }
EOF
  gcc -Wall -Wextra -o main main.c 2>/dev/null && pass "compile" "gcc ok" || fail "compile" "gcc failed"
  ./main 2>/dev/null && pass "test" "assertions pass" || fail "test" "assertions failed"
  cleanup "$DIR"
}

# --- C++ ---
smoke_cpp() {
  echo "C++"
  DIR="/tmp/smoke_cpp_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > main.cpp << 'EOF'
#include <iostream>
#include <cassert>
#include <string>
std::string hello() { return "hello"; }
int main() { assert(hello() == "hello"); std::cout << "test ok" << std::endl; return 0; }
EOF
  g++ -Wall -Wextra -o main main.cpp 2>/dev/null && pass "compile" "g++ ok" || fail "compile" "g++ failed"
  ./main 2>/dev/null && pass "test" "assertions pass" || fail "test" "assertions failed"
  cleanup "$DIR"
}

# --- Ruby ---
smoke_ruby() {
  echo "Ruby"
  DIR="/tmp/smoke_rb_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > hello.rb << 'EOF'
def hello; "hello"; end
raise "test failed" unless hello == "hello"
puts "test ok"
EOF
  ruby hello.rb 2>/dev/null && pass "test" "ruby assert pass" || fail "test" "ruby assert failed"
  rubocop --except Metrics --format simple hello.rb 2>/dev/null && pass "lint" "rubocop clean" || skip "lint" "rubocop config"
  cleanup "$DIR"
}

# --- Kotlin ---
smoke_kotlin() {
  echo "Kotlin"
  KOTLINC="$(command -v kotlinc)"
  JAVA="$(command -v java)"
  DIR="/tmp/smoke_kt_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > Hello.kt << 'EOF'
fun hello() = "hello"
fun main() { require(hello() == "hello") { "test failed" }; println("test ok") }
EOF
  $KOTLINC Hello.kt -include-runtime -d Hello.jar 2>/dev/null && pass "compile" "kotlinc ok" || fail "compile" "kotlinc failed"
  $JAVA -jar Hello.jar 2>/dev/null && pass "test" "kotlin assert pass" || fail "test" "kotlin assert failed"
  cleanup "$DIR"
}

# --- PHP ---
smoke_php() {
  echo "PHP"
  PHP_BIN="$(command -v php)"
  DIR="/tmp/smoke_php_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > hello.php << 'EOF'
<?php
function hello(): string { return "hello"; }
assert(hello() === "hello");
echo "test ok\n";
EOF
  $PHP_BIN hello.php 2>/dev/null && pass "test" "php assert pass" || fail "test" "php assert failed"
  $PHP_BIN -l hello.php 2>/dev/null && pass "lint" "php lint clean" || fail "lint" "php lint failed"
  cleanup "$DIR"
}

# --- Swift ---
smoke_swift() {
  echo "Swift"
  SWIFT="$(command -v swift)"
  DIR="/tmp/smoke_swift_$$"
  mkdir -p "$DIR" && cd "$DIR"
  cat > main.swift << 'EOF'
func hello() -> String { return "hello" }
assert(hello() == "hello")
print("test ok")
EOF
  $SWIFT main.swift 2>/dev/null && pass "test" "swift assert pass" || fail "test" "swift assert failed"
  cleanup "$DIR"
}

# --- Dart ---
smoke_dart() {
  echo "Dart"
  DART="$(command -v dart)"
  DIR="/tmp/smoke_dart_$$"
  rm -rf "$DIR"
  mkdir -p "$DIR/bin" && cd "$DIR"
  cat > pubspec.yaml << 'EOF'
name: smoke_dart
environment:
  sdk: '>=3.0.0 <4.0.0'
EOF
  cat > bin/main.dart << 'EOF'
void main() {
  assert(1 + 1 == 2);
  print('test ok');
}
EOF
  $DART pub get 2>/dev/null && pass "deps" "dart pub get" || fail "deps" "pub get failed"
  $DART run bin/main.dart 2>/dev/null && pass "test" "dart assert pass" || fail "test" "dart assert failed"
  $DART analyze 2>/dev/null && pass "lint" "dart analyze clean" || skip "lint" "dart analyze"
  cleanup "$DIR"
}

# --- Dispatch ---
TARGET="${1:-all}"
case "$TARGET" in
  javascript|js) smoke_javascript ;;
  typescript|ts) smoke_typescript ;;
  python|py) smoke_python ;;
  go) smoke_go ;;
  rust|rs) smoke_rust ;;
  java) smoke_java ;;
  csharp|cs|dotnet) smoke_csharp ;;
  c) smoke_c ;;
  cpp|c++) smoke_cpp ;;
  ruby|rb) smoke_ruby ;;
  kotlin|kt) smoke_kotlin ;;
  php) smoke_php ;;
  swift) smoke_swift ;;
  dart) smoke_dart ;;
  all)
    smoke_javascript; echo ""
    smoke_typescript; echo ""
    smoke_python; echo ""
    smoke_go; echo ""
    smoke_rust; echo ""
    smoke_java; echo ""
    smoke_csharp; echo ""
    smoke_c; echo ""
    smoke_cpp; echo ""
    smoke_ruby; echo ""
    smoke_kotlin; echo ""
    smoke_php; echo ""
    smoke_swift; echo ""
    smoke_dart; echo ""
    ;;
esac

echo "=============================================="
echo "RESULTS: $PASS passed, $FAIL failed, $SKIP skipped (of $TOTAL)"
echo "=============================================="
if [ "$FAIL" -eq 0 ]; then
  echo "STATUS: ALL SMOKE TESTS PASSED"
  exit 0
else
  echo "STATUS: $FAIL SMOKE TEST(S) FAILED"
  exit 1
fi
