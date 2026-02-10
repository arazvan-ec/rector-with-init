#!/bin/bash
# detect_framework.sh - Detect frameworks based on project files
# Returns JSON with detected frameworks
#
# Usage: ./detect_framework.sh [path]
#
# Output: JSON object with detected frameworks

set -e

PROJECT_PATH="${1:-.}"

# Convert to absolute path
PROJECT_PATH="$(cd "$PROJECT_PATH" && pwd)"

# Helper function to check if file contains string
file_contains() {
    local file="$1"
    local search="$2"
    [ -f "$file" ] && grep -qi "$search" "$file" 2>/dev/null
}

# Initialize arrays for frameworks
BACKEND_FRAMEWORKS=""
FRONTEND_FRAMEWORKS=""

# Detect Backend Frameworks
if [ -f "$PROJECT_PATH/symfony.lock" ] || [ -f "$PROJECT_PATH/config/bundles.php" ]; then
    BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Symfony\",\"language\":\"PHP\",\"confidence\":\"high\"},"
elif [ -f "$PROJECT_PATH/artisan" ]; then
    BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Laravel\",\"language\":\"PHP\",\"confidence\":\"high\"},"
elif [ -f "$PROJECT_PATH/composer.json" ]; then
    if file_contains "$PROJECT_PATH/composer.json" "symfony/framework"; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Symfony\",\"language\":\"PHP\",\"confidence\":\"medium\"},"
    elif file_contains "$PROJECT_PATH/composer.json" "laravel/framework"; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Laravel\",\"language\":\"PHP\",\"confidence\":\"medium\"},"
    fi
fi

if [ -f "$PROJECT_PATH/manage.py" ]; then
    BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Django\",\"language\":\"Python\",\"confidence\":\"high\"},"
elif [ -f "$PROJECT_PATH/requirements.txt" ]; then
    if file_contains "$PROJECT_PATH/requirements.txt" "fastapi"; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"FastAPI\",\"language\":\"Python\",\"confidence\":\"medium\"},"
    elif file_contains "$PROJECT_PATH/requirements.txt" "flask"; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Flask\",\"language\":\"Python\",\"confidence\":\"medium\"},"
    elif file_contains "$PROJECT_PATH/requirements.txt" "django"; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Django\",\"language\":\"Python\",\"confidence\":\"medium\"},"
    fi
fi

if [ -f "$PROJECT_PATH/package.json" ]; then
    if file_contains "$PROJECT_PATH/package.json" "\"express\""; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Express\",\"language\":\"Node.js\",\"confidence\":\"medium\"},"
    fi
    if file_contains "$PROJECT_PATH/package.json" "@nestjs/core"; then
        BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"NestJS\",\"language\":\"Node.js\",\"confidence\":\"medium\"},"
    fi
fi

if [ -f "$PROJECT_PATH/go.mod" ]; then
    BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Go\",\"language\":\"Go\",\"confidence\":\"high\"},"
fi

if [ -f "$PROJECT_PATH/Cargo.toml" ]; then
    BACKEND_FRAMEWORKS="$BACKEND_FRAMEWORKS{\"name\":\"Rust\",\"language\":\"Rust\",\"confidence\":\"high\"},"
fi

# Detect Frontend Frameworks
if [ -f "$PROJECT_PATH/package.json" ]; then
    if file_contains "$PROJECT_PATH/package.json" "\"react\""; then
        if file_contains "$PROJECT_PATH/package.json" "\"next\""; then
            FRONTEND_FRAMEWORKS="$FRONTEND_FRAMEWORKS{\"name\":\"Next.js\",\"language\":\"TypeScript/React\",\"confidence\":\"high\"},"
        else
            FRONTEND_FRAMEWORKS="$FRONTEND_FRAMEWORKS{\"name\":\"React\",\"language\":\"TypeScript/React\",\"confidence\":\"medium\"},"
        fi
    fi

    if file_contains "$PROJECT_PATH/package.json" "\"vue\""; then
        if [ -f "$PROJECT_PATH/nuxt.config.js" ] || [ -f "$PROJECT_PATH/nuxt.config.ts" ]; then
            FRONTEND_FRAMEWORKS="$FRONTEND_FRAMEWORKS{\"name\":\"Nuxt\",\"language\":\"TypeScript/Vue\",\"confidence\":\"high\"},"
        else
            FRONTEND_FRAMEWORKS="$FRONTEND_FRAMEWORKS{\"name\":\"Vue\",\"language\":\"TypeScript/Vue\",\"confidence\":\"medium\"},"
        fi
    fi

    if [ -f "$PROJECT_PATH/angular.json" ]; then
        FRONTEND_FRAMEWORKS="$FRONTEND_FRAMEWORKS{\"name\":\"Angular\",\"language\":\"TypeScript/Angular\",\"confidence\":\"high\"},"
    fi

    if file_contains "$PROJECT_PATH/package.json" "\"svelte\""; then
        FRONTEND_FRAMEWORKS="$FRONTEND_FRAMEWORKS{\"name\":\"Svelte\",\"language\":\"TypeScript/Svelte\",\"confidence\":\"medium\"},"
    fi
fi

# Remove trailing commas
BACKEND_FRAMEWORKS="${BACKEND_FRAMEWORKS%,}"
FRONTEND_FRAMEWORKS="${FRONTEND_FRAMEWORKS%,}"

# Output JSON
cat << EOF
{
  "path": "$PROJECT_PATH",
  "analysis_date": "$(date -Iseconds)",
  "backend": [
    $BACKEND_FRAMEWORKS
  ],
  "frontend": [
    $FRONTEND_FRAMEWORKS
  ],
  "package_managers": {
    "composer": $([ -f "$PROJECT_PATH/composer.json" ] && echo "true" || echo "false"),
    "npm": $([ -f "$PROJECT_PATH/package.json" ] && echo "true" || echo "false"),
    "yarn": $([ -f "$PROJECT_PATH/yarn.lock" ] && echo "true" || echo "false"),
    "pnpm": $([ -f "$PROJECT_PATH/pnpm-lock.yaml" ] && echo "true" || echo "false"),
    "pip": $([ -f "$PROJECT_PATH/requirements.txt" ] || [ -f "$PROJECT_PATH/Pipfile" ] && echo "true" || echo "false"),
    "cargo": $([ -f "$PROJECT_PATH/Cargo.toml" ] && echo "true" || echo "false"),
    "go_mod": $([ -f "$PROJECT_PATH/go.mod" ] && echo "true" || echo "false")
  }
}
EOF
