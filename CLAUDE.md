# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**gpe_go_api** - A new Go API project. The repository is currently empty (initial commit only).

## Project Status

This is a freshly initialized repository with no code yet. When implementing:

- The project name suggests this will be a Go-based REST API
- Located in XAMPP htdocs, suggesting local development environment

## Getting Started (Once Code Exists)

Standard Go commands will apply:

```bash
# Initialize module (if not done)
go mod init gpe_go_api

# Install dependencies
go mod tidy

# Run the application
go run .

# Run tests
go test ./...

# Build
go build -o gpe_api .
```

## Notes

- Git LFS is configured (see `.gitattributes`)
- Line endings are auto-normalized to LF
