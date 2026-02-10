#!/usr/bin/env bash
# TDD Enforcer - Ensure Test-Driven Development practices
# Feature: workflow-improvements-2026 | Task: BE-017

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_ROOT="$(cd "${WORKFLOW_DIR}/../.." && pwd)"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
TDD_CONFIG_FILE="${WORKFLOW_DIR}/tdd_config.yaml"
TDD_STRICTNESS="${TDD_STRICTNESS:-medium}"  # strict, medium, relaxed

# Test file patterns by extension
declare -A TEST_PATTERNS=(
    ["ts"]="*.test.ts *.spec.ts"
    ["tsx"]="*.test.tsx *.spec.tsx"
    ["js"]="*.test.js *.spec.js"
    ["jsx"]="*.test.jsx *.spec.jsx"
    ["py"]="test_*.py *_test.py"
    ["php"]="*Test.php"
    ["go"]="*_test.go"
    ["rb"]="*_spec.rb *_test.rb"
    ["rs"]="*_test.rs"
    ["java"]="*Test.java"
    ["sh"]="test_*.sh"
)

# Directories to search for tests
TEST_DIRECTORIES=(
    "tests"
    "test"
    "__tests__"
    "spec"
    "specs"
)

# Get test file path for a source file
# @param $1 source_file - Path to source file
# @return string - Expected test file path(s)
_get_expected_test_paths() {
    local source_file="$1"
    local basename
    local dirname
    local ext

    basename=$(basename "$source_file")
    dirname=$(dirname "$source_file")
    ext="${basename##*.}"

    # Remove extension
    local name_no_ext="${basename%.*}"

    # Get test patterns for this extension
    local patterns="${TEST_PATTERNS[$ext]:-}"

    if [[ -z "$patterns" ]]; then
        echo ""
        return
    fi

    local paths=()

    # Generate possible test file paths
    case "$ext" in
        ts|tsx|js|jsx)
            # TypeScript/JavaScript: file.test.ts, file.spec.ts
            paths+=("${dirname}/${name_no_ext}.test.${ext}")
            paths+=("${dirname}/${name_no_ext}.spec.${ext}")
            paths+=("${dirname}/__tests__/${name_no_ext}.test.${ext}")
            # Parallel test directory
            local test_dir="${dirname/src/tests}"
            paths+=("${test_dir}/${name_no_ext}.test.${ext}")
            ;;
        py)
            # Python: test_file.py, file_test.py
            paths+=("${dirname}/test_${name_no_ext}.py")
            paths+=("${dirname}/${name_no_ext}_test.py")
            paths+=("tests/${name_no_ext}_test.py")
            paths+=("tests/test_${name_no_ext}.py")
            ;;
        php)
            # PHP: FileTest.php
            paths+=("${dirname}/${name_no_ext}Test.php")
            paths+=("tests/${name_no_ext}Test.php")
            paths+=("tests/Unit/${name_no_ext}Test.php")
            ;;
        go)
            # Go: file_test.go (same directory)
            paths+=("${dirname}/${name_no_ext}_test.go")
            ;;
        sh)
            # Bash: test_file.sh
            paths+=("tests/test_${name_no_ext}.sh")
            paths+=("${dirname}/test_${name_no_ext}.sh")
            ;;
        *)
            # Generic pattern
            paths+=("tests/test_${name_no_ext}.${ext}")
            ;;
    esac

    printf '%s\n' "${paths[@]}"
}

# Check if a test file exists for a source file
# @param $1 source_file - Path to source file
# @return bool - true if test exists
tdd_check_test_exists() {
    local source_file="${1:?Source file required}"

    # Skip test files themselves
    if _is_test_file "$source_file"; then
        return 0
    fi

    # Skip non-code files
    if ! _is_code_file "$source_file"; then
        return 0
    fi

    local expected_paths
    expected_paths=$(_get_expected_test_paths "$source_file")

    if [[ -z "$expected_paths" ]]; then
        # Unknown file type, skip
        return 0
    fi

    while IFS= read -r test_path; do
        if [[ -f "$PROJECT_ROOT/$test_path" ]]; then
            return 0
        fi
    done <<< "$expected_paths"

    return 1
}

