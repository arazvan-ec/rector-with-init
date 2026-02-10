#!/bin/bash
# read_dependencies.sh - Read project dependencies from package managers
# Returns JSON with dependency information
#
# Usage: ./read_dependencies.sh [path]
#
# Output: JSON object with dependencies

set -e

PROJECT_PATH="${1:-.}"

# Convert to absolute path
PROJECT_PATH="$(cd "$PROJECT_PATH" && pwd)"

echo "{"
echo "  \"path\": \"$PROJECT_PATH\","
echo "  \"analysis_date\": \"$(date -Iseconds)\","

# Read package.json
if [ -f "$PROJECT_PATH/package.json" ]; then
    echo "  \"npm\": {"
    echo "    \"exists\": true,"

    # Get name and version
    NAME=$(grep -m1 '"name"' "$PROJECT_PATH/package.json" 2>/dev/null | sed 's/.*: *"\([^"]*\)".*/\1/' || echo "unknown")
    VERSION=$(grep -m1 '"version"' "$PROJECT_PATH/package.json" 2>/dev/null | sed 's/.*: *"\([^"]*\)".*/\1/' || echo "unknown")

    echo "    \"name\": \"$NAME\","
    echo "    \"version\": \"$VERSION\","

    # Count dependencies
    DEP_COUNT=$(grep -c '"[^"]*":' "$PROJECT_PATH/package.json" 2>/dev/null | head -1 || echo "0")

    echo "    \"dependency_count\": $DEP_COUNT,"

    # Check for TypeScript
    if grep -q '"typescript"' "$PROJECT_PATH/package.json" 2>/dev/null; then
        echo "    \"typescript\": true,"
    else
        echo "    \"typescript\": false,"
    fi

    # Check for test frameworks
    echo "    \"test_frameworks\": ["
    TESTS=""
    if grep -q '"jest"' "$PROJECT_PATH/package.json" 2>/dev/null; then
        TESTS="$TESTS\"jest\","
    fi
    if grep -q '"vitest"' "$PROJECT_PATH/package.json" 2>/dev/null; then
        TESTS="$TESTS\"vitest\","
    fi
    if grep -q '"mocha"' "$PROJECT_PATH/package.json" 2>/dev/null; then
        TESTS="$TESTS\"mocha\","
    fi
    TESTS="${TESTS%,}"
    echo "      $TESTS"
    echo "    ]"

    echo "  },"
else
    echo "  \"npm\": {\"exists\": false},"
fi

# Read composer.json
if [ -f "$PROJECT_PATH/composer.json" ]; then
    echo "  \"composer\": {"
    echo "    \"exists\": true,"

    # Get name
    NAME=$(grep -m1 '"name"' "$PROJECT_PATH/composer.json" 2>/dev/null | sed 's/.*: *"\([^"]*\)".*/\1/' || echo "unknown")
    echo "    \"name\": \"$NAME\","

    # Check for PHPUnit
    if grep -q '"phpunit"' "$PROJECT_PATH/composer.json" 2>/dev/null; then
        echo "    \"phpunit\": true,"
    else
        echo "    \"phpunit\": false,"
    fi

    # Check for Symfony
    if grep -q 'symfony' "$PROJECT_PATH/composer.json" 2>/dev/null; then
        echo "    \"symfony\": true,"
    else
        echo "    \"symfony\": false,"
    fi

    # Check for Doctrine
    if grep -q 'doctrine' "$PROJECT_PATH/composer.json" 2>/dev/null; then
        echo "    \"doctrine\": true"
    else
        echo "    \"doctrine\": false"
    fi

    echo "  },"
else
    echo "  \"composer\": {\"exists\": false},"
fi

# Read requirements.txt
if [ -f "$PROJECT_PATH/requirements.txt" ]; then
    echo "  \"pip\": {"
    echo "    \"exists\": true,"

    # Count packages
    PKG_COUNT=$(grep -c '^[a-zA-Z]' "$PROJECT_PATH/requirements.txt" 2>/dev/null || echo "0")
    echo "    \"package_count\": $PKG_COUNT,"

    # Check for common frameworks
    echo "    \"frameworks\": ["
    FRAMEWORKS=""
    if grep -qi 'django' "$PROJECT_PATH/requirements.txt" 2>/dev/null; then
        FRAMEWORKS="$FRAMEWORKS\"django\","
    fi
    if grep -qi 'fastapi' "$PROJECT_PATH/requirements.txt" 2>/dev/null; then
        FRAMEWORKS="$FRAMEWORKS\"fastapi\","
    fi
    if grep -qi 'flask' "$PROJECT_PATH/requirements.txt" 2>/dev/null; then
        FRAMEWORKS="$FRAMEWORKS\"flask\","
    fi
    FRAMEWORKS="${FRAMEWORKS%,}"
    echo "      $FRAMEWORKS"
    echo "    ]"

    echo "  },"
else
    echo "  \"pip\": {\"exists\": false},"
fi

# Read go.mod
if [ -f "$PROJECT_PATH/go.mod" ]; then
    echo "  \"go\": {"
    echo "    \"exists\": true,"
    MODULE=$(grep -m1 '^module ' "$PROJECT_PATH/go.mod" 2>/dev/null | sed 's/module //' || echo "unknown")
    echo "    \"module\": \"$MODULE\""
    echo "  },"
else
    echo "  \"go\": {\"exists\": false},"
fi

# Read Cargo.toml
if [ -f "$PROJECT_PATH/Cargo.toml" ]; then
    echo "  \"cargo\": {"
    echo "    \"exists\": true,"
    NAME=$(grep -m1 '^name' "$PROJECT_PATH/Cargo.toml" 2>/dev/null | sed 's/.*= *"\([^"]*\)".*/\1/' || echo "unknown")
    echo "    \"name\": \"$NAME\""
    echo "  }"
else
    echo "  \"cargo\": {\"exists\": false}"
fi

echo "}"
