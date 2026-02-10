#!/bin/bash
# Wrapper para acceder fácilmente al workflow.sh desde la raíz del proyecto
exec "$(dirname "$0")/.ai/scripts/workflow.sh" "$@"
