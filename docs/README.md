# Dokumentasi SakaSantri

Struktur dokumentasi:

```text
docs/
├── architecture/
├── work/
└── _templates/
```

## Architecture

Dokumen canonical:

```text
docs/architecture/SakaSantri_Architecture_Baseline.md
```

## Module Documentation

Setiap module penting memiliki:

```text
app/Modules/<Namespace>/<Module>/README.md
```

README module fokus pada:

```text
Purpose
Owned concepts/data
Dependencies
Public contracts
Commands / Queries
Events produced
Events consumed
Permissions
Routes
Important business rules
Tables
Testing notes
```

## Work Package

```text
docs/work/<Namespace>/<Module>/<work-id>/
├── ADR.md
├── PLAN.md
└── TASKS.md
```

## Canonical Rule

```text
AGENTS.md              -> aturan repository
docs/architecture/     -> arsitektur
Module README          -> kebenaran module
docs/work/             -> eksekusi task
```