# Check if file is a test file
# @param $1 file_path - Path to file
# @return bool - true if test file
_is_test_file() {
    local file_path="$1"
    local basename
    basename=$(basename "$file_path")

    # Check common test patterns
    [[ "$basename" == test_* ]] && return 0
    [[ "$basename" == *_test.* ]] && return 0
    [[ "$basename" == *.test.* ]] && return 0
    [[ "$basename" == *.spec.* ]] && return 0
    [[ "$basename" == *Test.php ]] && return 0
    [[ "$basename" == *Test.java ]] && return 0
    [[ "$basename" == *_spec.rb ]] && return 0

    # Check if in test directory
    [[ "$file_path" == */test/* ]] && return 0
    [[ "$file_path" == */tests/* ]] && return 0
    [[ "$file_path" == */__tests__/* ]] && return 0
    [[ "$file_path" == */spec/* ]] && return 0

    return 1
}

# Check if file is a code file (not config, docs, etc.)
_is_code_file() {
    local file_path="$1"
    local ext="${file_path##*.}"

    local code_extensions="ts tsx js jsx py php go rb rs java sh bash"

    [[ " $code_extensions " == *" $ext "* ]]
}

# Verify test-first commit order
# @param $1 commit_range - Git commit range (optional, default: HEAD~1..HEAD)
# @return JSON - Verification result
tdd_verify_order() {
    local commit_range="${1:-HEAD~1..HEAD}"

    local source_files=()
    local test_files=()
    local violations=()

    # Get files changed in commit range
    while IFS= read -r file; do
        if _is_test_file "$file"; then
            test_files+=("$file")
        elif _is_code_file "$file"; then
            source_files+=("$file")
        fi
    done < <(git diff --name-only "$commit_range" 2>/dev/null | grep -v '^$')

    # In strict mode, every source file should have a corresponding test file
    if [[ "$TDD_STRICTNESS" == "strict" ]]; then
        for source_file in "${source_files[@]}"; do
            local found_test=false

            for test_file in "${test_files[@]}"; do
                if _test_matches_source "$test_file" "$source_file"; then
                    found_test=true
                    break
                fi
            done

            if ! $found_test && ! tdd_check_test_exists "$source_file"; then
                violations+=("$source_file: no test file in commit or existing")
            fi
        done
    fi

    # Output result
    local has_violations="false"
    if [[ ${#violations[@]} -gt 0 ]]; then
        has_violations="true"
    fi

    cat << EOF
{
  "commit_range": "${commit_range}",
  "strictness": "${TDD_STRICTNESS}",
  "source_files": ${#source_files[@]},
  "test_files": ${#test_files[@]},
  "has_violations": ${has_violations},
  "violations": [$(printf '"%s",' "${violations[@]}" | sed 's/,$//' 2>/dev/null || echo "")]
}
EOF

    [[ "$has_violations" == "false" ]]
}

# Check if test file matches source file
_test_matches_source() {
    local test_file="$1"
    local source_file="$2"

    local test_name
    local source_name

    test_name=$(basename "$test_file" | sed 's/\.\(test\|spec\|_test\)\..*$//' | sed 's/^test_//' | sed 's/Test$//')
    source_name=$(basename "$source_file" | sed 's/\.[^.]*$//')

    [[ "${test_name,,}" == "${source_name,,}" ]]
}

# Check for test deletions in staged changes
# @return bool - true if tests were deleted
tdd_check_deletion() {
    local deleted_tests=()

    # Check staged deletions
    while IFS= read -r file; do
        if _is_test_file "$file"; then
            deleted_tests+=("$file")
        fi
    done < <(git diff --cached --name-only --diff-filter=D 2>/dev/null | grep -v '^$')

    if [[ ${#deleted_tests[@]} -gt 0 ]]; then
        echo -e "${RED}ERROR: Test files are being deleted!${NC}" >&2
        for test in "${deleted_tests[@]}"; do
            echo -e "  ${RED}- ${test}${NC}" >&2
        done
        return 1
    fi

    return 0
}

# Check staged files for TDD compliance
# @return bool - true if compliant
tdd_check_staged() {
    echo -e "${BLUE}Checking TDD compliance for staged files...${NC}"

    local errors=0
    local warnings=0

    # Check for test deletions
    if ! tdd_check_deletion; then
        ((errors++))
    fi

    # Check each staged source file
    while IFS= read -r file; do
        if _is_code_file "$file" && ! _is_test_file "$file"; then
            if ! tdd_check_test_exists "$file"; then
                if [[ "$TDD_STRICTNESS" == "strict" ]]; then
                    echo -e "${RED}ERROR: Missing test for: ${file}${NC}" >&2
                    ((errors++))
                elif [[ "$TDD_STRICTNESS" == "medium" ]]; then
                    echo -e "${YELLOW}WARNING: Missing test for: ${file}${NC}" >&2
                    ((warnings++))
                fi
            else
                echo -e "${GREEN}OK: ${file} has test${NC}"
            fi
        fi
    done < <(git diff --cached --name-only --diff-filter=ACMR 2>/dev/null | grep -v '^$')

    echo ""
    echo "=== TDD Check Summary ==="
    echo "Strictness: ${TDD_STRICTNESS}"
    echo "Errors: ${errors}"
    echo "Warnings: ${warnings}"

    if [[ $errors -gt 0 ]]; then
        echo -e "${RED}TDD check FAILED${NC}"
        return 1
    elif [[ $warnings -gt 0 ]]; then
        echo -e "${YELLOW}TDD check passed with warnings${NC}"
        return 0
    else
        echo -e "${GREEN}TDD check PASSED${NC}"
        return 0
    fi
}

# Generate test file template
# @param $1 source_file - Source file to generate test for
# @return string - Test file content
tdd_generate_test() {
    local source_file="${1:?Source file required}"
    local ext="${source_file##*.}"
    local basename
    local name_no_ext

    basename=$(basename "$source_file")
    name_no_ext="${basename%.*}"

    case "$ext" in
        ts|tsx)
            cat << EOF
import { describe, it, expect } from 'vitest';
// import { ${name_no_ext} } from './${name_no_ext}';

describe('${name_no_ext}', () => {
  it('should exist', () => {
    // TODO: Add tests
    expect(true).toBe(true);
  });

  // RED: Write failing test first
  // GREEN: Implement minimum code to pass
  // REFACTOR: Clean up while keeping tests green
});
EOF
            ;;
        js|jsx)
            cat << EOF
describe('${name_no_ext}', () => {
  it('should exist', () => {
    // TODO: Add tests
    expect(true).toBe(true);
  });

  // RED: Write failing test first
  // GREEN: Implement minimum code to pass
  // REFACTOR: Clean up while keeping tests green
});
EOF
            ;;
        py)
            cat << EOF
"""Tests for ${name_no_ext}"""
import pytest
# from ${name_no_ext} import *


class Test${name_no_ext^}:
    """Test cases for ${name_no_ext}"""

    def test_exists(self):
        """Basic existence test"""
        # TODO: Add tests
        assert True

    # RED: Write failing test first
    # GREEN: Implement minimum code to pass
    # REFACTOR: Clean up while keeping tests green
EOF
            ;;
        sh)
            cat << EOF
#!/usr/bin/env bash
# Tests for ${name_no_ext}

set -euo pipefail

SCRIPT_DIR="\$(cd "\$(dirname "\${BASH_SOURCE[0]}")" && pwd)"
# source "\${SCRIPT_DIR}/../${name_no_ext}.sh"

# Test helpers
test_count=0
pass_count=0
fail_count=0

assert_equals() {
    local expected="\$1"
    local actual="\$2"
    local message="\${3:-}"
    ((test_count++))
    if [[ "\$expected" == "\$actual" ]]; then
        ((pass_count++))
        echo "PASS: \$message"
    else
        ((fail_count++))
        echo "FAIL: \$message (expected: \$expected, got: \$actual)"
    fi
}

# Tests
test_${name_no_ext}_exists() {
    # TODO: Add tests
    assert_equals "true" "true" "${name_no_ext} exists"
}

# Run tests
test_${name_no_ext}_exists

# Summary
echo ""
echo "=== Test Summary ==="
echo "Total: \$test_count"
echo "Passed: \$pass_count"
echo "Failed: \$fail_count"

[[ \$fail_count -eq 0 ]]
EOF
            ;;
        *)
            echo "// TODO: Add tests for ${name_no_ext}"
            ;;
    esac
}

# Display TDD status for a file
# @param $1 file_path - Path to file
tdd_display() {
    local file_path="${1:?File path required}"

    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║               TDD STATUS                                   ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
    echo "File: ${file_path}"

    if _is_test_file "$file_path"; then
        echo -e "Type: ${GREEN}Test file${NC}"
        echo -e "Status: ${GREEN}N/A (this is a test)${NC}"
        return 0
    fi

    if ! _is_code_file "$file_path"; then
        echo -e "Type: ${YELLOW}Non-code file${NC}"
        echo -e "Status: ${YELLOW}N/A (not code)${NC}"
        return 0
    fi

    echo "Type: Source file"
    echo ""

    local expected_paths
    expected_paths=$(_get_expected_test_paths "$file_path")

    echo "Expected test locations:"
    local found_any=false
    while IFS= read -r test_path; do
        if [[ -f "$PROJECT_ROOT/$test_path" ]]; then
            echo -e "  ${GREEN}[EXISTS] ${test_path}${NC}"
            found_any=true
        else
            echo -e "  ${YELLOW}[MISSING] ${test_path}${NC}"
        fi
    done <<< "$expected_paths"

    echo ""
    if $found_any; then
        echo -e "Status: ${GREEN}TDD COMPLIANT${NC}"
    else
        echo -e "Status: ${RED}MISSING TEST${NC}"
        echo ""
        echo "To generate a test template:"
        echo "  tdd_generate_test \"$file_path\""
    fi
}

# Usage information
usage() {
    cat << EOF
TDD Enforcer - Ensure Test-Driven Development practices

Usage:
  source tdd_enforcer.sh

Functions:
  tdd_check_test_exists <file>    Check if test file exists
  tdd_check_staged                Check TDD compliance for staged files
  tdd_verify_order [commit_range] Verify test-first commit order
  tdd_check_deletion              Check for test file deletions
  tdd_generate_test <file>        Generate test file template
  tdd_display <file>              Display TDD status for file

Environment:
  TDD_STRICTNESS    Enforcement level: strict, medium, relaxed (default: medium)

Examples:
  # Check if test exists
  tdd_check_test_exists "src/auth/user.ts"

  # Check all staged files
  tdd_check_staged

  # Verify commit order
  tdd_verify_order "HEAD~5..HEAD"

  # Generate test template
  tdd_generate_test "src/module.ts" > src/module.test.ts

  # Display status
  tdd_display "src/module.ts"

Strictness Levels:
  strict   - Block commits without tests
  medium   - Warn but allow commits without tests
  relaxed  - Only check test deletions
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        check)
            tdd_check_staged
            ;;
        exists)
            if tdd_check_test_exists "${2:-}"; then
                echo "Test exists"
            else
                echo "Test missing"
                exit 1
            fi
            ;;
        verify)
            tdd_verify_order "${2:-HEAD~1..HEAD}"
            ;;
        deletion)
            tdd_check_deletion
            ;;
        generate)
            tdd_generate_test "${2:-}"
            ;;
        display)
            tdd_display "${2:-}"
            ;;
        *)
            tdd_check_staged
            ;;
    esac
fi
