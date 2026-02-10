#!/bin/bash
# analyze_structure.sh - Analyze project directory structure
# Returns JSON with directory analysis
#
# Usage: ./analyze_structure.sh [path]
#
# Output: JSON object with structure analysis

set -e

PROJECT_PATH="${1:-.}"

# Convert to absolute path
PROJECT_PATH="$(cd "$PROJECT_PATH" && pwd)"

# Initialize result
cat << EOF
{
  "path": "$PROJECT_PATH",
  "analysis_date": "$(date -Iseconds)",
  "directories": [
EOF

# Find directories (first 2 levels, exclude hidden)
first=true
find "$PROJECT_PATH" -maxdepth 2 -type d ! -path '*/\.*' ! -path "$PROJECT_PATH" 2>/dev/null | sort | while read -r dir; do
    relpath="${dir#$PROJECT_PATH/}"
    if [ "$first" = true ]; then
        first=false
    else
        echo ","
    fi
    echo -n "    \"$relpath\""
done

cat << EOF

  ],
  "has_backend": $([ -d "$PROJECT_PATH/backend" ] || [ -d "$PROJECT_PATH/api" ] || [ -d "$PROJECT_PATH/server" ] && echo "true" || echo "false"),
  "has_frontend": $([ -d "$PROJECT_PATH/frontend" ] || [ -d "$PROJECT_PATH/client" ] || [ -d "$PROJECT_PATH/web" ] && echo "true" || echo "false"),
  "has_tests": $([ -d "$PROJECT_PATH/tests" ] || [ -d "$PROJECT_PATH/test" ] || [ -d "$PROJECT_PATH/__tests__" ] && echo "true" || echo "false"),
  "has_docs": $([ -d "$PROJECT_PATH/docs" ] || [ -d "$PROJECT_PATH/documentation" ] && echo "true" || echo "false"),
  "has_ci": $([ -d "$PROJECT_PATH/.github" ] || [ -f "$PROJECT_PATH/.gitlab-ci.yml" ] && echo "true" || echo "false"),
  "has_docker": $([ -f "$PROJECT_PATH/Dockerfile" ] || [ -f "$PROJECT_PATH/docker-compose.yml" ] && echo "true" || echo "false"),
  "has_ai_workflow": $([ -d "$PROJECT_PATH/.ai" ] && echo "true" || echo "false"),
  "file_count": $(find "$PROJECT_PATH" -maxdepth 3 -type f ! -path '*/\.*' ! -path '*/node_modules/*' ! -path '*/vendor/*' 2>/dev/null | wc -l | tr -d ' ')
}
EOF
